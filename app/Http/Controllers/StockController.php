<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\Item;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::with(['item.smallestUom', 'item.itemUoms.uom'])
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->select('stocks.*')
            ->orderBy('items.name');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('items.code', 'like', "%{$search}%")
                    ->orWhere('items.name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'good') {
                $query->whereColumn('stocks.quantity', '>', 'items.reorder_level');
            } elseif ($request->status === 'low') {
                $query->where('stocks.quantity', '>', 0)
                    ->whereColumn('stocks.quantity', '<=', 'items.reorder_level');
            } elseif ($request->status === 'out_of_stock') {
                $query->where('stocks.quantity', '<=', 0);
            }
        }

        if ($request->filled('item_type')) {
            $query->where('items.item_type', $request->item_type);
        }

        $stocks = $query->get();

        $lowStockCount = Stock::query()
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->whereColumn('stocks.quantity', '<=', 'items.reorder_level')
            ->count();

        $adjustableItems = Item::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $itemTypes = Item::getItemTypes();

        return view('stocks.index', compact('stocks', 'lowStockCount', 'adjustableItems', 'itemTypes'));
    }

    public function transactions(Request $request)
    {
        $query = StockTransaction::with(['item', 'creator'])
            ->join('items', 'stock_transactions.item_id', '=', 'items.id')
            ->select('stock_transactions.*');

        // Filter by item_id
        if ($request->filled('item_id')) {
            $query->where('stock_transactions.item_id', $request->item_id);
        }

        // Filter by transaction type
        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        // Filter by reference type
        if ($request->filled('reference')) {
            $query->where('reference_type', $request->reference);
        }

        // Filter by month
        if ($request->filled('month')) {
            $query->whereMonth('stock_transactions.created_at', (int) $request->month);
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->whereYear('stock_transactions.created_at', (int) $request->year);
        }

        // Filter by item category
        if ($request->filled('category')) {
            $query->where('items.item_type', $request->category);
        }

        $transactions = $query
            ->orderBy('stock_transactions.created_at', 'desc')
            ->orderBy('stock_transactions.id', 'asc')
            ->paginate(50)
            ->withQueryString();

        // Get all items for filter dropdown
        $items = Item::orderBy('name')->get(['id', 'name', 'code']);

        // Get unique reference types
        $referenceTypes = StockTransaction::select('reference_type')
            ->distinct()
            ->whereNotNull('reference_type')
            ->orderBy('reference_type')
            ->pluck('reference_type');

        $allYears = StockTransaction::selectRaw('YEAR(created_at) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');

        $categories = Item::select('item_type')
            ->distinct()->orderBy('item_type')->pluck('item_type');

        return view('stocks.transactions', compact('transactions', 'items', 'referenceTypes', 'allYears', 'categories'));
    }

    public function adjust(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'adjustment_type' => 'required|in:set,add,subtract',
            'quantity' => 'required|numeric|min:0',
            'notes' => 'required|string|min:5',
        ]);

        $stock = Stock::where('item_id', $validated['item_id'])
            ->where('location', 'default')
            ->firstOrFail();

        $oldQuantity = $stock->quantity;

        switch ($validated['adjustment_type']) {
            case 'set':
                $newQuantity = $validated['quantity'];
                break;
            case 'add':
                $newQuantity = $oldQuantity + $validated['quantity'];
                break;
            case 'subtract':
                $newQuantity = max(0, $oldQuantity - $validated['quantity']);
                break;
        }

        $stock->update(['quantity' => $newQuantity]);

        // Create transaction record
        StockTransaction::create([
            'item_id' => $validated['item_id'],
            'transaction_type' => 'adjustment',
            'quantity' => $newQuantity - $oldQuantity,
            'balance_after' => $newQuantity,
            'location' => 'default',
            'reference_type' => 'Manual Adjustment',
            'reference_id' => null,
            'notes' => $validated['notes'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('stocks.index')->with('success', 'Stock adjusted successfully!');
    }

    public function export()
    {
        $stocks = Stock::with(['item.smallestUom', 'item.itemUoms.uom'])
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->select('stocks.*')
            ->orderBy('items.code')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'STOCK OPNAME FORM');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set date
        $sheet->setCellValue('A2', 'Date: ' . now()->format('d M Y'));
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);

        // Set headers
        $headers = [
            'Item Code',
            'Item Name',
            'Item Type',
            'System Stock',
            'UOM',
            'Physical Count',
            'Difference',
            'Notes'
        ];

        $row = 4;
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }

        // Style headers
        $sheet->getStyle('A4:H4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);

        // Fill data
        $row = 5;
        foreach ($stocks as $stock) {
            $statusClass = $stock->quantity <= $stock->item->reorder_level ? 'warning' : 'good';
            
            $sheet->setCellValue('A' . $row, $stock->item->code);
            $sheet->setCellValue('B' . $row, $stock->item->name);
            $sheet->setCellValue('C' . $row, $stock->item->item_type_name);
            $sheet->setCellValue('D' . $row, number_format($stock->quantity, 2, '.', ''));
            $sheet->setCellValue('E' . $row, $stock->item->smallestUom->code);
            $sheet->setCellValue('F' . $row, ''); // For manual entry
            $sheet->setCellValue('G' . $row, '=IF(F' . $row . '<>"",F' . $row . '-D' . $row . ',"")'); // Auto calculate difference
            $sheet->setCellValue('H' . $row, '');

            // Highlight low stock rows
            if ($statusClass === 'warning') {
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF3CD']
                    ]
                ]);
            }

            $row++;
        }

        // Apply borders to all data
        $lastRow = $row - 1;
        $sheet->getStyle('A4:H' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN]
            ]
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(15);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(30);

        // Center align numeric columns
        $sheet->getStyle('D5:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E5:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add instructions at the bottom
        $row = $lastRow + 2;
        $sheet->setCellValue('A' . $row, 'Instructions:');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue('A' . $row, '1. Fill in the "Physical Count" column with actual counted quantities');
        $row++;
        $sheet->setCellValue('A' . $row, '2. The "Difference" column will automatically calculate (Physical - System)');
        $row++;
        $sheet->setCellValue('A' . $row, '3. Add notes for any discrepancies in the "Notes" column');
        $row++;
        $sheet->setCellValue('A' . $row, '4. Yellow highlighted rows indicate low stock items');

        // Generate filename
        $filename = 'Stock_Opname_' . now()->format('Y-m-d_His') . '.xlsx';

        // Write file
        $writer = new Xlsx($spreadsheet);

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function exportWithPrices()
    {
        if (!\App\Helpers\PermissionHelper::canViewPrices()) {
            abort(403, 'Access denied.');
        }

        $stocks = Stock::with(['item.smallestUom', 'item.itemUoms.uom'])
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->select('stocks.*')
            ->orderBy('items.code')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock with Prices');

        // Title
        $sheet->setCellValue('A1', 'STOCK LIST WITH PRICES');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Date & generated by
        $sheet->setCellValue('A2', 'Generated: ' . now()->format('d M Y H:i') . '   —   ' . auth()->user()->name);
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);
        $sheet->getStyle('A2')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF666666'));

        // Headers
        $headers = [
            'No.',
            'Item Code',
            'Item Name',
            'Item Type',
            'Current Stock',
            'UOM',
            'Reorder Level',
            'Status',
            'Avg. Purchase Cost (Rp)',
            'Selling Price (Rp)',
            'Stock Value (Rp)',
        ];

        $headerRow = 4;
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $headerRow, $header);
            $col++;
        }

        // Style header row
        $sheet->getStyle('A4:K4')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // Fill data
        $dataRow = 5;
        $no = 1;
        $totalStockValue = 0;

        foreach ($stocks as $stock) {
            $isLow = $stock->quantity <= $stock->item->reorder_level && $stock->quantity > 0;
            $isOut = $stock->quantity <= 0;

            $statusLabel = $isOut ? 'Out of Stock' : ($isLow ? 'Low' : 'Good');
            $avgCost     = (float) ($stock->avg_cost ?? 0);
            $sellingPrice = (float) ($stock->item->selling_price ?? 0);
            $stockValue  = $stock->quantity * $avgCost;
            $totalStockValue += $stockValue;

            $sheet->setCellValue('A' . $dataRow, $no++);
            $sheet->setCellValue('B' . $dataRow, $stock->item->code);
            $sheet->setCellValue('C' . $dataRow, $stock->item->name);
            $sheet->setCellValue('D' . $dataRow, $stock->item->item_type_name);
            $sheet->setCellValue('E' . $dataRow, (float) number_format($stock->quantity, 2, '.', ''));
            $sheet->setCellValue('F' . $dataRow, $stock->item->smallestUom->code);
            $sheet->setCellValue('G' . $dataRow, (float) number_format($stock->item->reorder_level, 2, '.', ''));
            $sheet->setCellValue('H' . $dataRow, $statusLabel);
            $sheet->setCellValue('I' . $dataRow, $avgCost);
            $sheet->setCellValue('J' . $dataRow, $sellingPrice);
            $sheet->setCellValue('K' . $dataRow, $stockValue);

            // Number format for price columns
            $sheet->getStyle('I' . $dataRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('J' . $dataRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('K' . $dataRow)->getNumberFormat()->setFormatCode('#,##0.00');

            // Row color
            if ($isOut) {
                $bgColor = 'FADADD';
            } elseif ($isLow) {
                $bgColor = 'FFF3CD';
            } else {
                $bgColor = $no % 2 === 0 ? 'FFFFFF' : 'F2F7FC';
            }

            $sheet->getStyle('A' . $dataRow . ':K' . $dataRow)->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            ]);

            // Status badge colors
            if ($isOut) {
                $sheet->getStyle('H' . $dataRow)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCC0000'));
                $sheet->getStyle('H' . $dataRow)->getFont()->setBold(true);
            } elseif ($isLow) {
                $sheet->getStyle('H' . $dataRow)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF856404'));
                $sheet->getStyle('H' . $dataRow)->getFont()->setBold(true);
            } else {
                $sheet->getStyle('H' . $dataRow)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF155724'));
                $sheet->getStyle('H' . $dataRow)->getFont()->setBold(true);
            }

            $dataRow++;
        }

        // Total row
        $lastRow = $dataRow - 1;
        $sheet->setCellValue('J' . $dataRow, 'TOTAL STOCK VALUE:');
        $sheet->getStyle('J' . $dataRow)->getFont()->setBold(true);
        $sheet->getStyle('J' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('K' . $dataRow, $totalStockValue);
        $sheet->getStyle('K' . $dataRow)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('K' . $dataRow)->getFont()->setBold(true);
        $sheet->getStyle('J' . $dataRow . ':K' . $dataRow)->applyFromArray([
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E8F5']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        // Apply outer border to data block
        $sheet->getStyle('A4:K' . $dataRow)->applyFromArray([
            'borders' => [
                'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1F4E79']],
            ],
        ]);

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(16);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(8);
        $sheet->getColumnDimension('G')->setWidth(14);
        $sheet->getColumnDimension('H')->setWidth(13);
        $sheet->getColumnDimension('I')->setWidth(22);
        $sheet->getColumnDimension('J')->setWidth(22);
        $sheet->getColumnDimension('K')->setWidth(22);

        // Alignments
        $sheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E5:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('F5:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G5:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H5:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('I5:K' . $dataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Freeze header row
        $sheet->freezePane('A5');

        $filename = 'Stock_With_Prices_' . now()->format('Y-m-d_His') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
