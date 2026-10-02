<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WsOmzetReportController extends Controller
{
    private const ROLES = ['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit'];

    public const ACCOUNT_CODES = [
        'C'        => 'Cash',
        'ASURANSI' => 'Asuransi',
        'INT_WS'   => 'Internal WS',
        'INT_W3'   => 'Internal W3',
    ];

    public const STATUSES = [
        'on_progress' => 'On Progress',
        'sent'        => 'Sent',
        'partial'     => 'Partial',
        'paid'        => 'Paid',
        'overdue'     => 'Overdue',
    ];

    private const NUMBER_FORMAT = '#,##0;-#,##0;0';
    private const YELLOW = 'FFFF00';

    /**
     * @return User|null
     */
    private function currentUser(): ?User
    {
        return Auth::user();
    }

    public function index(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);
        $data    = $this->buildData($filters);
        $canViewCogs = PermissionHelper::canViewCOGS();

        return view('reports.ws_omzet', array_merge($data, [
            'filters'      => $filters,
            'canViewCogs'  => $canViewCogs,
            'accountCodes' => self::ACCOUNT_CODES,
            'statuses'     => self::STATUSES,
        ]));
    }

    public function export(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);
        $data    = $this->buildData($filters);
        $period  = ($filters['date_from'] || $filters['date_to'])
            ? ($filters['date_from'] ?? 'awal') . ' s/d ' . ($filters['date_to'] ?? 'sekarang')
            : 'Semua Periode';

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->writeSheet($spreadsheet, 'WS Invoice Summary', 'WS Invoice Summary — ' . $period, [
            'Doc. No.',
            'Doc. Type',
            'Doc. Date',
            'Account No.',
            'Customer Name',
            'Reg No',
            'Reg Date',
            'VIN',
            'Desc',
            'W. Order No',
            'WIP No.',
            'Date In',
            'Labor',
            'Parts',
            'Discount',
            'Stamp Duty',
            'Total',
            'Operator Name',
        ], array_map(fn($r) => [
            $r['doc_no'],
            $r['doc_type'],
            $r['doc_date'],
            $r['account_no'],
            $r['customer'],
            $r['reg_no'],
            $r['reg_date'],
            $r['vin'],
            $r['desc'],
            $r['wo_no'],
            $r['wip_no'],
            $r['date_in'],
            $r['labor'],
            $r['parts'],
            $r['discount'],
            $r['stamp_duty'],
            $r['total'],
            $r['operator'],
        ], $data['summary']), range(13, 17), []);

        if (PermissionHelper::canViewCOGS()) {
            $this->writeSheet($spreadsheet, 'HPP Summary', 'HPP Summary per Invoice — ' . $period, [
                'Doc. No.',
                'Doc. Date',
                'W. Order No',
                'Customer Name',
                'Reg No',
                'Labor',
                'Parts Selling Price',
                'Parts Cost Price (HPP)',
                'Parts Gross Profit',
                'GP %',
                'Discount',
                'Total',
            ], array_map(fn($r) => [
                $r['doc_no'],
                $r['doc_date'],
                $r['wo_no'],
                $r['customer'],
                $r['reg_no'],
                $r['labor'],
                $r['parts'],
                $r['parts_cost'],
                $r['parts_gp'],
                $r['parts_gp_pct'],
                $r['discount'],
                $r['total'],
            ], $data['summary']), [6, 7, 8, 9, 11, 12], [7, 8]);

            $this->writeSheet($spreadsheet, 'HPP (COGS)', 'HPP (COGS) — ' . $period, [
                'Doc. Date',
                'Doc. No.',
                'W. Order No',
                'Part No',
                'Part Name',
                'UOM',
                'Qty',
                'Disc %',
                'Total Selling',
                'Unit Price',
                'Invoice No',
                'Invoice Date',
                'Account No.',
                'Customer Name',
                'Address',
            ], array_map(fn($r) => [
                $r['doc_date'],
                $r['doc_no'],
                $r['wo_no'],
                $r['part_no'],
                $r['part_name'],
                $r['uom'],
                $r['qty'],
                $r['disc_pct'],
                $r['total_selling'],
                $r['cost'],
                $r['invoice_no'],
                $r['invoice_date'],
                $r['account_no'],
                $r['customer'],
                $r['address'],
            ], $data['parts']), [9, 10], [9, 10], [7]);
        }

        $this->writeSheet($spreadsheet, 'Detail Labor', 'Detail Labor — ' . $period, [
            'Doc. No.',
            'Doc. Date',
            'W. Order No',
            'Reg No',
            'Labor Code',
            'Description',
            'Type',
            'Qty',
            'Rate',
            'Total',
        ], array_map(fn($r) => [
            $r['doc_no'],
            $r['doc_date'],
            $r['wo_no'],
            $r['reg_no'],
            $r['labor_code'],
            $r['description'],
            $r['type'],
            $r['qty'],
            $r['rate'],
            $r['total'],
        ], $data['labors']), [9, 10], [], [8], [9]);

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Laporan_Omzet_WS_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // -------------------------------------------------------------------------

    private function filters(Request $request): array
    {
        return [
            'date_from'    => $request->input('date_from') ?: null,
            'date_to'      => $request->input('date_to') ?: null,
            'account_code' => array_key_exists($request->input('account_code'), self::ACCOUNT_CODES) ? $request->input('account_code') : null,
            'status'       => array_key_exists($request->input('status'), self::STATUSES) ? $request->input('status') : null,
            'search'       => trim((string) $request->input('search')),
        ];
    }

    private function buildData(array $filters): array
    {
        $invoices = Invoice::query()
            ->with([
                'customer',
                'creator',
                'workOrder.vehicle',
                'workOrder.labors.labor',
                'workOrder.bonOuts' => fn($q) => $q->where('status', 'completed'),
                'workOrder.bonOuts.items.item.smallestUom',
                'workOrder.bonOuts.items.uom',
            ])
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('work_order_id')
            ->when($filters['date_from'], fn($q, $d) => $q->whereDate('invoice_date', '>=', $d))
            ->when($filters['date_to'], fn($q, $d) => $q->whereDate('invoice_date', '<=', $d))
            ->when($filters['status'], fn($q, $s) => $q->where('status', $s))
            ->when($filters['account_code'], fn($q, $c) => $q->whereHas('workOrder', fn($w) => $w->where('account_code', $c)))
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $term = '%' . $filters['search'] . '%';
                $q->where(function ($q) use ($term) {
                    $q->where('invoice_number', 'like', $term)
                        ->orWhereHas('customer', fn($c) => $c->where('name', 'like', $term)->orWhere('code', 'like', $term))
                        ->orWhereHas('workOrder', fn($w) => $w->where('wo_number', 'like', $term)
                            ->orWhere('vehicle_plate', 'like', $term)
                            ->orWhere('chasis_no', 'like', $term));
                });
            })
            ->orderBy('invoice_date')
            ->orderBy('invoice_number')
            ->get();

        $summary = [];
        $parts   = [];
        $labors  = [];

        foreach ($invoices as $invoice) {
            $wo      = $invoice->workOrder;
            $docNo   = $invoice->invoice_number;
            $docDate = $invoice->invoice_date?->format('d/m/Y');
            $regNo   = $wo->vehicle_plate ?: $wo->vehicle?->plate_number;

            $labor    = (float) $wo->labor_total;
            $partsAmt = (float) $wo->material_total;
            $partsCost = (float) $invoice->cogm_material;
            $discount = (float) $invoice->discount_amount;
            $vat      = (float) ($invoice->tax_amount ?? 0);
            $materai  = (float) $invoice->materai;

            $summary[] = [
                'invoice_id'   => $invoice->id,
                'work_order_id' => $wo->id,
                'doc_no'       => $docNo,
                'doc_type'     => self::ACCOUNT_CODES[$wo->account_code] ?? $wo->account_code,
                'doc_date'     => $docDate,
                'account_no'   => $invoice->customer?->code,
                'customer'     => $invoice->customer?->name,
                'reg_no'       => $regNo,
                'reg_date'     => null,
                'vin'          => $wo->chasis_no ?: $wo->vehicle?->chasis_no,
                'desc'         => trim(($wo->vehicle_merk ?? '') . ' ' . ($wo->vehicle_type_year ?? '')),
                'wo_no'        => $wo->wo_number,
                'wip_no'       => $wo->id,
                'date_in'      => $wo->work_date?->format('d/m/Y'),
                'labor'        => $labor,
                'parts'        => $partsAmt,
                'surcharges'   => 0.0,
                'sublets'      => 0.0,
                'menu'         => 0.0,
                'discount'     => $discount,
                'vat'          => $vat,
                'stamp_duty'   => $materai,
                'total'        => (float) $invoice->grand_total + $vat,
                'operator'     => $invoice->creator?->name,
                'status'       => $invoice->status,
                'parts_cost'   => $partsCost,
                'parts_gp'     => $partsAmt - $partsCost,
                'parts_gp_pct' => $partsAmt > 0 ? round(($partsAmt - $partsCost) / $partsAmt * 100, 2) : 0.0,
            ];

            // Insurance jobs carry a sparepart-specific discount; other jobs use the invoice-wide percentage.
            $partDiscPct = $wo->usesEstimasiDiscount()
                ? (float) $wo->estimasi_discount_percentage_sparepart
                : (float) $invoice->discount_percentage;

            foreach ($wo->bonOuts as $bonOut) {
                foreach ($bonOut->items as $boi) {
                    $qty = (float) $boi->actual_quantity;
                    if ($qty <= 0) {
                        continue;
                    }
                    $price = (float) $boi->unit_price;
                    $parts[] = [
                        'doc_date'      => $bonOut->issued_date?->format('d/m/Y') ?? $docDate,
                        'doc_no'        => $bonOut->bon_out_number,
                        'wo_no'         => $wo->wo_number,
                        'part_no'       => $boi->item?->code,
                        'part_name'     => $boi->item?->name,
                        'uom'           => $boi->uom?->code ?? $boi->item?->smallestUom?->code,
                        'qty'           => $qty,
                        'unit_price'    => $price,
                        'disc_pct'      => $partDiscPct,
                        'total_selling' => round($qty * $price * (1 - $partDiscPct / 100), 2),
                        'cost'          => round($qty * (float) $boi->unit_cost, 2),
                        'invoice_id'    => $invoice->id,
                        'invoice_no'    => $docNo,
                        'invoice_date'  => $docDate,
                        'account_no'    => $invoice->customer?->code,
                        'customer'      => $invoice->customer?->name,
                        'address'       => $invoice->customer?->address,
                    ];
                }
            }

            foreach ($wo->labors as $wl) {
                $labors[] = [
                    'doc_no'      => $docNo,
                    'doc_date'    => $docDate,
                    'wo_no'       => $wo->wo_number,
                    'reg_no'      => $regNo,
                    'labor_code'  => $wl->labor?->labor_code,
                    'description' => $wl->description ?: $wl->labor?->description,
                    'type'        => $wl->is_extra ? 'Extra' : 'Base',
                    'qty'         => (float) ($wl->qty ?? 1),
                    'rate'        => (float) $wl->rate,
                    'total'       => (float) $wl->total_price,
                ];
            }
        }

        $col = fn(string $key) => array_sum(array_column($summary, $key));
        $totals = [
            'count'      => count($summary),
            'labor'      => $col('labor'),
            'parts'      => $col('parts'),
            'discount'   => $col('discount'),
            'vat'        => $col('vat'),
            'stamp_duty' => $col('stamp_duty'),
            'total'      => $col('total'),
            'parts_cost' => $col('parts_cost'),
            'parts_gp'   => $col('parts_gp'),
        ];
        $totals['parts_gp_pct'] = $totals['parts'] > 0 ? round($totals['parts_gp'] / $totals['parts'] * 100, 2) : 0;

        return compact('summary', 'parts', 'labors', 'totals');
    }

    /**
     * @param int[] $moneyCols  1-based columns formatted as Rupiah and summed in the total row
     * @param int[] $yellowCols 1-based columns highlighted yellow (selling / cost price)
     * @param int[] $qtyCols    1-based quantity columns (summed, 2 decimals)
     * @param int[] $unitCols   1-based money columns holding unit prices (formatted but not summed)
     */
    private function writeSheet(Spreadsheet $spreadsheet, string $title, string $heading, array $headers, array $rows, array $moneyCols, array $yellowCols, array $qtyCols = [], array $unitCols = []): Worksheet
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(substr($title, 0, 31));

        $lastCol   = Coordinate::stringFromColumnIndex(count($headers));
        $headerRow = 3;
        $firstData = $headerRow + 1;

        $sheet->setCellValue('A1', $heading);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $sheet->fromArray($headers, null, "A{$headerRow}");
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6A96']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        foreach ($yellowCols as $c) {
            $sheet->getStyle(Coordinate::stringFromColumnIndex($c) . $headerRow)->applyFromArray([
                'font' => ['color' => ['rgb' => '000000']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::YELLOW]],
            ]);
        }

        if ($rows) {
            $sheet->fromArray($rows, null, "A{$firstData}", true);
        }
        $lastData = $firstData + max(count($rows), 1) - 1;
        $totalRow = $lastData + 1;

        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        foreach (array_merge($moneyCols, $qtyCols) as $c) {
            $letter = Coordinate::stringFromColumnIndex($c);
            if (!in_array($c, $unitCols, true)) {
                $sheet->setCellValue("{$letter}{$totalRow}", "=SUM({$letter}{$firstData}:{$letter}{$lastData})");
            }
            $sheet->getStyle("{$letter}{$firstData}:{$letter}{$totalRow}")->getNumberFormat()
                ->setFormatCode(in_array($c, $qtyCols, true) ? '#,##0.00' : self::NUMBER_FORMAT);
        }
        foreach ($yellowCols as $c) {
            $letter = Coordinate::stringFromColumnIndex($c);
            $sheet->getStyle("{$letter}{$firstData}:{$letter}{$lastData}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF99');
        }

        $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E8E8']],
        ]);
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$totalRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range(1, count($headers)) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
        $sheet->freezePane("A{$firstData}");
        $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$lastData}");

        return $sheet;
    }
}
