<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Panel;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PanelController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('panels')) {
            return PermissionHelper::denyAccess('panels', 'view');
        }
        $panels = Panel::orderBy('panel_code')->get();
        return view('panels.index', compact('panels'));
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('panels')) {
            return PermissionHelper::denyAccess('panels', 'create');
        }
        return view('panels.create');
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('panels')) {
            return PermissionHelper::denyAccess('panels', 'create');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'price_0_300'   => 'nullable|numeric|min:0',
            'price_300_500' => 'nullable|numeric|min:0',
            'price_500_800' => 'nullable|numeric|min:0',
            'price_800_2000' => 'nullable|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        // Auto-generate panel code: PNL-0001
        $last = Panel::orderBy('id', 'desc')->first();
        $nextSeq = $last ? (int) substr($last->panel_code, 4) + 1 : 1;
        $panelCode = 'PNL-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        Panel::create([
            'panel_code'     => $panelCode,
            'description'    => $validated['description'],
            'price'          => $validated['price'],
            'price_0_300'    => $validated['price_0_300'] ?? $validated['price'],
            'price_300_500'  => $validated['price_300_500'] ?? $validated['price'],
            'price_500_800'  => $validated['price_500_800'] ?? $validated['price'],
            'price_800_2000' => $validated['price_800_2000'] ?? $validated['price'],
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return redirect()->route('panels.index')
            ->with('success', "Panel {$panelCode} created.");
    }

    public function edit(Panel $panel)
    {
        if (!PermissionHelper::canUpdate('panels')) {
            return PermissionHelper::denyAccess('panels', 'update');
        }
        return view('panels.edit', compact('panel'));
    }

    public function update(Request $request, Panel $panel)
    {
        if (!PermissionHelper::canUpdate('panels')) {
            return PermissionHelper::denyAccess('panels', 'update');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'price_0_300'   => 'nullable|numeric|min:0',
            'price_300_500' => 'nullable|numeric|min:0',
            'price_500_800' => 'nullable|numeric|min:0',
            'price_800_2000' => 'nullable|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $panel->update([
            'description'    => $validated['description'],
            'price'          => $validated['price'],
            'price_0_300'    => $validated['price_0_300'] ?? $validated['price'],
            'price_300_500'  => $validated['price_300_500'] ?? $validated['price'],
            'price_500_800'  => $validated['price_500_800'] ?? $validated['price'],
            'price_800_2000' => $validated['price_800_2000'] ?? $validated['price'],
            'is_active'      => $request->boolean('is_active', true),
        ]);

        return redirect()->route('panels.index')
            ->with('success', "Panel {$panel->panel_code} updated.");
    }

    public function destroy(Panel $panel)
    {
        if (!PermissionHelper::canDelete('panels')) {
            return PermissionHelper::denyAccess('panels', 'delete');
        }

        $panel->delete();

        return redirect()->route('panels.index')
            ->with('success', 'Panel deleted.');
    }

    public function downloadTemplate()
    {
        if (!PermissionHelper::canCreate('panels')) {
            return PermissionHelper::denyAccess('panels', 'create');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Panel Import');

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
        $sheet->setCellValue('A2', 'PNL-9999');
        $sheet->setCellValue('B2', 'Panel example name');
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
        $sheet->setCellValue('A5', '- Column A (cdjob): Panel code — used as unique key');
        $sheet->setCellValue('A6', '- Column B (emjob): Panel name/description');
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
        }, 'panel_import_template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request)
    {
        if (!PermissionHelper::canCreate('panels')) {
            return PermissionHelper::denyAccess('panels', 'create');
        }

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('excel_file')->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();
            $rows        = $sheet->toArray(null, true, true, true);

            $imported = 0;
            $skipped  = 0;
            $errors   = [];

            foreach (array_slice($rows, 1, null, true) as $rowNum => $row) {
                $cdjob = trim((string) ($row['A'] ?? ''));
                if ($cdjob === '') {
                    continue;
                }

                $emjob       = trim((string) ($row['B'] ?? ''));
                $hstd        = (float) ($row['C'] ?? 0);
                $price0300   = (float) ($row['F'] ?? 0);
                $price300500 = (float) ($row['G'] ?? 0);
                $price500800 = (float) ($row['H'] ?? 0);
                $price8002000 = (float) ($row['I'] ?? 0);

                if ($emjob === '') {
                    $errors[] = "Row {$rowNum}: panel name (emjob) is empty — skipped.";
                    $skipped++;
                    continue;
                }

                Panel::updateOrCreate(
                    ['panel_code' => $cdjob],
                    [
                        'description'    => $emjob,
                        'multiplier'     => $hstd,
                        'price_0_300'    => $price0300,
                        'price_300_500'  => $price300500,
                        'price_500_800'  => $price500800,
                        'price_800_2000' => $price8002000,
                        'price'          => $price0300,
                        'is_active'      => true,
                    ]
                );
                $imported++;
            }

            $msg = "Import complete: {$imported} panel(s) imported/updated.";
            if ($skipped) {
                $msg .= " {$skipped} row(s) skipped.";
            }

            return redirect()->route('panels.index')->with('success', $msg)
                ->with('import_errors', $errors);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
