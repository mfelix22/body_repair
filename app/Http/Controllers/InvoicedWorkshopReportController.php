<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InvoicedWorkshopReportController extends Controller
{
    private const ROLES = ['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit'];

    private const NUMBER_FORMAT = '#,##0;-#,##0;0';

    private const MONEY_KEYS = ['labour', 'sparepart', 'discount', 'amount', 'tax', 'stamp', 'total'];

    private function currentUser(): ?User
    {
        return Auth::user();
    }

    public function index(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);

        return view('reports.invoiced_workshop', array_merge($this->buildData($filters), [
            'filters'      => $filters,
            'period'       => $this->periodLabel($filters),
            'accountCodes' => WsOmzetReportController::ACCOUNT_CODES,
        ]));
    }

    public function print(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);

        return view('reports.invoiced_workshop_print', array_merge($this->buildData($filters), [
            'filters'  => $filters,
            'period'   => $this->periodLabel($filters),
            'jobLabel' => $this->jobLabel($filters),
        ]));
    }

    public function export(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);
        $data    = $this->buildData($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Invoiced Workshop Detail');

        $sheet->setCellValue('A1', '*** INVOICED WORKSHOP DETAIL ***');
        $sheet->setCellValue('A2', 'From ' . $this->periodLabel($filters));
        $sheet->setCellValue('A3', $this->jobLabel($filters));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $headers = ['No.', 'Date', 'Inv-No', 'WIP-No', 'Customer', 'Labour', 'Sparepart', 'Discount', 'Amount', 'Tax', 'Stamp', 'Total', 'Job-No'];
        $sheet->fromArray($headers, null, 'A5');
        $sheet->getStyle('A5:M5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6A96']],
        ]);

        $row = 6;
        foreach ($data['groups'] as $group) {
            $sheet->setCellValue("A{$row}", "ACCOUNT : {$group['label']}");
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            foreach ($group['rows'] as $i => $r) {
                $sheet->fromArray([
                    $i + 1,
                    $r['date'],
                    $r['inv_no'],
                    $r['wip_no'],
                    $r['customer'],
                    $r['labour'],
                    $r['sparepart'],
                    $r['discount'],
                    $r['amount'],
                    $r['tax'],
                    $r['stamp'],
                    $r['total'],
                    $r['job_no'],
                ], null, "A{$row}", true);
                $sheet->getCell("C{$row}")->setValueExplicit((string) $r['inv_no'], DataType::TYPE_STRING);
                $sheet->getCell("M{$row}")->setValueExplicit((string) $r['job_no'], DataType::TYPE_STRING);
                $row++;
            }

            $this->writeTotalRow($sheet, $row, "SUB TOTAL  {$group['label']}", $group['totals'], 'E5E8E8');
            $row += 2;
        }

        $this->writeTotalRow($sheet, $row, 'GRAND TOTAL', $data['totals'], 'F9E79F');

        $sheet->getStyle("F6:L{$row}")->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A6');

        $filename = 'Invoiced_Workshop_Detail_' . now()->format('Ymd_His') . '.xlsx';
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
        $sheet->setCellValue("E{$row}", $label);
        foreach (array_combine(range('F', 'L'), self::MONEY_KEYS) as $col => $key) {
            $sheet->setCellValue("{$col}{$row}", $totals[$key]);
        }
        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
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

    private function jobLabel(array $filters): string
    {
        return $filters['account_code']
            ? WsOmzetReportController::ACCOUNT_CODES[$filters['account_code']] . ' Job Type Transaction'
            : 'All Job Type Transaction';
    }

    private function filters(Request $request): array
    {
        $code = $request->input('account_code');

        return [
            'date_from'    => $request->input('date_from') ?: now()->startOfMonth()->toDateString(),
            'date_to'      => $request->input('date_to') ?: now()->endOfMonth()->toDateString(),
            'account_code' => array_key_exists($code, WsOmzetReportController::ACCOUNT_CODES) ? $code : null,
        ];
    }

    /**
     * Every WO invoice issued in the period is listed (including ones cancelled later), and every
     * credit note issued in the period is listed as a negative row, so a cancelled invoice and its
     * credit note net to zero — matching the DMS "Invoiced Workshop Detail" layout.
     */
    private function buildData(array $filters): array
    {
        $byAccount = fn($q) => $q->whereHas('workOrder', fn($w) => $w->where('account_code', $filters['account_code']));

        $invoices = Invoice::query()
            ->with(['customer', 'workOrder'])
            ->whereNotNull('work_order_id')
            ->whereDate('invoice_date', '>=', $filters['date_from'])
            ->whereDate('invoice_date', '<=', $filters['date_to'])
            ->when($filters['account_code'], $byAccount)
            ->get();

        $creditNotes = CreditNote::query()
            ->with(['customer', 'workOrder', 'invoice'])
            ->whereNotNull('work_order_id')
            ->whereDate('credit_note_date', '>=', $filters['date_from'])
            ->whereDate('credit_note_date', '<=', $filters['date_to'])
            ->when($filters['account_code'], $byAccount)
            ->get();

        $rows = [];
        foreach ($invoices as $invoice) {
            $rows[] = $this->makeRow('invoice', $invoice->id, $invoice->invoice_number, $invoice->invoice_date, $invoice, $invoice, 1);
        }
        foreach ($creditNotes as $cn) {
            $rows[] = $this->makeRow('credit_note', $cn->id, $cn->credit_note_number, $cn->credit_note_date, $cn, $cn->invoice, -1);
        }

        usort($rows, fn($a, $b) => [$a['sort_date'], $a['inv_no']] <=> [$b['sort_date'], $b['inv_no']]);

        $order  = array_keys(WsOmzetReportController::ACCOUNT_CODES);
        $groups = [];
        foreach ($rows as $r) {
            $groups[$r['account_code']] ??= [
                'code'  => $r['account_code'],
                'label' => WsOmzetReportController::ACCOUNT_CODES[$r['account_code']] ?? ($r['account_code'] ?: 'Tanpa Account'),
                'rows'  => [],
            ];
            $groups[$r['account_code']]['rows'][] = $r;
        }
        uksort($groups, function ($a, $b) use ($order) {
            $ia = array_search($a, $order, true);
            $ib = array_search($b, $order, true);
            return [$ia === false ? PHP_INT_MAX : $ia, $a] <=> [$ib === false ? PHP_INT_MAX : $ib, $b];
        });

        $sum = function (array $rows) {
            $totals = ['count' => count($rows)];
            foreach (self::MONEY_KEYS as $key) {
                $totals[$key] = array_sum(array_column($rows, $key));
            }
            return $totals;
        };

        foreach ($groups as &$group) {
            $group['totals'] = $sum($group['rows']);
        }
        unset($group);

        return ['groups' => array_values($groups), 'totals' => $sum($rows)];
    }

    /**
     * @param Invoice|CreditNote $doc     document carrying the amounts (subtotal / discount / grand total)
     * @param Invoice|null       $invoice source invoice (for tax, which is only stored on invoices)
     * @param int                $sign    1 for invoices, -1 for credit notes
     */
    private function makeRow(string $type, int $id, string $number, ?Carbon $date, $doc, ?Invoice $invoice, int $sign): array
    {
        $wo        = $doc->workOrder;
        $labour    = (float) ($wo?->labor_total ?? 0);
        $sparepart = (float) ($wo?->material_total ?? 0);
        $discount  = (float) $doc->discount_amount;
        $amount    = (float) $doc->subtotal - $discount;
        $stamp     = (float) ($invoice?->materai ?? 0);
        $tax       = (float) ($invoice?->tax_amount ?? 0);

        return [
            'type'         => $type,
            'id'           => $id,
            'account_code' => (string) ($wo?->account_code ?? ''),
            'sort_date'    => $date?->toDateString(),
            'date'         => $date?->format('d/m/Y'),
            'inv_no'       => $number,
            'wip_no'       => $wo?->id,
            'work_order_id' => $wo?->id,
            'customer'     => $doc->customer?->name,
            'cancelled'    => $type === 'invoice' && $doc->status === 'cancelled',
            'labour'       => $sign * $labour,
            'sparepart'    => $sign * $sparepart,
            'discount'     => $sign * $discount,
            'amount'       => $sign * $amount,
            'tax'          => $sign * $tax,
            'stamp'        => $sign * $stamp,
            'total'        => $sign * ($amount + $tax + $stamp),
            'job_no'       => $wo?->wo_number,
        ];
    }
}
