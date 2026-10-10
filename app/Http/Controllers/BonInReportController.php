<?php

namespace App\Http\Controllers;

use App\Models\Receivable;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BonInReportController extends Controller
{
    private const ROLES = ['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit'];

    public const BON_IN_TYPES = [
        1 => 'Restocking for Storage',
        2 => 'Customer Specific Item',
        3 => 'Adjustment In',
    ];

    private const NUMBER_FORMAT = '#,##0.00;-#,##0.00;0';

    private function currentUser(): ?User
    {
        return Auth::user();
    }

    public function index(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);

        return view('reports.bon_in', array_merge($this->buildData($filters), [
            'filters'   => $filters,
            'period'    => $this->periodLabel($filters),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'supplier_code', 'name']),
            'types'     => self::BON_IN_TYPES,
        ]));
    }

    public function print(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);

        return view('reports.bon_in_print', array_merge($this->buildData($filters), [
            'filters' => $filters,
            'period'  => $this->periodLabel($filters),
        ]));
    }

    public function export(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);
        $data    = $this->buildData($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Bon In Listing');

        $sheet->setCellValue('A1', '*** BON IN LISTING ***');
        $sheet->setCellValue('A2', $this->periodLabel($filters));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $headers = ['Type', 'Bon In', 'Date', 'Order', 'Value', 'Tax', 'Total', 'Cost Value', 'Note'];
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:I4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6A96']],
        ]);

        $row = 5;
        foreach ($data['groups'] as $group) {
            $sheet->setCellValue("A{$row}", "Vendor : {$group['code']} - {$group['name']}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            foreach ($group['rows'] as $r) {
                $sheet->fromArray([
                    $r['type'],
                    $r['bon_in'],
                    $r['date'],
                    $r['order'],
                    $r['value'],
                    $r['tax'],
                    $r['total'],
                    $r['cost_value'],
                    $r['note'],
                ], null, "A{$row}", true);
                $sheet->getCell("B{$row}")->setValueExplicit((string) $r['bon_in'], DataType::TYPE_STRING);
                $row++;
            }

            $this->writeTotalRow($sheet, $row, "Total {$group['code']} - {$group['name']}", $group['totals'], 'E5E8E8');
            $row += 2;
        }

        $this->writeTotalRow($sheet, $row, 'GRAND TOTAL', $data['totals'], 'F9E79F');

        $sheet->getStyle("E5:H{$row}")->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A5');

        $filename = 'Bon_In_Listing_' . now()->format('Ymd_His') . '.xlsx';
        $writer   = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    // -------------------------------------------------------------------------

    private function writeTotalRow($sheet, int $row, string $label, array $totals, string $color): void
    {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("E{$row}", $totals['value']);
        $sheet->setCellValue("F{$row}", $totals['tax']);
        $sheet->setCellValue("G{$row}", $totals['total']);
        $sheet->setCellValue("H{$row}", $totals['cost_value']);
        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
            'font'    => ['bold' => true],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN], 'bottom' => ['borderStyle' => Border::BORDER_DOUBLE]],
        ]);
    }

    private function periodLabel(array $filters): string
    {
        $fmt = fn($d) => Carbon::parse($d)->format('d/m/Y');

        return $fmt($filters['date_from']) . ' - ' . $fmt($filters['date_to']);
    }

    private function filters(Request $request): array
    {
        $type = (int) $request->input('bon_in_type');

        return [
            'date_from'   => $request->input('date_from') ?: now()->startOfMonth()->toDateString(),
            'date_to'     => $request->input('date_to') ?: now()->endOfMonth()->toDateString(),
            'supplier_id' => $request->input('supplier_id') ? (int) $request->input('supplier_id') : null,
            'bon_in_type' => array_key_exists($type, self::BON_IN_TYPES) ? $type : null,
        ];
    }

    /**
     * Value per Bon In follows the same costing rules as ReceivableController::complete():
     * PO-based receipts use the PO line net price (total_price / quantity) plus PPN when applicable,
     * standalone receipts (Adjustment In) are valued at the avg cost booked in the stock transaction.
     */
    private function buildData(array $filters): array
    {
        $receivables = Receivable::query()
            ->with(['purchaseOrder.supplier', 'purchaseOrder.details', 'supplier', 'items'])
            ->where('status', 'completed')
            ->whereDate('received_date', '>=', $filters['date_from'])
            ->whereDate('received_date', '<=', $filters['date_to'])
            ->when($filters['bon_in_type'], fn($q, $t) => $q->where('bon_in_type', $t))
            ->when($filters['supplier_id'], fn($q, $id) => $q->where(fn($q) => $q
                ->where('supplier_id', $id)
                ->orWhereHas('purchaseOrder', fn($p) => $p->where('supplier_id', $id))))
            ->orderBy('received_date')
            ->orderBy('receive_number')
            ->get();

        $standaloneCosts = StockTransaction::query()
            ->where('reference_type', 'BON_IN')
            ->whereIn('reference_id', $receivables->whereNull('purchase_order_id')->pluck('id'))
            ->selectRaw('reference_id, SUM(quantity * unit_cost) as value')
            ->groupBy('reference_id')
            ->pluck('value', 'reference_id');

        $groups = [];
        foreach ($receivables as $receivable) {
            $po    = $receivable->purchaseOrder;
            $hasPO = $po && $po->id;

            if ($hasPO) {
                $supplier = $po->supplier;
                $code     = $supplier?->supplier_code ?: '-';
                $name     = $supplier?->name ?? ($po->supplier_name ?: '-');
            } elseif ($receivable->supplier || $receivable->supplier_name) {
                $code = $receivable->supplier?->supplier_code ?: '-';
                $name = $receivable->supplier?->name ?? $receivable->supplier_name;
            } else {
                $code = 'ADJ';
                $name = 'ADJUSTMENT IN (NO VENDOR)';
            }

            if ($hasPO) {
                $details = $po->details->keyBy(fn($d) => $d->item_id . '-' . $d->uom_id);
                $value = 0.0;
                foreach ($receivable->items as $ri) {
                    $detail = $details->get($ri->item_id . '-' . $ri->uom_id);
                    $unit = $detail && (float) $detail->quantity > 0
                        ? (float) $detail->total_price / (float) $detail->quantity
                        : (float) ($detail->unit_price ?? $ri->unit_price);
                    $value += (float) $ri->quantity_received * $unit;
                }
                $value = round($value, 2);
                $tax   = ($po->include_ppn && $po->po_type === 'purchase_order') ? round($value * 0.11, 2) : 0.0;
            } else {
                $value = round((float) ($standaloneCosts[$receivable->id] ?? 0), 2);
                $tax   = 0.0;
            }

            $key = $code . '|' . $name;
            $groups[$key] ??= ['code' => $code, 'name' => $name, 'rows' => []];
            $groups[$key]['rows'][] = [
                'id'         => $receivable->id,
                'type'       => $receivable->bon_in_type,
                'bon_in'     => $receivable->receive_number,
                'date'       => $receivable->received_date?->format('d/m/Y'),
                'order'      => $hasPO ? $po->po_number : null,
                'po_id'      => $hasPO ? $po->id : null,
                'value'      => $value,
                'tax'        => $tax,
                'total'      => $value + $tax,
                'cost_value' => $value + $tax,
                'note'       => $receivable->notes,
            ];
        }

        $sum = fn(array $rows) => [
            'count'      => count($rows),
            'value'      => array_sum(array_column($rows, 'value')),
            'tax'        => array_sum(array_column($rows, 'tax')),
            'total'      => array_sum(array_column($rows, 'total')),
            'cost_value' => array_sum(array_column($rows, 'cost_value')),
        ];

        ksort($groups);
        $allRows = [];
        foreach ($groups as &$group) {
            $group['totals'] = $sum($group['rows']);
            array_push($allRows, ...$group['rows']);
        }
        unset($group);

        return ['groups' => array_values($groups), 'totals' => $sum($allRows)];
    }
}
