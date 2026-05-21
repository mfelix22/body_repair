<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Labor;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class LaborController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('labors')) {
            return PermissionHelper::denyAccess('labors', 'view');
        }
        $labors = Labor::orderBy('labor_code')->get();
        return view('labors.index', compact('labors'));
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('labors')) {
            return PermissionHelper::denyAccess('labors', 'create');
        }
        return view('labors.create');
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('labors')) {
            return PermissionHelper::denyAccess('labors', 'create');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        // Auto-generate labor code: LAB-0001
        $last = Labor::orderBy('id', 'desc')->first();
        $nextSeq = $last ? (int) substr($last->labor_code, 4) + 1 : 1;
        $laborCode = 'LAB-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        Labor::create([
            'labor_code'  => $laborCode,
            'description' => $validated['description'],
            'price'       => $validated['price'],
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('labors.index')
            ->with('success', "Labor {$laborCode} created.");
    }

    public function edit(Labor $labor)
    {
        if (!PermissionHelper::canUpdate('labors')) {
            return PermissionHelper::denyAccess('labors', 'update');
        }
        return view('labors.edit', compact('labor'));
    }

    public function update(Request $request, Labor $labor)
    {
        if (!PermissionHelper::canUpdate('labors')) {
            return PermissionHelper::denyAccess('labors', 'update');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $labor->update([
            'description' => $validated['description'],
            'price'       => $validated['price'],
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('labors.index')
            ->with('success', "Labor {$labor->labor_code} updated.");
    }

    public function destroy(Labor $labor)
    {
        if (!PermissionHelper::canDelete('labors')) {
            return PermissionHelper::denyAccess('labors', 'delete');
        }

        $labor->delete();

        return redirect()->route('labors.index')
            ->with('success', 'Labor deleted.');
    }

    public function downloadTemplate()
    {
        if (!PermissionHelper::canCreate('labors')) {
            return PermissionHelper::denyAccess('labors', 'create');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Labor Import');

        // Headers
        $headers = ['cdjob', 'emjob', 'hstd', 'fstd', 'cdjob_o', '0-300jt', '300-500jt', '500-800jt', '800jt-2mil'];
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '1';
            $sheet->setCellValue($cell, $header);
        }

        // Header style — dark background, white bold text
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']]],
        ]);

        // Example row
        $sheet->setCellValue('A2', '102RHR2');
        $sheet->setCellValue('B2', 'Replace + (example name)');
        $sheet->setCellValue('C2', 0.25);
        $sheet->setCellValue('D2', 0);
        $sheet->setCellValue('E2', '');
        $sheet->setCellValue('F2', 189000);
        $sheet->setCellValue('G2', 210000);
        $sheet->setCellValue('H2', 262500);
        $sheet->setCellValue('I2', 288750);

        $sheet->getStyle('A2:I2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F4F8']],
            'font' => ['italic' => true, 'color' => ['rgb' => '888888']],
        ]);

        // Column widths
        $widths = ['A' => 16, 'B' => 40, 'C' => 10, 'D' => 10, 'E' => 14, 'F' => 14, 'G' => 14, 'H' => 14, 'I' => 16];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Note row
        $sheet->setCellValue('A4', 'Notes:');
        $sheet->setCellValue('A5', '- Column A (cdjob): Job code — used as unique key');
        $sheet->setCellValue('A6', '- Column B (emjob): Job name/description');
        $sheet->setCellValue('A7', '- Column C (hstd): Multiplier (e.g. 0.25). Prices = base price x multiplier');
        $sheet->setCellValue('A8', '- Columns D & E (fstd, cdjob_o): optional, ignored on import');
        $sheet->setCellValue('A9', '- Columns F-I: Price per tier (0-300jt, 300-500jt, 500-800jt, 800jt-2mil)');
        $sheet->setCellValue('A10', '- Row 1 (headers) is skipped automatically. Existing codes will be updated.');
        $sheet->getStyle('A4:A10')->getFont()->setItalic(true)->setSize(9);
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $sheet->mergeCells('A4:I4');
        for ($r = 5; $r <= 10; $r++) {
            $sheet->mergeCells("A{$r}:I{$r}");
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'labor_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        if (!PermissionHelper::canCreate('labors')) {
            return PermissionHelper::denyAccess('labors', 'create');
        }

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('excel_file')->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray(null, true, true, true); // keyed by column letter

            $imported = 0;
            $skipped  = 0;
            $errors   = [];

            // Skip row 1 (headers). Row 2 may be base-price reference row — skip if no cdjob.
            foreach (array_slice($rows, 1, null, true) as $rowNum => $row) {
                $cdjob = trim((string) ($row['A'] ?? ''));
                if ($cdjob === '') {
                    continue; // skip header / base-price rows
                }

                $emjob       = trim((string) ($row['B'] ?? ''));
                $hstd        = (float) ($row['C'] ?? 0);
                $price0300   = (float) ($row['F'] ?? 0);
                $price300500 = (float) ($row['G'] ?? 0);
                $price500800 = (float) ($row['H'] ?? 0);
                $price8002000 = (float) ($row['I'] ?? 0);

                if ($emjob === '') {
                    $errors[] = "Row {$rowNum}: labor name (emjob) is empty — skipped.";
                    $skipped++;
                    continue;
                }

                Labor::updateOrCreate(
                    ['labor_code' => $cdjob],
                    [
                        'description'   => $emjob,
                        'multiplier'    => $hstd,
                        'price_0_300'   => $price0300,
                        'price_300_500' => $price300500,
                        'price_500_800' => $price500800,
                        'price_800_2000' => $price8002000,
                        'price'         => $price0300, // default price = tier 1
                        'is_active'     => true,
                    ]
                );
                $imported++;
            }

            $msg = "Import complete: {$imported} labor(s) imported/updated.";
            if ($skipped) {
                $msg .= " {$skipped} row(s) skipped.";
            }

            return redirect()->route('labors.index')->with('success', $msg)
                ->with('import_errors', $errors);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
