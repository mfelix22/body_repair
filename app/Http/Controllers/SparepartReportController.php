<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SparepartReportController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit'])) {
            abort(403);
        }

        // Filter options
        $vehicles    = DB::table('work_orders')
            ->whereNotNull('vehicle_plate')
            ->where('vehicle_plate', '!=', '')
            ->orderBy('vehicle_plate')
            ->distinct()
            ->pluck('vehicle_plate');

        $paketCodes  = DB::table('work_orders')
            ->whereNotNull('paket_code')
            ->where('paket_code', '!=', '')
            ->orderBy('paket_code')
            ->distinct()
            ->pluck('paket_code');

        $items = DB::table('items')
            ->where('is_active', true)
            ->orderBy('name')
            ->select('id', 'code', 'name')
            ->get();

        // Build filtered query base
        $query = $this->buildBaseQuery($request);

        // Summary stats
        $totalTransactions = (clone $query)->count();
        $uniqueParts       = (clone $query)->distinct('boi.item_id')->count('boi.item_id');
        $totalCost         = (clone $query)->sum(DB::raw('boi.actual_quantity * COALESCE(boi.unit_cost, 0)'));

        // Summary by Part
        $byPart = (clone $query)
            ->select(
                'i.id as item_id',
                'i.code as item_code',
                'i.name as item_name',
                DB::raw('SUM(boi.actual_quantity) as total_qty'),
                DB::raw('COUNT(boi.id) as usage_count'),
                DB::raw('AVG(boi.unit_cost) as avg_price'),
                DB::raw('SUM(boi.actual_quantity * COALESCE(boi.unit_cost, 0)) as total_cost')
            )
            ->groupBy('i.id', 'i.code', 'i.name')
            ->orderByDesc('total_cost')
            ->get();

        // Summary by Vehicle
        $byVehicle = (clone $query)
            ->select(
                'wo.vehicle_id',
                'wo.vehicle_plate',
                'wo.vehicle_merk',
                DB::raw('SUM(boi.actual_quantity) as total_qty'),
                DB::raw('COUNT(boi.id) as usage_count'),
                DB::raw('SUM(boi.actual_quantity * COALESCE(boi.unit_cost, 0)) as total_cost')
            )
            ->groupBy('wo.id', 'wo.vehicle_id', 'wo.vehicle_plate', 'wo.vehicle_merk')
            ->orderByDesc('total_cost')
            ->get();

        // Detailed transactions
        $detailed = (clone $query)
            ->select(
                'bo.id as bon_out_id',
                'bo.issued_date',
                'bo.bon_out_number',
                'wo.id as work_order_id',
                'wo.wo_number',
                'wo.vehicle_id',
                'wo.vehicle_plate',
                'wo.vehicle_merk',
                'wo.paket_code',
                'i.id as item_id',
                'i.code as item_code',
                'i.name as item_name',
                'boi.actual_quantity',
                'boi.unit_cost',
                DB::raw('boi.actual_quantity * COALESCE(boi.unit_cost, 0) as line_cost')
            )
            ->orderBy('bo.issued_date', 'desc')
            ->orderBy('bo.id', 'desc')
            ->get();

        return view('reports.sparepart', compact(
            'vehicles', 'paketCodes', 'items',
            'totalTransactions', 'uniqueParts', 'totalCost',
            'byPart', 'byVehicle', 'detailed'
        ));
    }

    public function export(Request $request)
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit'])) {
            abort(403);
        }

        $tab   = $request->input('tab', 'by_part');
        $query = $this->buildBaseQuery($request);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // ---- Header style helpers
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6A96']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $cellStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];

        // Title row
        $filterLabel = $this->buildFilterLabel($request);
        $sheet->setCellValue('A1', 'Sparepart Usage Report');
        $sheet->setCellValue('A2', $filterLabel);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $row = 4;

        if ($tab === 'by_part') {
            $headers = ['No', 'Part Code', 'Part Name', 'Total Qty Used', 'Usage Count', 'Avg Price (Rp)', 'Total Cost (Rp)'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
            }
            $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($headerStyle);
            $row++;

            $data = (clone $query)
                ->select('i.code', 'i.name',
                    DB::raw('SUM(boi.actual_quantity) as total_qty'),
                    DB::raw('COUNT(boi.id) as usage_count'),
                    DB::raw('AVG(boi.unit_cost) as avg_price'),
                    DB::raw('SUM(boi.actual_quantity * COALESCE(boi.unit_cost,0)) as total_cost'))
                ->groupBy('i.id', 'i.code', 'i.name')
                ->orderByDesc('total_cost')->get();

            foreach ($data as $n => $r) {
                $cols = [$n + 1, $r->code, $r->name,
                    (float) $r->total_qty, (int) $r->usage_count,
                    (float) $r->avg_price, (float) $r->total_cost];
                foreach ($cols as $col => $val) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
                }
                $sheet->getStyle("A{$row}:G{$row}")->applyFromArray($cellStyle);
                $row++;
            }
            foreach (range(1, 7) as $col) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            }
            $sheet->setTitle('Summary by Part');

        } elseif ($tab === 'by_vehicle') {
            $headers = ['No', 'Vehicle Plate', 'Merk', 'Total Qty Used', 'Usage Count', 'Total Cost (Rp)'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
            }
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($headerStyle);
            $row++;

            $data = (clone $query)
                ->select('wo.vehicle_plate', 'wo.vehicle_merk',
                    DB::raw('SUM(boi.actual_quantity) as total_qty'),
                    DB::raw('COUNT(boi.id) as usage_count'),
                    DB::raw('SUM(boi.actual_quantity * COALESCE(boi.unit_cost,0)) as total_cost'))
                ->groupBy('wo.id', 'wo.vehicle_plate', 'wo.vehicle_merk')
                ->orderByDesc('total_cost')->get();

            foreach ($data as $n => $r) {
                $cols = [$n + 1, $r->vehicle_plate, $r->vehicle_merk,
                    (float) $r->total_qty, (int) $r->usage_count, (float) $r->total_cost];
                foreach ($cols as $col => $val) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
                }
                $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($cellStyle);
                $row++;
            }
            foreach (range(1, 6) as $col) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            }
            $sheet->setTitle('Summary by Vehicle');

        } else {
            // Detailed
            $headers = ['No', 'Date', 'Bon Out #', 'WO #', 'Vehicle', 'Merk', 'Paket Code', 'Part Code', 'Part Name', 'Qty', 'Unit Cost (Rp)', 'Total (Rp)'];
            foreach ($headers as $col => $h) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $h);
            }
            $sheet->getStyle("A{$row}:L{$row}")->applyFromArray($headerStyle);
            $row++;

            $data = (clone $query)
                ->select('bo.issued_date', 'bo.bon_out_number', 'wo.wo_number',
                    'wo.vehicle_plate', 'wo.vehicle_merk', 'wo.paket_code',
                    'i.code as item_code', 'i.name as item_name',
                    'boi.actual_quantity', 'boi.unit_cost',
                    DB::raw('boi.actual_quantity * COALESCE(boi.unit_cost,0) as line_cost'))
                ->orderBy('bo.issued_date', 'desc')->orderBy('bo.id', 'desc')->get();

            foreach ($data as $n => $r) {
                $cols = [$n + 1, $r->issued_date, $r->bon_out_number, $r->wo_number,
                    $r->vehicle_plate, $r->vehicle_merk, $r->paket_code,
                    $r->item_code, $r->item_name,
                    (float) $r->actual_quantity, (float) $r->unit_cost, (float) $r->line_cost];
                foreach ($cols as $col => $val) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($col + 1) . $row, $val);
                }
                $sheet->getStyle("A{$row}:L{$row}")->applyFromArray($cellStyle);
                $row++;
            }
            foreach (range(1, 12) as $col) {
                $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
            }
            $sheet->setTitle('Detailed Transactions');
        }

        $filename = 'sparepart_report_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    // -------------------------------------------------------------------------

    private function buildBaseQuery(Request $request)
    {
        $q = DB::table('bon_out_items as boi')
            ->join('bon_outs as bo', 'bo.id', '=', 'boi.bon_out_id')
            ->join('work_orders as wo', 'wo.id', '=', 'bo.work_order_id')
            ->join('items as i', 'i.id', '=', 'boi.item_id')
            ->where('bo.status', 'completed')
            ->whereNotNull('boi.actual_quantity')
            ->where('boi.actual_quantity', '>', 0);

        if ($request->filled('date_from')) {
            $q->whereDate('bo.issued_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('bo.issued_date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle')) {
            $q->where('wo.vehicle_plate', $request->vehicle);
        }
        if ($request->filled('item_id')) {
            $q->where('boi.item_id', $request->item_id);
        }
        if ($request->filled('paket_code')) {
            $q->where('wo.paket_code', $request->paket_code);
        }

        return $q;
    }

    private function buildFilterLabel(Request $request): string
    {
        $parts = [];
        if ($request->filled('date_from'))  $parts[] = 'From: ' . $request->date_from;
        if ($request->filled('date_to'))    $parts[] = 'To: '   . $request->date_to;
        if ($request->filled('vehicle'))    $parts[] = 'Vehicle: ' . $request->vehicle;
        if ($request->filled('paket_code')) $parts[] = 'Paket: ' . $request->paket_code;
        return $parts ? implode(' | ', $parts) : 'All Data';
    }
}
