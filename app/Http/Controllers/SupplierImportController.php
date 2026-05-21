<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SupplierImportController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canCreate('suppliers')) {
            return PermissionHelper::denyAccess('suppliers', 'create');
        }

        return view('suppliers.import');
    }

    public function import(Request $request)
    {
        if (!PermissionHelper::canCreate('suppliers')) {
            return PermissionHelper::denyAccess('suppliers', 'create');
        }

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        try {
            $file        = $request->file('excel_file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet   = $spreadsheet->getActiveSheet();
            $rows        = $worksheet->toArray(null, true, false, false);
        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Could not read the file: ' . $e->getMessage()]);
        }

        // Expected header columns (case-insensitive match)
        $colMap = [
            'supplier code'    => 'supplier_code',
            'supplier name'    => 'name',
            'address'          => 'address',
            'ecity'            => 'city',
            'kodepos'          => 'postal_code',
            'phone number'     => 'phone',
            'supplier contact' => 'contact_person',
            'bank'             => 'bank_name',
            'acc_no'           => 'bank_account_no',
            'acc_name'         => 'bank_account_name',
            'npwp'             => 'npwp',
            'email'            => 'email',
            'website'          => 'website',
        ];

        $headerIndexes = [];
        $imported      = 0;
        $updated       = 0;
        $skipped       = 0;
        $errors        = [];

        DB::beginTransaction();

        try {
            foreach ($rows as $rowIndex => $row) {
                // Detect header row dynamically (first row containing "supplier name" or "supplier code")
                if (empty($headerIndexes)) {
                    $normalized = array_map(fn($c) => strtolower(trim((string) $c)), $row);
                    if (in_array('supplier name', $normalized) || in_array('supplier code', $normalized)) {
                        foreach ($normalized as $colIdx => $colName) {
                            if (isset($colMap[$colName])) {
                                $headerIndexes[$colMap[$colName]] = $colIdx;
                            }
                        }
                    }
                    continue; // always skip the header row itself
                }

                // Skip entirely empty rows
                $rowValues = array_filter(array_map('trim', array_map('strval', $row)));
                if (empty($rowValues)) {
                    continue;
                }

                $get = fn(string $field) => isset($headerIndexes[$field])
                    ? trim((string) ($row[$headerIndexes[$field]] ?? ''))
                    : '';

                $supplierName = $get('name');
                $supplierCode = $get('supplier_code');

                if (empty($supplierName)) {
                    $errors[] = "Row " . ($rowIndex + 1) . ": Supplier name is empty — skipped.";
                    $skipped++;
                    continue;
                }

                $data = [
                    'supplier_code'     => $supplierCode ?: null,
                    'name'              => $supplierName,
                    'contact_person'    => $get('contact_person') ?: null,
                    'email'             => $get('email') ?: null,
                    'phone'             => $get('phone') ?: null,
                    'address'           => $get('address') ?: null,
                    'city'              => $get('city') ?: null,
                    'postal_code'       => $get('postal_code') ?: null,
                    'bank_name'         => $get('bank_name') ?: null,
                    'bank_account_no'   => $get('bank_account_no') ?: null,
                    'bank_account_name' => $get('bank_account_name') ?: null,
                    'npwp'              => $get('npwp') ?: null,
                    'website'           => $get('website') ?: null,
                ];

                // Match by supplier_code first, then by name
                $existing = null;
                if (!empty($supplierCode)) {
                    $existing = Supplier::where('supplier_code', $supplierCode)->first();
                }
                if (!$existing) {
                    $existing = Supplier::where('name', $supplierName)->first();
                }

                if ($existing) {
                    $existing->update($data);
                    $updated++;
                } else {
                    Supplier::create($data);
                    $imported++;
                }
            }

            if (empty($headerIndexes)) {
                DB::rollBack();
                return back()->withErrors(['excel_file' => 'Could not detect header row. Make sure the file has a header row with "SUPPLIER CODE" and "SUPPLIER NAME" columns.']);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['excel_file' => 'Import failed: ' . $e->getMessage()]);
        }

        $summary = "Import complete: {$imported} added, {$updated} updated, {$skipped} skipped.";

        if (!empty($errors)) {
            return redirect()->route('suppliers.index')
                ->with('success', $summary)
                ->with('import_warnings', $errors);
        }

        return redirect()->route('suppliers.index')->with('success', $summary);
    }
}
