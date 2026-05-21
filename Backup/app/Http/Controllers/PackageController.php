<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Item;
use App\Models\Package;
use App\Models\PackageBomItem;
use App\Models\PackageSize;
use App\Models\UOM;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PackageController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('packages')) {
            return PermissionHelper::denyAccess('packages', 'view');
        }

        $packages = Package::with('sizes')->orderBy('category')->orderBy('code')->get();
        $groupedPackages = $packages->groupBy('category');

        return view('packages.index', compact('groupedPackages'));
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('packages')) {
            return PermissionHelper::denyAccess('packages', 'create');
        }

        $items = Item::where('is_active', true)->with(['smallestUom'])->orderBy('name')->get();
        $uoms  = UOM::orderBy('name')->get();
        return view('packages.create', compact('items', 'uoms'));
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('packages')) {
            return PermissionHelper::denyAccess('packages', 'create');
        }

        $validated = $request->validate([
            'category'            => 'required|string|max:100',
            'code'                => 'required|string|max:20|unique:packages,code',
            'name'                => 'required|string|max:100',
            'description'         => 'nullable|string',
            'is_active'           => 'boolean',
            'sizes'               => 'required|array|min:1',
            'sizes.*.size_name'   => 'required|string|max:50',
            'sizes.*.price'       => 'required|numeric|min:0',
            'sizes.*.is_active'   => 'boolean',
            'bom'                 => 'nullable|array',
            'bom.*.item_id'       => 'required|exists:items,id',
            'bom.*.uom_id'        => 'required|exists:uoms,id',
            'bom.*.quantity'      => 'required|numeric|min:0.001',
            'bom.*.notes'         => 'nullable|string|max:255',
        ]);

        $package = Package::create([
            'category'    => $validated['category'],
            'code'        => strtoupper($validated['code']),
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        foreach ($validated['sizes'] as $sizeData) {
            PackageSize::create([
                'package_id' => $package->id,
                'size_name'  => $sizeData['size_name'],
                'price'      => $sizeData['price'],
                'is_active'  => $sizeData['is_active'] ?? true,
            ]);
        }

        foreach (($validated['bom'] ?? []) as $bomData) {
            PackageBomItem::create([
                'package_id' => $package->id,
                'item_id'    => $bomData['item_id'],
                'uom_id'     => $bomData['uom_id'],
                'quantity'   => $bomData['quantity'],
                'notes'      => $bomData['notes'] ?? null,
            ]);
        }

        return redirect()->route('packages.index')
            ->with('success', 'Package created successfully.');
    }

    public function show(Package $package)
    {
        if (!PermissionHelper::canView('packages')) {
            return PermissionHelper::denyAccess('packages', 'view');
        }

        $package->load(['sizes', 'bomItems.item.smallestUom', 'bomItems.uom']);
        return view('packages.show', compact('package'));
    }

    public function edit(Package $package)
    {
        if (!PermissionHelper::canUpdate('packages')) {
            return PermissionHelper::denyAccess('packages', 'update');
        }

        $package->load(['sizes', 'bomItems.item.smallestUom', 'bomItems.uom']);
        $items = Item::where('is_active', true)->with(['smallestUom'])->orderBy('name')->get();
        $uoms  = UOM::orderBy('name')->get();
        return view('packages.edit', compact('package', 'items', 'uoms'));
    }

    public function update(Request $request, Package $package)
    {
        if (!PermissionHelper::canUpdate('packages')) {
            return PermissionHelper::denyAccess('packages', 'update');
        }

        $validated = $request->validate([
            'category'            => 'required|string|max:100',
            'code'                => 'required|string|max:20|unique:packages,code,' . $package->id,
            'name'                => 'required|string|max:100',
            'description'         => 'nullable|string',
            'is_active'           => 'boolean',
            'sizes'               => 'required|array|min:1',
            'sizes.*.id'          => 'nullable|exists:package_sizes,id',
            'sizes.*.size_name'   => 'required|string|max:50',
            'sizes.*.price'       => 'required|numeric|min:0',
            'sizes.*.is_active'   => 'boolean',
            'bom'                 => 'nullable|array',
            'bom.*.item_id'       => 'required|exists:items,id',
            'bom.*.uom_id'        => 'required|exists:uoms,id',
            'bom.*.quantity'      => 'required|numeric|min:0.001',
            'bom.*.notes'         => 'nullable|string|max:255',
        ]);

        $package->update([
            'category'    => $validated['category'],
            'code'        => strtoupper($validated['code']),
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        // ---- Sync sizes ----
        $existingSizeIds = [];
        foreach ($validated['sizes'] as $sizeData) {
            if (!empty($sizeData['id'])) {
                $size = PackageSize::find($sizeData['id']);
                if ($size && $size->package_id == $package->id) {
                    $size->update([
                        'size_name' => $sizeData['size_name'],
                        'price'     => $sizeData['price'],
                        'is_active' => $sizeData['is_active'] ?? true,
                    ]);
                    $existingSizeIds[] = $size->id;
                }
            } else {
                $size = PackageSize::create([
                    'package_id' => $package->id,
                    'size_name'  => $sizeData['size_name'],
                    'price'      => $sizeData['price'],
                    'is_active'  => $sizeData['is_active'] ?? true,
                ]);
                $existingSizeIds[] = $size->id;
            }
        }
        PackageSize::where('package_id', $package->id)
            ->whereNotIn('id', $existingSizeIds)
            ->delete();

        // ---- Sync BOM items (replace all) ----
        PackageBomItem::where('package_id', $package->id)->delete();
        foreach (($validated['bom'] ?? []) as $bomData) {
            PackageBomItem::create([
                'package_id' => $package->id,
                'item_id'    => $bomData['item_id'],
                'uom_id'     => $bomData['uom_id'],
                'quantity'   => $bomData['quantity'],
                'notes'      => $bomData['notes'] ?? null,
            ]);
        }

        return redirect()->route('packages.index')
            ->with('success', 'Package updated successfully.');
    }

    public function destroy(Package $package)
    {
        if (!PermissionHelper::canDelete('packages')) {
            return PermissionHelper::denyAccess('packages', 'delete');
        }

        $package->delete();
        return redirect()->route('packages.index')
            ->with('success', 'Package deleted successfully.');
    }

    // ================================================================
    //  BOM Import
    // ================================================================

    /**
     * Normalize string for matching: trim, lowercase, collapse multiple spaces
     */
    private function normalizeString($str)
    {
        // Trim whitespace
        $str = trim($str);
        // Replace non-breaking spaces and other Unicode spaces with regular space
        $str = preg_replace('/\s+/u', ' ', $str);
        // Lowercase
        $str = strtolower($str);
        return $str;
    }

    /**
     * Find similar item names using fuzzy matching
     */
    private function findSimilarItems($searchName, $allItems, $threshold = 70, $limit = 3)
    {
        $suggestions = [];
        $searchNormalized = $this->normalizeString($searchName);

        foreach ($allItems as $itemName => $itemId) {
            similar_text($searchNormalized, $this->normalizeString($itemName), $percent);
            if ($percent >= $threshold) {
                $suggestions[] = ['name' => $itemName, 'score' => $percent];
            }
        }

        // Sort by score descending
        usort($suggestions, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($suggestions, 0, $limit);
    }

    public function importBomIndex()
    {
        return view('packages.import_bom');
    }

    public function showItemNames()
    {
        $items = Item::with('smallestUom:id,code,name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'smallest_uom_id']);
        return view('packages.item_names', compact('items'));
    }

    public function importBom(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        } catch (\Exception $e) {
            return back()->withErrors(['file' => 'Cannot read file: ' . $e->getMessage()]);
        }

        // Find the Entry/BOM sheet, else use active
        $sheet = null;
        foreach ($spreadsheet->getSheetNames() as $name) {
            if (stripos($name, 'entry') !== false || stripos($name, 'bom') !== false) {
                $sheet = $spreadsheet->getSheetByName($name);
                break;
            }
        }
        $sheet = $sheet ?? $spreadsheet->getActiveSheet();
        $rows  = $sheet->toArray(null, true, false, false);

        // Detect header row containing expected column names
        $headerRow = 0;
        $colMap    = [];
        foreach ($rows as $idx => $row) {
            foreach ($row as $cell) {
                $val = strtolower(trim((string) $cell));
                if (in_array($val, ['package_code', 'item_name', 'item_code', 'part_number', 'qty', 'quantity', 'uom', 'notes'])) {
                    $headerRow = $idx;
                    break 2;
                }
            }
        }

        foreach ($rows[$headerRow] as $ci => $cell) {
            $key = strtolower(str_replace([' ', '-'], '_', trim((string) $cell)));
            $colMap[$key] = $ci;
            if ($key === 'qty') $colMap['quantity'] = $ci;
            // Map PART_NUMBER to item_code
            if ($key === 'part_number') $colMap['item_code'] = $ci;
        }

        // Accept item_name, item_code, or part_number (prefer item_code/part_number)
        $itemColumn = 'item_name';
        if (isset($colMap['item_code'])) {
            $itemColumn = 'item_code';
        } elseif (!isset($colMap['item_name']) && !isset($colMap['item_code'])) {
            // Neither found
        }

        // Validate required columns (Quantity and UOM are now optional, will default)
        foreach (['package_code', $itemColumn] as $req) {
            if (!isset($colMap[$req])) {
                $foundCols = implode(', ', array_keys($colMap));
                return back()->withErrors(['file' => "Column '{$req}' not found. Expected 'Package_Code' and 'PART_NUMBER' (or 'Item_Code' or 'Item_Name'). Found: {$foundCols}"]);
            }
        }

        $results = ['added' => [], 'updated' => [], 'skipped' => [], 'errors' => []];
        $rowNum  = $headerRow + 1;

        // Pre-load lookup maps
        $packages = Package::pluck('id', 'code')->toArray();
        // For items: create normalized name lookup AND keep original names for suggestions
        $allItems = Item::all();
        $itemsByName = $allItems->mapWithKeys(function ($item) {
            return [$this->normalizeString($item->name) => $item->id];
        })->toArray();
        $itemNamesOriginal = $allItems->pluck('name', 'name')->toArray(); // For fuzzy matching
        $itemsByCode = Item::pluck('id', 'code')->toArray();
        $uoms = UOM::pluck('id', 'code')->toArray();

        foreach (array_slice($rows, $headerRow + 1) as $row) {
            $rowNum++;

            $pkgCode   = strtoupper(trim((string) ($row[$colMap['package_code']] ?? '')));
            $itemValue = trim((string) ($row[$colMap[$itemColumn]] ?? ''));
            $qty       = (float) str_replace([',', ' '], '', (string) ($row[$colMap['quantity']] ?? ''));
            $uomCode   = strtoupper(trim((string) ($row[$colMap['uom']] ?? '')));
            $notes     = trim((string) ($row[$colMap['notes'] ?? ''] ?? ''));

            // Default quantity to 1 if blank or zero
            if ($qty <= 0) {
                $qty = 1;
            }

            if ($pkgCode === '' && $itemValue === '') continue;

            if ($pkgCode === '') {
                $results['skipped'][] = "Row {$rowNum}: Package_Code is blank";
                continue;
            }
            if ($itemValue === '') {
                $results['skipped'][] = "Row {$rowNum}: Item is blank (package: {$pkgCode})";
                continue;
            }

            $packageId = $packages[$pkgCode] ?? null;
            if (!$packageId) {
                $results['errors'][] = "Row {$rowNum}: Package '{$pkgCode}' not found in database";
                continue;
            }

            // Try to find item by name (normalized) or code
            $itemId = null;
            if ($itemColumn === 'item_name') {
                $itemId = $itemsByName[$this->normalizeString($itemValue)] ?? null;
            } else {
                $itemId = $itemsByCode[strtoupper($itemValue)] ?? null;
            }

            if (!$itemId) {
                // Fuzzy match to suggest similar items
                if ($itemColumn === 'item_name') {
                    $similar = $this->findSimilarItems($itemValue, $itemNamesOriginal, 60, 3);
                    if (!empty($similar)) {
                        $suggestions = implode(', ', array_map(fn($s) => "'{$s['name']}'", $similar));
                        $results['errors'][] = "Row {$rowNum}: Item '{$itemValue}' not found. Did you mean: {$suggestions}";
                    } else {
                        $results['errors'][] = "Row {$rowNum}: Item '{$itemValue}' not found (no similar matches)";
                    }
                } else {
                    $results['errors'][] = "Row {$rowNum}: Item code '{$itemValue}' not found in database";
                }
                continue;
            }

            // Find UOM or use item's smallest UOM if not specified
            $uomId = null;
            if ($uomCode !== '') {
                if (!isset($uoms[$uomCode])) {
                    $uom = UOM::create(['code' => $uomCode, 'name' => $uomCode, 'is_active' => true]);
                    $uoms[$uomCode] = $uom->id;
                }
                $uomId = $uoms[$uomCode];
            } else {
                // No UOM specified - use item's smallest UOM
                $item = Item::with('smallestUom')->find($itemId);
                if ($item && $item->smallestUom) {
                    $uomId = $item->smallestUom->id;
                }
            }

            try {
                $existing = PackageBomItem::where('package_id', $packageId)
                    ->where('item_id', $itemId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'uom_id'   => $uomId ?? $existing->uom_id,
                        'quantity' => $qty,
                        'notes'    => $notes ?: $existing->notes,
                    ]);
                    $results['updated'][] = "{$pkgCode} >> {$itemValue} (qty: {$qty} {$uomCode})";
                } else {
                    PackageBomItem::create([
                        'package_id' => $packageId,
                        'item_id'    => $itemId,
                        'uom_id'     => $uomId,
                        'quantity'   => $qty,
                        'notes'      => $notes ?: null,
                    ]);
                    $results['added'][] = "{$pkgCode} >> {$itemValue} (qty: {$qty} {$uomCode})";
                }
            } catch (\Exception $e) {
                $results['errors'][] = "Row {$rowNum} ({$pkgCode}/{$itemValue}): " . $e->getMessage();
            }
        }

        return view('packages.import_bom_result', compact('results'));
    }

    public function downloadBomTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Entry');

        $headers = ['Package_Code', 'PART_NUMBER', 'Quantity', 'UOM', 'Notes'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '375623']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);

        $examples = [
            ['COAT-S', 'B0006', 30,  'ML',   'Main coating agent'],
            ['COAT-S', 'B0015', 5,   'PCS',  'For application'],
            ['COAT-S', 'B0020', 2,   'ROLL', ''],
            ['COAT-M', 'B0006', 60,  'ML',   ''],
            ['COAT-M', 'B0015', 8,   'PCS',  ''],
        ];
        foreach ($examples as $ri => $row) {
            foreach ($row as $ci => $val) {
                $sheet->setCellValue(chr(65 + $ci) . ($ri + 2), $val);
            }
        }

        $widths = [14, 14, 10, 10, 35];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimension(chr(65 + $i))->setWidth($w);
        }

        // Package reference sheet
        $pkgSheet = $spreadsheet->createSheet();
        $pkgSheet->setTitle('Packages');
        $pkgSheet->setCellValue('A1', 'Package_Code');
        $pkgSheet->setCellValue('B1', 'Package_Name');
        $pkgSheet->setCellValue('C1', 'Category');
        $pkgSheet->getStyle('A1:C1')->applyFromArray($headerStyle);
        $allPkgs = Package::orderBy('category')->orderBy('code')->get();
        foreach ($allPkgs as $i => $pkg) {
            $pkgSheet->setCellValue('A' . ($i + 2), $pkg->code);
            $pkgSheet->setCellValue('B' . ($i + 2), $pkg->name);
            $pkgSheet->setCellValue('C' . ($i + 2), $pkg->category);
        }
        $pkgSheet->getColumnDimension('A')->setWidth(14);
        $pkgSheet->getColumnDimension('B')->setAutoSize(true);
        $pkgSheet->getColumnDimension('C')->setWidth(14);

        // Item reference sheet
        $itemSheet = $spreadsheet->createSheet();
        $itemSheet->setTitle('Items');
        $itemSheet->setCellValue('A1', 'PART_NUMBER');
        $itemSheet->setCellValue('B1', 'Item_Name');
        $itemSheet->setCellValue('C1', 'Smallest_UOM');
        $itemSheet->getStyle('A1:C1')->applyFromArray($headerStyle);
        $allItems = Item::where('is_active', true)->with('smallestUom')->orderBy('code')->get();
        foreach ($allItems as $i => $itm) {
            $itemSheet->setCellValue('A' . ($i + 2), $itm->code);
            $itemSheet->setCellValue('B' . ($i + 2), $itm->name);
            $itemSheet->setCellValue('C' . ($i + 2), $itm->smallestUom?->code ?? '');
        }
        $itemSheet->getColumnDimension('A')->setWidth(15);
        $itemSheet->getColumnDimension('B')->setAutoSize(true);
        $itemSheet->getColumnDimension('C')->setWidth(12);

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $temp   = tempnam(sys_get_temp_dir(), 'bom_template_') . '.xlsx';
        $writer->save($temp);

        return response()->download($temp, 'Package_BOM_Import_Template.xlsx')->deleteFileAfterSend(true);
    }
}
