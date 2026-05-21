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
        $query = StockTransaction::with(['item', 'creator']);

        // Filter by item_id
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->item_id);
        }

        // Filter by transaction type
        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        // Filter by reference type
        if ($request->filled('reference')) {
            $query->where('reference_type', $request->reference);
        }

        $transactions = $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'asc')
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

        return view('stocks.transactions', compact('transactions', 'items', 'referenceTypes'));
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
}
