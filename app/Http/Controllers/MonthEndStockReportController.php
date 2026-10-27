<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Stock;
use App\Models\StockCostAdjustment;
use App\Models\StockTransaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Month-end stock valuation report.
 *
 * The stocks table only holds CURRENT quantity/avg_cost, so historical month-end
 * positions are reconstructed by replaying the stock_transactions ledger together
 * with stock_cost_adjustments (direct avg_cost overrides logged without a
 * transaction), applying the same weighted-average rule used by
 * Stock::addQuantity() / ReceivableController.
 */
class MonthEndStockReportController extends Controller
{
    private const ROLES = ['super_admin', 'admin', 'director', 'manager', 'accounting', 'audit'];

    private const NUMBER_FORMAT = '#,##0.00;-#,##0.00;0';

    private function currentUser(): ?User
    {
        return Auth::user();
    }

    public function index(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);

        return view('reports.stock_month_end', array_merge($this->buildData($filters), [
            'filters'   => $filters,
            'types'     => Item::getItemTypes(),
            'typeOrder' => array_keys(Item::getItemTypes()),
        ]));
    }

    public function print(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);

        return view('reports.stock_month_end_print', array_merge($this->buildData($filters), [
            'filters' => $filters,
            'types'   => Item::getItemTypes(),
        ]));
    }

    public function export(Request $request)
    {
        abort_unless($this->currentUser()?->hasAnyRole(self::ROLES), 403);

        $filters = $this->filters($request);
        $data    = $this->buildData($filters);

        $spreadsheet = new Spreadsheet();
        $this->writeSummarySheet($spreadsheet->getActiveSheet(), $data, $filters);
        $this->writeDetailSheet($spreadsheet->createSheet(), $data, $filters);

        $filename = 'Month_End_Stock_' . $filters['year'] . '_' . str_pad((string) $filters['month'], 2, '0', STR_PAD_LEFT) . '_' . now()->format('Ymd_His') . '.xlsx';
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
        $now      = now();
        $earliest = StockTransaction::min('created_at');
        $firstYear = $earliest ? (int) Carbon::parse($earliest)->year : (int) $now->year;

        $year = max($firstYear, min((int) $now->year, (int) $request->input('year', $now->year)));

        $lastMonth = $year === (int) $now->year ? (int) $now->month : 12;
        $month     = max(1, min($lastMonth, (int) $request->input('month', $lastMonth)));

        return [
            'year'       => $year,
            'month'      => $month,
            'last_month' => $lastMonth,
            'first_year' => $firstYear,
            'item_type'  => $request->input('item_type') ?: null,
            'search'     => trim((string) $request->input('search', '')) ?: null,
        ];
    }

    /**
     * Replay the ledger once and snapshot every item at each month boundary.
     * Returns matrix (type x month closing values), per-item month detail rows,
     * and headline totals for the selected month.
     */
    private function buildData(array $filters): array
    {
        $year = $filters['year'];
        $now  = now();

        $monthEnds = [];
        for ($m = 1; $m <= 12; $m++) {
            $end = Carbon::create($year, $m, 1)->endOfMonth();
            $monthEnds[$m] = $end->gt($now) ? $now->copy() : $end;
        }
        $horizon   = $monthEnds[12]->copy();
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();

        $txns = StockTransaction::where('created_at', '<=', $horizon)
            ->orderBy('created_at')->orderBy('id')
            ->get(['item_id', 'transaction_type', 'quantity', 'unit_cost', 'balance_after', 'avg_cost_after', 'created_at']);

        $costAdjs = StockCostAdjustment::where('created_at', '<=', $horizon)
            ->orderBy('created_at')->orderBy('id')
            ->get(['item_id', 'new_avg_cost', 'created_at']);

        // Current stock state: fallback cost for balances that were priced
        // directly (import opening_avg_cost / manual initial_cost) with no
        // transaction trail, and implicit opening for items with no history.
        $stockMap = [];
        foreach (Stock::all() as $s) {
            $e        = $stockMap[$s->item_id] ??= ['qty' => 0.0, 'val' => 0.0, 'created_at' => null];
            $e['qty'] += (float) $s->quantity;
            $e['val'] += (float) $s->quantity * (float) $s->avg_cost;
            if ($s->created_at && (!$e['created_at'] || $s->created_at->lt($e['created_at']))) {
                $e['created_at'] = $s->created_at;
            }
        }

        $eventsByItem = [];
        foreach ($txns as $t) {
            $eventsByItem[$t->item_id][] = ['t' => $t->created_at, 'k' => 1, 'id' => $t->id, 'o' => $t];
        }
        foreach ($costAdjs as $a) {
            $eventsByItem[$a->item_id][] = ['t' => $a->created_at, 'k' => 0, 'id' => $a->id, 'o' => $a];
        }
        foreach ($stockMap as $itemId => $sm) {
            if (!isset($eventsByItem[$itemId]) && ($sm['qty'] != 0.0 || $sm['val'] != 0.0)) {
                $eventsByItem[$itemId][] = [
                    't' => $sm['created_at'] ?? $yearStart,
                    'k' => 1,
                    'id' => 0,
                    'o' => (object) [
                        'transaction_type' => 'opening',
                        'quantity'         => $sm['qty'],
                        'unit_cost'        => $sm['qty'] != 0.0 ? $sm['val'] / $sm['qty'] : 0.0,
                        'balance_after'    => $sm['qty'],
                    ],
                ];
            }
        }

        // Applies one event to ($qty, $avg); when $f is given, also accumulates
        // the month's in/out/adjustment/revaluation flows.
        $apply = function (array $e, float &$qty, float &$avg, ?array &$f): void {
            if ($e['k'] === 0) {
                $newAvg = (float) $e['o']->new_avg_cost;
                if ($f !== null) {
                    $f['reval'] += $qty * ($newAvg - $avg);
                }
                $avg = $newAvg;
                return;
            }

            $t    = $e['o'];
            $q    = (float) $t->quantity;
            $bal  = (float) $t->balance_after;
            $cost = $t->unit_cost !== null ? (float) $t->unit_cost : $avg;

            if (in_array($t->transaction_type, ['in', 'opening'], true) && $q > 0) {
                $before = $bal - $q;
                if ($t->unit_cost !== null && $bal > 0) {
                    // stocks.avg_cost is decimal(15,2) — round per step so the
                    // reconstruction ties to the live stock value to the cent.
                    $avg = round((max(0.0, $before) * $avg + $q * $cost) / $bal, 2);
                }
                if ($f !== null) {
                    $f['in_qty'] += $q;
                    $f['in_val'] += $q * $cost;
                }
            } elseif ($t->transaction_type === 'out') {
                if ($f !== null) {
                    $f['out_qty'] += $q;
                    $f['out_val'] += $q * $cost;
                }
            } elseif ($f !== null) {
                $f['adj_qty'] += $q;
                $f['adj_val'] += $q * $cost;
            }

            // avg_cost_after recorded on the ledger row is authoritative; when a
            // pure cost-set transaction (qty 0) changes the average, the value
            // impact is booked as revaluation so the flows still reconcile.
            if ($t->avg_cost_after !== null) {
                $recorded = (float) $t->avg_cost_after;
                if ($f !== null && $t->transaction_type === 'adjustment' && $q == 0.0
                    && abs($recorded - $avg) > 0.000001
                ) {
                    $f['reval'] += $bal * ($recorded - $avg);
                }
                $avg = $recorded;
            }

            $qty = $bal;
        };

        $blankFlow  = ['in_qty' => 0.0, 'in_val' => 0.0, 'out_qty' => 0.0, 'out_val' => 0.0, 'adj_qty' => 0.0, 'adj_val' => 0.0, 'reval' => 0.0];
        $itemsData  = [];

        foreach ($eventsByItem as $itemId => $events) {
            usort($events, fn($a, $b) => [$a['t']->getTimestamp(), $a['k'], $a['id']] <=> [$b['t']->getTimestamp(), $b['k'], $b['id']]);

            $qty = 0.0;
            $avg = 0.0;
            $ei  = 0;
            $n   = count($events);
            $rows = [];

            // Fast-forward everything before the report year — it only
            // contributes to the opening balance of January.
            while ($ei < $n && $events[$ei]['t']->lt($yearStart)) {
                $f = null;
                $apply($events[$ei++], $qty, $avg, $f);
            }

            for ($m = 1; $m <= 12; $m++) {
                $openQty = $qty;
                $openAvg = $avg;
                $f       = $blankFlow;

                while ($ei < $n && $events[$ei]['t']->lte($monthEnds[$m])) {
                    $apply($events[$ei++], $qty, $avg, $f);
                }

                if ($qty > 0 && $avg <= 0 && isset($stockMap[$itemId]) && $stockMap[$itemId]['qty'] != 0.0) {
                    $avg = round($stockMap[$itemId]['val'] / $stockMap[$itemId]['qty'], 2);
                }

                $rows[$m] = [
                    'open_qty'  => $openQty,
                    'open_avg'  => $openAvg,
                    'open_val'  => $openQty * $openAvg,
                    'close_qty' => $qty,
                    'close_avg' => $avg,
                    'close_val' => $qty * $avg,
                ] + $f;
            }

            $itemsData[$itemId] = $rows;
        }

        $items     = Item::with('smallestUom')->get()->keyBy('id');
        $typeOrder = array_flip(array_keys(Item::getItemTypes()));

        // Closing stock value matrix: item type x month.
        $matrix      = [];
        $monthTotals = array_fill(1, 12, 0.0);
        foreach ($itemsData as $itemId => $rows) {
            $type = $items[$itemId]->item_type ?? '-';
            for ($m = 1; $m <= 12; $m++) {
                $matrix[$type][$m] = ($matrix[$type][$m] ?? 0.0) + $rows[$m]['close_val'];
                $monthTotals[$m]  += $rows[$m]['close_val'];
            }
        }
        uksort($matrix, fn($a, $b) => ($typeOrder[$a] ?? 999) <=> ($typeOrder[$b] ?? 999));

        // Per-item detail for the selected month.
        $month  = $filters['month'];
        $detail = [];
        foreach ($itemsData as $itemId => $rows) {
            $r = $rows[$month];

            $hasMovement = $r['in_qty'] != 0.0 || $r['out_qty'] != 0.0 || $r['adj_qty'] != 0.0
                || $r['reval'] != 0.0 || $r['open_val'] != 0.0;
            if ($r['close_qty'] == 0.0 && !$hasMovement) {
                continue;
            }

            $item = $items[$itemId] ?? null;
            $type = $item?->item_type ?? '-';
            if ($filters['item_type'] && $type !== $filters['item_type']) {
                continue;
            }
            if ($filters['search']) {
                $haystack = mb_strtolower(($item->code ?? '') . ' ' . ($item->name ?? '') . ' ' . $itemId);
                if (!str_contains($haystack, mb_strtolower($filters['search']))) {
                    continue;
                }
            }

            $detail[] = [
                'id'      => $itemId,
                'code'    => $item->code ?? '#' . $itemId,
                'name'    => $item->name ?? '(deleted item)',
                'type'    => $type,
                'uom'     => $item?->smallestUom?->code ?? '-',
            ] + $r;
        }

        usort($detail, fn($a, $b) => [($typeOrder[$a['type']] ?? 999), $a['code']] <=> [($typeOrder[$b['type']] ?? 999), $b['code']]);

        $groups = [];
        foreach ($detail as $row) {
            $groups[$row['type']]['rows'][] = $row;
        }
        $valueKeys = ['open_val', 'in_val', 'out_val', 'adj_val', 'reval', 'close_val'];
        foreach ($groups as &$group) {
            $group['totals'] = ['count' => count($group['rows'])];
            foreach ($valueKeys as $k) {
                $group['totals'][$k] = array_sum(array_column($group['rows'], $k));
            }
        }
        unset($group);

        $totals = ['count' => count($detail)];
        foreach ($valueKeys as $k) {
            $totals[$k] = array_sum(array_column($detail, $k));
        }

        // Headline figures (unfiltered) for the selected month.
        $closeTotal  = $monthTotals[$month];
        $prevTotal   = $month === 1
            ? array_sum(array_map(fn($rows) => $rows[1]['open_val'], $itemsData))
            : $monthTotals[$month - 1];
        $inValTotal  = array_sum(array_map(fn($rows) => $rows[$month]['in_val'], $itemsData));
        $outValTotal = array_sum(array_map(fn($rows) => $rows[$month]['out_val'], $itemsData));
        $itemsInStock = count(array_filter($itemsData, fn($rows) => $rows[$month]['close_qty'] > 0));

        return [
            'matrix'       => $matrix,
            'monthTotals'  => $monthTotals,
            'groups'       => $groups,
            'totals'       => $totals,
            'cards'        => [
                'close_total'    => $closeTotal,
                'prev_total'     => $prevTotal,
                'in_val'         => $inValTotal,
                'out_val'        => $outValTotal,
                'items_in_stock' => $itemsInStock,
            ],
            'monthLabel'   => Carbon::create($year, $month, 1)->format('F Y'),
        ];
    }

    // -------------------------------------------------------------------------

    private function writeSummarySheet($sheet, array $data, array $filters): void
    {
        $sheet->setTitle('Monthly Summary');
        $sheet->setCellValue('A1', '*** MONTH-END STOCK VALUE ' . $filters['year'] . ' ***');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A2', 'Closing stock value at end of each month (weighted average cost)');

        $headers = ['Item Type'];
        for ($m = 1; $m <= 12; $m++) {
            $headers[] = Carbon::create($filters['year'], $m, 1)->format('M');
        }
        $sheet->fromArray($headers, null, 'A4');
        $sheet->getStyle('A4:N4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6A96']],
        ]);

        $row = 5;
        $types = Item::getItemTypes();
        foreach ($data['matrix'] as $type => $months) {
            $sheet->setCellValue("A{$row}", ($types[$type] ?? $type) . ' (' . $type . ')');
            for ($m = 1; $m <= 12; $m++) {
                if ($m <= $filters['last_month']) {
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($m + 1) . $row, round($months[$m] ?? 0.0, 2));
                } else {
                    $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($m + 1) . $row, '-');
                }
            }
            $row++;
        }

        $sheet->setCellValue("A{$row}", 'TOTAL');
        for ($m = 1; $m <= 12; $m++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($m + 1);
            if ($m <= $filters['last_month']) {
                $sheet->setCellValue("{$col}{$row}", round($data['monthTotals'][$m], 2));
            } else {
                $sheet->setCellValue("{$col}{$row}", '-');
            }
        }
        $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
            'font'    => ['bold' => true],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9E79F']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN], 'bottom' => ['borderStyle' => Border::BORDER_DOUBLE]],
        ]);

        $sheet->getStyle("B5:N{$row}")->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
        $sheet->getColumnDimension('A')->setWidth(24);
        foreach (range('B', 'N') as $col) {
            $sheet->getColumnDimension($col)->setWidth(16);
        }
        $sheet->freezePane('B5');
    }

    private function writeDetailSheet($sheet, array $data, array $filters): void
    {
        $sheet->setTitle('Detail ' . Carbon::create($filters['year'], $filters['month'], 1)->format('M Y'));
        $sheet->setCellValue('A1', '*** MONTH-END STOCK COST — ' . strtoupper($data['monthLabel']) . ' ***');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        $headers = [
            'Item Code', 'Item Name', 'UOM',
            'Open Qty', 'Open Value',
            'In Qty', 'In Value',
            'Out Qty', 'Out Value',
            'Adj Qty', 'Adj+Reval Value',
            'Close Qty', 'Avg Cost', 'Close Value',
        ];
        $sheet->fromArray($headers, null, 'A3');
        $sheet->getStyle('A3:N3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D6A96']],
        ]);

        $types    = Item::getItemTypes();
        $numCols  = range('D', 'N');
        $row      = 4;
        foreach ($data['groups'] as $type => $group) {
            $sheet->setCellValue("A{$row}", 'Type : ' . $type . ' — ' . ($types[$type] ?? $type));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;

            foreach ($group['rows'] as $r) {
                $sheet->fromArray([
                    $r['code'], $r['name'], $r['uom'],
                    $r['open_qty'], $r['open_val'],
                    $r['in_qty'], $r['in_val'],
                    -$r['out_qty'], -$r['out_val'],
                    $r['adj_qty'], $r['adj_val'] + $r['reval'],
                    $r['close_qty'], $r['close_avg'], $r['close_val'],
                ], null, "A{$row}", true);
                $sheet->getCell("A{$row}")->setValueExplicit((string) $r['code'], DataType::TYPE_STRING);
                $row++;
            }

            $sheet->setCellValue("B{$row}", 'Total ' . $type . ' — ' . ($types[$type] ?? $type) . ' (' . $group['totals']['count'] . ' items)');
            $sheet->setCellValue("E{$row}", round($group['totals']['open_val'], 2));
            $sheet->setCellValue("G{$row}", round($group['totals']['in_val'], 2));
            $sheet->setCellValue("I{$row}", round(-$group['totals']['out_val'], 2));
            $sheet->setCellValue("K{$row}", round($group['totals']['adj_val'] + $group['totals']['reval'], 2));
            $sheet->setCellValue("N{$row}", round($group['totals']['close_val'], 2));
            $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
                'font'    => ['bold' => true],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E8E8']],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $row += 2;
        }

        $sheet->setCellValue("B{$row}", 'GRAND TOTAL (' . $data['totals']['count'] . ' items)');
        $sheet->setCellValue("E{$row}", round($data['totals']['open_val'], 2));
        $sheet->setCellValue("G{$row}", round($data['totals']['in_val'], 2));
        $sheet->setCellValue("I{$row}", round(-$data['totals']['out_val'], 2));
        $sheet->setCellValue("K{$row}", round($data['totals']['adj_val'] + $data['totals']['reval'], 2));
        $sheet->setCellValue("N{$row}", round($data['totals']['close_val'], 2));
        $sheet->getStyle("A{$row}:N{$row}")->applyFromArray([
            'font'    => ['bold' => true],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9E79F']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN], 'bottom' => ['borderStyle' => Border::BORDER_DOUBLE]],
        ]);

        $sheet->getStyle("D4:N{$row}")->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT);
        foreach ($numCols as $col) {
            $sheet->getStyle("{$col}4:{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(36);
        $sheet->getColumnDimension('C')->setWidth(8);
        foreach ($numCols as $col) {
            $sheet->getColumnDimension($col)->setWidth(15);
        }
        $sheet->freezePane('A4');
    }
}
