<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Stock;
use App\Models\UOM;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ItemImportController extends Controller
{
    /**
     * Class code → item_type mapping
     */
    private const CLASS_MAP = [
        'CHEM'  => 'B',
        'COAT'  => 'A',
        'CONS'  => 'C',
        'EQUIP' => 'E',
        'TOOL'  => 'T',
        'TE'    => 'TE',
        // also accept the full item_type letters directly
        'A'     => 'A',
        'B'     => 'B',
        'C'     => 'C',
        'E'     => 'E',
        'T'     => 'T',
        'TE'    => 'TE',
    ];

    public function index()
    {
        return view('items.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        // Get the uploaded file directly from temp
        $uploadedFile = $request->file('file');
        $tempPath = $uploadedFile->getRealPath();

        try {
            $spreadsheet = IOFactory::load($tempPath);
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Could not read the file: ' . $e->getMessage()]);
        }

        // Try to find "Entry" sheet first, fall back to first sheet
        $sheet = null;
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (stripos($name, 'entry') !== false) {
                $sheet = $spreadsheet->getSheetByName($name);
                break;
            }
        }
        if (!$sheet) {
            $sheet = $spreadsheet->getActiveSheet();
        }

        $rows  = $sheet->toArray(null, true, false, false);

        // Detect header row (first row with "SKU" or "Nama Barang")
        $headerRow = 0;
        $colMap    = [];
        foreach ($rows as $idx => $row) {
            foreach ($row as $ci => $cell) {
                $val = strtolower(trim((string) $cell));
                if (in_array($val, ['sku', 'nama barang', 'class', 'int_unit', 'conv_unit', 'int_qty', 'conv_qty'])) {
                    $headerRow = $idx;
                    break 2;
                }
            }
        }

        // Build column index map from header row
        // Normalize column names: remove spaces, convert to lowercase
        foreach ($rows[$headerRow] as $ci => $cell) {
            $key = strtolower(trim((string) $cell));
            // Normalize: int_qty, intqty, int qty all map to 'int_qty'
            $normalized = str_replace([' ', '_'], '', $key);
            $colMap[$key] = $ci;
            // Also store normalized version for flexible matching
            if ($normalized === 'intqty')   $colMap['int_qty']  = $ci;
            if ($normalized === 'intunit')  $colMap['int_unit'] = $ci;
            if ($normalized === 'convqty')  $colMap['conv_qty'] = $ci;
            if ($normalized === 'convunit') $colMap['conv_unit'] = $ci;
            if ($normalized === 'saldo')    $colMap['saldo']    = $ci;
            if (in_array($normalized, ['openingavgcost', 'avgcost', 'avgprice', 'hargarata2', 'hargarata', 'averagecost'])) {
                $colMap['opening_avg_cost'] = $ci;
            }
            if (in_array($normalized, ['sellingprice', 'hargajual', 'harga_jual', 'sellprice', 'saleprice', 'unitprice'])) {
                $colMap['selling_price'] = $ci;
            }
        }

        $required = ['sku', 'nama barang'];
        foreach ($required as $col) {
            if (!isset($colMap[$col])) {
                return back()->withErrors(['file' => "Column '$col' not found in header row. Found headers: " . implode(', ', array_keys($colMap))]);
            }
        }

        $results = [
            'created' => [],
            'updated' => [],
            'skipped' => [],
            'errors'  => [],
            'debug'   => [
                'header_row'      => $headerRow + 1,
                'columns_found'   => array_keys($colMap),
                'int_qty_index'   => $colMap['int_qty'] ?? 'NOT FOUND',
                'conv_qty_index'  => $colMap['conv_qty'] ?? 'NOT FOUND',
                'int_unit_index'  => $colMap['int_unit'] ?? 'NOT FOUND',
                'conv_unit_index' => $colMap['conv_unit'] ?? 'NOT FOUND',
                'opening_avg_cost_index' => $colMap['opening_avg_cost'] ?? 'NOT FOUND',
                // Show raw values from first data row for verification
                'first_row_raw'   => isset($rows[$headerRow + 1]) ? $rows[$headerRow + 1] : [],
            ],
        ];

        $parseNumber = static function ($value, float $default = 0.0): float {
            if ($value === null) {
                return $default;
            }

            $raw = trim((string) $value);
            if ($raw === '') {
                return $default;
            }

            $raw = str_replace(' ', '', $raw);

            $hasComma = str_contains($raw, ',');
            $hasDot = str_contains($raw, '.');

            if ($hasComma && $hasDot) {
                // Common ID format: 1.234,56
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } elseif ($hasComma && !$hasDot) {
                // Decimal comma: 12,5
                $raw = str_replace(',', '.', $raw);
            } else {
                // Remove thousand separators if any stray commas remain
                $raw = str_replace(',', '', $raw);
            }

            if (!is_numeric($raw)) {
                return $default;
            }

            return (float) $raw;
        };

        $rowNum = $headerRow + 1;
        foreach (array_slice($rows, $headerRow + 1) as $row) {
            $rowNum++;

            $sku      = trim((string) ($row[$colMap['sku']] ?? ''));
            $name     = trim((string) ($row[$colMap['nama barang']] ?? ''));
            $classRaw = strtoupper(trim((string) ($row[$colMap['class'] ?? ''] ?? '')));

            // Extract numeric values with better fallback
            $intQty   = 1;
            if (isset($colMap['int_qty']) && isset($row[$colMap['int_qty']])) {
                $intQty = $parseNumber($row[$colMap['int_qty']], 1);
                if ($intQty <= 0) $intQty = 1;
            }

            $intUnit  = strtoupper(trim((string) ($row[$colMap['int_unit'] ?? ''] ?? '')));

            $convQty  = 1;
            if (isset($colMap['conv_qty']) && isset($row[$colMap['conv_qty']])) {
                $convQty = $parseNumber($row[$colMap['conv_qty']], 1);
                if ($convQty <= 0) $convQty = 1;
            }

            $convUnit = strtoupper(trim((string) ($row[$colMap['conv_unit'] ?? ''] ?? '')));

            // Saldo (opening stock quantity in smallest UOM)
            $saldo = 0.0;
            if (isset($colMap['saldo']) && isset($row[$colMap['saldo']])) {
                $saldo = $parseNumber($row[$colMap['saldo']], 0);
                if ($saldo < 0) $saldo = 0;
            }

            // Opening average cost (optional)
            $openingAvgCost = 0.0;
            if (isset($colMap['opening_avg_cost']) && isset($row[$colMap['opening_avg_cost']])) {
                $openingAvgCost = $parseNumber($row[$colMap['opening_avg_cost']], 0);
                if ($openingAvgCost < 0) $openingAvgCost = 0;
            }

            // Selling price (optional)
            $sellingPrice = null;
            if (isset($colMap['selling_price']) && isset($row[$colMap['selling_price']])) {
                $parsed = $parseNumber($row[$colMap['selling_price']], -1);
                if ($parsed >= 0) $sellingPrice = $parsed;
            }

            // Skip blank rows
            if ($sku === '' && $name === '') {
                continue;
            }
            if ($sku === '') {
                $results['skipped'][] = "Row $rowNum: SKU is blank (name: $name)";
                continue;
            }
            if ($name === '') {
                $results['skipped'][] = "Row $rowNum: Name is blank (SKU: $sku)";
                continue;
            }

            // Map class to item_type. If class is unknown/blank, infer from SKU prefix.
            $itemType = self::CLASS_MAP[$classRaw] ?? null;

            if (!$itemType) {
                $skuUpper = strtoupper($sku);
                if (str_starts_with($skuUpper, 'TE')) {
                    $itemType = 'TE';
                } else {
                    $firstChar = substr($skuUpper, 0, 1);
                    $itemType = in_array($firstChar, ['A', 'B', 'C', 'E', 'T']) ? $firstChar : 'C';
                }
            }

            // Resolve / create UOMs
            // Conv_Unit is the smallest/tracking unit
            $smallestUom = null;
            if ($convUnit !== '') {
                $smallestUom = UOM::firstOrCreate(
                    ['code' => $convUnit],
                    ['name' => $convUnit, 'is_active' => true]
                );
            }

            // Int_Unit is the larger/purchase unit
            $intUom = null;
            if ($intUnit !== '' && $intUnit !== $convUnit) {
                $intUom = UOM::firstOrCreate(
                    ['code' => $intUnit],
                    ['name' => $intUnit, 'is_active' => true]
                );
            }

            try {
                $existed = Item::where('code', $sku)->exists();
                $stockExists = false; // Initialize
                $stockCreated = false; // Initialize
                $avgCostUpdated = false; // Initialize

                $itemData = [
                        'name'           => $name,
                        'item_type'      => $itemType,
                        'category'       => $classRaw,
                        'smallest_uom_id' => $smallestUom?->id,
                        'is_active'      => true,
                    ];
                    if ($sellingPrice !== null) {
                        $itemData['selling_price'] = $sellingPrice;
                    }
                    $item = Item::updateOrCreate(
                    ['code' => $sku],
                    $itemData
                );

                // Always ensure smallest UOM ItemUOM exists (conversion = 1)
                if ($smallestUom) {
                    $iuSmallest = ItemUOM::firstOrNew(['item_id' => $item->id, 'uom_id' => $smallestUom->id]);
                    $iuSmallest->conversion_to_smallest = 1;
                    $iuSmallest->is_default = true; // Always set smallest as default
                    $iuSmallest->save();

                    // Check if stock record exists BEFORE we try to create it
                    $stockRecord = Stock::where('item_id', $item->id)
                        ->where('location', 'default')
                        ->first();

                    $stockExists = ($stockRecord !== null);
                    $stockCreated = false;

                    // Create or update stock based on whether item and stock existed
                    if (!$stockExists) {
                        // No stock exists - create it
                        Stock::create([
                            'item_id'  => $item->id,
                            'location' => 'default',
                            'quantity' => $saldo,
                            'avg_cost' => $openingAvgCost,
                        ]);
                        $stockCreated = true;

                        // Create opening transaction if saldo > 0
                        if ($saldo > 0) {
                            \App\Models\StockTransaction::create([
                                'item_id'          => $item->id,
                                'transaction_type' => 'in',
                                'quantity'         => $saldo,
                                'unit_cost'        => $openingAvgCost,
                                'balance_after'    => $saldo,
                                'location'         => 'default',
                                'reference_type'   => 'OPENING',
                                'reference_id'     => null,
                                'notes'            => 'Opening balance (Saldo awal) from item import',
                                'created_by'       => auth()->id(),
                            ]);
                        }
                    } else {
                        // Stock exists - allow one-time backfill of avg_cost if currently zero
                        // and import provides a positive opening average cost.
                        if ($stockRecord && (float) $stockRecord->avg_cost <= 0 && $openingAvgCost > 0) {
                            $stockRecord->avg_cost = $openingAvgCost;
                            $stockRecord->save();
                            $avgCostUpdated = true;
                        }
                    }
                    // If stock exists, don't overwrite (use Bon In for adjustments)
                }

                // Int UOM (larger/purchase UOM) if different from smallest
                if ($intUom) {
                    // Conv_Qty IS the conversion factor directly
                    // e.g. Conv_Qty=1000 means 1 Int_Unit = 1000 Conv_Unit
                    $conversionFactor = $convQty;

                    $iuInt = ItemUOM::firstOrNew(['item_id' => $item->id, 'uom_id' => $intUom->id]);
                    $iuInt->conversion_to_smallest = $conversionFactor;
                    $iuInt->is_default = false; // Smallest UOM is default, not the larger one
                    $iuInt->save();
                }

                if ($existed) {
                    $stockMsg = $stockCreated
                        ? ($saldo > 0
                            ? " | Stock created with saldo: {$saldo}, avg cost: {$openingAvgCost}"
                            : " | Stock created: 0, avg cost: {$openingAvgCost}")
                        : '';

                    if (!$stockCreated && $avgCostUpdated) {
                        $stockMsg .= " | Avg cost updated to: {$openingAvgCost}";
                    }

                    $results['updated'][] = sprintf(
                        '%s – %s | int: %s %s | conv: %s %s | factor: %s%s',
                        $sku,
                        $name,
                        $intQty,
                        $intUnit,
                        $convQty,
                        $convUnit,
                        $intUom ? $convQty : '(same unit)',
                        $stockMsg
                    );
                } else {
                    $saldoMsg = $saldo > 0
                        ? " | Opening stock: {$saldo} | Opening avg cost: {$openingAvgCost}"
                        : " | Opening stock: 0 | Opening avg cost: {$openingAvgCost}";
                    $results['created'][] = sprintf(
                        '%s – %s | int: %s %s | conv: %s %s | factor: %s%s',
                        $sku,
                        $name,
                        $intQty,
                        $intUnit,
                        $convQty,
                        $convUnit,
                        $intUom ? $convQty : '(same unit)',
                        $saldoMsg
                    );
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Row $rowNum ($sku): " . $e->getMessage();
            }
        }

        return view('items.import_result', compact('results'));
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Entry');

        // Headers
        $headers = ['ENTRY DATE', 'SKU', 'Class', 'Nama Barang', 'Int_Qty', 'Int_Unit', 'Conv_Qty', 'Conv_Unit', 'Saldo', 'Opening_Avg_Cost', 'Selling_Price'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}1", $h);
        }

        // Header style
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F497D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:K1')->applyFromArray($headerStyle);

        // Highlight Saldo column header in a different colour
        $sheet->getStyle('I1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '375623']],
        ]);

        // Highlight Opening_Avg_Cost column header in a different colour
        $sheet->getStyle('J1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7F6000']],
        ]);

        // Highlight Selling_Price column header
        $sheet->getStyle('K1')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '375623']],
        ]);

        // Example rows
        $examples = [
            ['5/28/2025', 'B0001', 'CHEM', 'IGL Eco Sine Dastt (Dashboard)',       1, 'GR',  1,   'GR',  50,  15000, 25000],
            ['5/28/2025', 'B0002', 'CHEM', 'IGL Eco Clean Delete (Water Spot)',    1, 'GR',  1,   'GR',  0,   0,     0],
            ['5/28/2025', 'A0001', 'COAT', 'IGL Kenzo Ceramic Coating 9H',         1, 'ML',  30,  'ML',  120, 8000,  12000],
            ['5/28/2025', 'C0001', 'CONS', 'Microfiber Cloth',                     1, 'PCS', 10,  'PCS', 25,  2500,  5000],
            ['5/28/2025', 'C0002', 'CONS', 'Masking Tape 1"',                      1, 'PCS', 100, 'ROLL', 0,  0,     0],
            ['5/28/2025', 'E0001', 'EQUIP', 'Polishing Machine',                   1, 'PCS', 1,   'PCS', 2,   1200000, 1500000],
        ];
        foreach ($examples as $ri => $row) {
            foreach ($row as $ci => $val) {
                $col = chr(65 + $ci);
                $sheet->setCellValue("{$col}" . ($ri + 2), $val);
            }
        }

        // Column widths
        $widths = [12, 10, 8, 40, 8, 10, 8, 10, 10, 16, 14];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
        }

        // Notes sheet
        $noteSheet = $spreadsheet->createSheet();
        $noteSheet->setTitle('Notes');
        $noteSheet->setCellValue('A1', 'Class Codes');
        $noteSheet->setCellValue('A2', 'CHEM  = Chemical (B)');
        $noteSheet->setCellValue('A3', 'COAT  = Coating (A)');
        $noteSheet->setCellValue('A4', 'CONS  = Consumable (C)');
        $noteSheet->setCellValue('A5', 'EQUIP = Equipment (E)');
        $noteSheet->setCellValue('A6', 'TOOL  = Tools (T)');
        $noteSheet->setCellValue('A7', 'TE    = Tools & Equipment (TE)');
        $noteSheet->setCellValue('A9',  'Conv_Unit = Smallest/tracking unit (e.g. GR, ML, PCS)');
        $noteSheet->setCellValue('A10', 'Int_Unit = Purchase/order unit (e.g. KG, L, BOX)');
        $noteSheet->setCellValue('A11', 'Int_Qty / Conv_Qty = conversion factor');
        $noteSheet->setCellValue('A12', 'Example: Conv_Qty=1000 Conv_Unit=GR, Int_Qty=1 Int_Unit=KG → 1 KG = 1000 GR');
        $noteSheet->setCellValue('A13', 'Conv_Unit is ALWAYS the default unit for stock tracking and expenditures');
        $noteSheet->setCellValue('A14', 'Saldo = Opening stock quantity in the smallest unit (Conv_Unit)');
        $noteSheet->setCellValue('A15', 'Leave Saldo blank or 0 for new items with no stock yet.');
        $noteSheet->setCellValue('A16', 'Opening_Avg_Cost = opening average cost per smallest unit (optional; leave blank/0 if unknown).');
        $noteSheet->setCellValue('A17', 'If item already exists: Saldo is ignored; Opening_Avg_Cost will only update avg_cost when current avg_cost is 0.');
        $noteSheet->setCellValue('A18', 'Selling_Price = selling price per smallest unit used to auto-fill unit price in Sales Orders (accounting only).');
        $noteSheet->getColumnDimension('A')->setAutoSize(true);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $temp = tempnam(sys_get_temp_dir(), 'item_template_') . '.xlsx';
        $writer->save($temp);

        return response()->download($temp, 'Item_Import_Template.xlsx')->deleteFileAfterSend(true);
    }
}
