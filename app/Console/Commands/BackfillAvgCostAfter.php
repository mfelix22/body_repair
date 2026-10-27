<?php

namespace App\Console\Commands;

use App\Models\Stock;
use App\Models\StockCostAdjustment;
use App\Models\StockTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class BackfillAvgCostAfter extends Command
{
    protected $signature = 'stock:backfill-avg-cost
                            {--item= : Only backfill a single item ID}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Replay the stock ledger to populate stock_transactions.avg_cost_after for historical rows';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $itemIds = StockTransaction::query()
            ->when($this->option('item'), fn ($q, $id) => $q->where('item_id', $id))
            ->distinct()
            ->pluck('item_id');

        // Also cover items that only have cost adjustments (no transactions)
        StockCostAdjustment::query()
            ->when($this->option('item'), fn ($q, $id) => $q->where('item_id', $id))
            ->distinct()
            ->pluck('item_id')
            ->each(fn ($id) => $itemIds->push($id));

        $itemIds = $itemIds->unique();

        $this->info("Processing {$itemIds->count()} item(s)" . ($dryRun ? ' [DRY RUN]' : '') . '...');

        $updated = 0;
        $drifts  = [];

        foreach ($itemIds as $itemId) {
            $result = $this->replayItem($itemId, $dryRun);
            $updated += $result['updated'];
            if ($result['drift'] !== null) {
                $drifts[] = $result['drift'];
            }
        }

        $this->info(($dryRun ? 'Would update ' : 'Updated ') . "{$updated} transaction row(s).");

        if ($drifts) {
            $this->warn(count($drifts) . ' item(s) where replayed avg cost differs from current stocks.avg_cost');
            $this->warn('(usually caused by transactions recorded before unit_cost existed — expected for early data):');
            $this->table(
                ['Item ID', 'Replayed Avg', 'Current Avg'],
                array_map(fn ($d) => [$d['item_id'], number_format($d['replayed'], 2), number_format($d['current'], 2)], $drifts)
            );
        }

        return self::SUCCESS;
    }

    /**
     * Replay one item's history and set avg_cost_after on each transaction.
     *
     * When the stock's current avg_cost is known (> 0), a backward pass is used:
     * the moving-average formula is inverted per 'in' transaction, anchored at
     * the current avg — this recovers the true in-effect cost even where old
     * transactions have no unit_cost. Otherwise a forward pass seeded with the
     * earliest known cost anchor is used. Manual cost adjustments
     * (stock_cost_adjustments and COST_ADJUSTMENT transactions) override the
     * average at their timestamp.
     */
    private function replayItem(int $itemId, bool $dryRun): array
    {
        $transactions = StockTransaction::where('item_id', $itemId)
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $adjustments = StockCostAdjustment::where('item_id', $itemId)
            ->orderBy('created_at')->orderBy('id')
            ->get();

        $events = $this->mergeEvents($transactions, $adjustments);

        $stock   = Stock::where('item_id', $itemId)->where('location', 'default')->first();
        $current = $stock ? (float) $stock->avg_cost : null;

        if ($current !== null && $current > 0) {
            $values = $this->backwardPass($events, $current);
            $final  = $current;
        } else {
            $seed   = $this->determineSeed($transactions, $adjustments, $stock);
            $result = $this->forwardPass($events, $seed);
            $values = $result['values'];
            $final  = $result['final'];
        }

        $updated = 0;
        foreach ($transactions as $txn) {
            $newAvg = $values[$txn->id] ?? null;
            if ($newAvg === null) {
                continue;
            }
            if ($txn->avg_cost_after === null || (float) $txn->avg_cost_after !== $newAvg) {
                $updated++;
                if (!$dryRun) {
                    StockTransaction::where('id', $txn->id)
                        ->update(['avg_cost_after' => $newAvg]);
                }
            }
        }

        $drift = ($current !== null && abs($current - $final) > 0.01)
            ? ['item_id' => $itemId, 'replayed' => $final, 'current' => $current]
            : null;

        return ['updated' => $updated, 'drift' => $drift];
    }

    /**
     * Pick the cost basis that applied before the first recorded cost event:
     *  - earliest cost adjustment -> its old_avg_cost (the avg in effect before it)
     *  - earliest costed in/opening transaction -> its unit_cost
     *  - no cost events at all -> the stock's current avg_cost (never recorded as changed)
     */
    private function determineSeed(Collection $transactions, Collection $adjustments, ?Stock $stock): float
    {
        $firstAdj = $adjustments->first();

        $firstCosted = $transactions->first(fn ($t) =>
            in_array($t->transaction_type, [StockTransaction::TYPE_IN, StockTransaction::TYPE_OPENING], true)
            && (float) $t->unit_cost > 0
        );

        if ($firstAdj && $firstCosted) {
            return $firstAdj->created_at <= $firstCosted->created_at
                ? (float) $firstAdj->old_avg_cost
                : (float) $firstCosted->unit_cost;
        }
        if ($firstAdj) {
            return (float) $firstAdj->old_avg_cost;
        }
        if ($firstCosted) {
            return (float) $firstCosted->unit_cost;
        }

        return $stock ? (float) $stock->avg_cost : 0.0;
    }

    /**
     * Merge transactions and cost adjustments into one chronological stream.
     * On identical timestamps, cost adjustments are applied first — so a
     * COST_ADJUSTMENT transaction row records the post-adjustment cost.
     */
    private function mergeEvents(Collection $transactions, Collection $adjustments): array
    {
        $events = [];

        foreach ($adjustments as $a) {
            $events[] = ['kind' => 'adjustment', 'at' => $a->created_at, 'order' => 0, 'id' => $a->id, 'model' => $a];
        }
        foreach ($transactions as $t) {
            $events[] = ['kind' => 'transaction', 'at' => $t->created_at, 'order' => 1, 'id' => $t->id, 'model' => $t];
        }

        usort($events, fn ($a, $b) =>
            [$a['at'], $a['order'], $a['id']] <=> [$b['at'], $b['order'], $b['id']]
        );

        return $events;
    }

    /**
     * A zero-quantity transaction that exists only to set the average cost
     * (written by ItemController::update / adjustCost and import backfills).
     */
    private function isCostSetTransaction($txn): bool
    {
        return $txn->transaction_type === StockTransaction::TYPE_ADJUSTMENT
            && $txn->reference_type === 'COST_ADJUSTMENT'
            && $txn->unit_cost !== null
            && (float) $txn->unit_cost > 0;
    }

    /**
     * Forward replay from a seeded average. Mirrors Stock::addQuantity():
     * costed 'in'/'opening' recalculate the weighted average; other types
     * leave it unchanged. Returns map of transaction id => avg cost after.
     */
    private function forwardPass(array $events, float $seed): array
    {
        $avg    = $seed;
        $values = [];

        foreach ($events as $event) {
            if ($event['kind'] === 'adjustment') {
                $avg = (float) $event['model']->new_avg_cost;
                continue;
            }

            /** @var StockTransaction $txn */
            $txn      = $event['model'];
            $qty      = (float) $txn->quantity;
            $balAfter = (float) $txn->balance_after;
            $unitCost = $txn->unit_cost !== null ? (float) $txn->unit_cost : null;

            if ($this->isCostSetTransaction($txn)) {
                $avg = $unitCost;
            } elseif (in_array($txn->transaction_type, [StockTransaction::TYPE_IN, StockTransaction::TYPE_OPENING], true)
                && $unitCost !== null
                && $unitCost > 0
            ) {
                $qtyBefore = $balAfter - $qty;

                if ($txn->transaction_type === StockTransaction::TYPE_OPENING || $qtyBefore <= 0) {
                    $avg = $unitCost; // opening stock / empty stock sets the cost basis outright
                } elseif ($balAfter > 0 && $qty > 0) {
                    $avg = (($qtyBefore * $avg) + ($qty * $unitCost)) / $balAfter;
                }
            }

            $values[$txn->id] = round($avg, 2);
        }

        return ['values' => $values, 'final' => $avg];
    }

    /**
     * Backward replay anchored at the stock's current avg_cost. For each 'in'
     * transaction the moving-average formula is inverted to recover the prior
     * average; cost adjustments restore their recorded old_avg_cost. Returns
     * map of transaction id => avg cost after.
     */
    private function backwardPass(array $events, float $currentAvg): array
    {
        $avg    = $currentAvg;
        $values = [];

        foreach (array_reverse($events) as $event) {
            if ($event['kind'] === 'adjustment') {
                $avg = (float) $event['model']->old_avg_cost;
                continue;
            }

            /** @var StockTransaction $txn */
            $txn = $event['model'];

            // avg in effect right after this transaction
            $values[$txn->id] = round($avg, 2);

            $qty      = (float) $txn->quantity;
            $balAfter = (float) $txn->balance_after;
            $unitCost = $txn->unit_cost !== null ? (float) $txn->unit_cost : null;

            if ($this->isCostSetTransaction($txn)) {
                // Post-adjustment cost is recorded above; the prior average is
                // unknowable in this direction — carry it through unchanged.
                continue;
            }

            if (in_array($txn->transaction_type, [StockTransaction::TYPE_IN, StockTransaction::TYPE_OPENING], true)
                && $unitCost !== null
                && $unitCost > 0
            ) {
                $qtyBefore = $balAfter - $qty;

                if ($qtyBefore > 0) {
                    // invert: avgBefore = (avgAfter*qtyAfter - qty*unitCost) / qtyBefore
                    $avg = max(0, (($avg * $balAfter) - ($qty * $unitCost)) / $qtyBefore);
                }
                // qtyBefore <= 0: prior stock was empty; prior avg unknowable — carry through
            }
            // out/adjustment/uncosted in: avg passes through unchanged
        }

        return $values;
    }
}
