<?php

namespace App\Console\Commands;

use App\Models\BonOut;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairWoBilledItems extends Command
{
    protected $signature = 'wo:repair-billed-items
        {--wo= : Limit to a single Work Order (id or wo_number, e.g. 2608/HAS/013)}
        {--apply : Actually write changes (default is a dry run)}';

    protected $description = 'Re-create missing WO billing lines for priced items on completed Bon Outs';

    protected $hidden = true;

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('This is a local-only repair command and is disabled in production.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        $woIds = null;
        if ($woFilter = $this->option('wo')) {
            $woIds = WorkOrder::where('id', $woFilter)->orWhere('wo_number', $woFilter)->pluck('id');
            if ($woIds->isEmpty()) {
                $this->error("Work Order '$woFilter' not found.");
                return self::FAILURE;
            }
        }

        $bonOuts = BonOut::where('status', 'completed')
            ->whereNotNull('work_order_id')
            ->where('bon_out_type', '!=', 3)
            ->when($woIds, fn($q) => $q->whereIn('work_order_id', $woIds))
            ->with(['items.item', 'workOrder'])
            ->get();

        $plan = [];
        foreach ($bonOuts as $bonOut) {
            foreach ($bonOut->items as $boi) {
                if ((float) $boi->actual_quantity <= 0 || (float) $boi->unit_price <= 0) {
                    continue;
                }

                $expectedTotal = round((float) $boi->actual_quantity * (float) $boi->unit_price, 2);
                $action = 'ok';
                $woItem = null;

                if ($boi->work_order_item_id) {
                    $woItem = WorkOrderItem::find($boi->work_order_item_id);
                    if ($woItem) {
                        if ((float) $woItem->total_price == 0) {
                            $action = 'update_price';
                        }
                    } else {
                        // Linked WO line was deleted — recreate it and repoint the link
                        $action = 'recreate';
                    }
                } else {
                    $woItem = WorkOrderItem::where('bon_out_item_id', $boi->id)->first();
                    if (!$woItem) {
                        // Rows created by the old backfill migration have no bon_out_item_id
                        $woItem = WorkOrderItem::where('work_order_id', $bonOut->work_order_id)
                            ->where('item_id', $boi->item_id)
                            ->where('actual_quantity', $boi->actual_quantity)
                            ->where('unit_price', $boi->unit_price)
                            ->where('total_price', $expectedTotal)
                            ->first();
                        $action = $woItem ? 'relink' : 'insert';
                    }
                }

                if ($action === 'ok') {
                    continue;
                }

                $plan[] = [
                    'bonOut'   => $bonOut,
                    'boi'      => $boi,
                    'woItem'   => $woItem,
                    'action'   => $action,
                    'total'    => $expectedTotal,
                ];
            }
        }

        if (empty($plan)) {
            $this->info('Nothing to repair — all priced Bon Out items already have WO billing lines.');
            return self::SUCCESS;
        }

        $this->table(
            ['WO', 'Bon Out', 'Item', 'Qty', 'Unit Price', 'Total', 'Action'],
            array_map(fn($p) => [
                $p['bonOut']->workOrder?->wo_number ?? $p['bonOut']->work_order_id,
                $p['bonOut']->bon_out_number,
                ($p['boi']->item?->code ?? '?') . ' ' . mb_strimwidth((string) ($p['boi']->item?->name ?? ''), 0, 30, '…'),
                number_format((float) $p['boi']->actual_quantity, 2),
                number_format((float) $p['boi']->unit_price, 2),
                number_format($p['total'], 2),
                $p['action'],
            ], $plan)
        );

        if (!$apply) {
            $this->warn('DRY RUN - nothing was changed. Re-run with --apply to commit.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Apply these changes?')) {
            return self::SUCCESS;
        }

        $affectedWos = [];
        DB::transaction(function () use ($plan, &$affectedWos) {
            foreach ($plan as $p) {
                $bonOut = $p['bonOut'];
                $boi    = $p['boi'];

                switch ($p['action']) {
                    case 'update_price':
                        $p['woItem']->update([
                            'unit_price'  => $boi->unit_price,
                            'total_price' => (float) $p['woItem']->actual_quantity * (float) $boi->unit_price,
                        ]);
                        break;

                    case 'relink':
                        $p['woItem']->update(['bon_out_item_id' => $boi->id]);
                        break;

                    case 'insert':
                    case 'recreate':
                        $woItem = WorkOrderItem::create([
                            'work_order_id'   => $bonOut->work_order_id,
                            'bon_out_item_id' => $boi->id,
                            'item_id'         => $boi->item_id,
                            'uom_id'          => $boi->uom_id,
                            'demand_quantity' => (float) $boi->demand_quantity > 0 ? $boi->demand_quantity : $boi->actual_quantity,
                            'actual_quantity' => $boi->actual_quantity,
                            'unit_price'      => $boi->unit_price,
                            'total_price'     => $p['total'],
                            'remark'          => $boi->remark,
                        ]);
                        if ($p['action'] === 'recreate') {
                            $boi->update(['work_order_item_id' => $woItem->id]);
                        }
                        break;
                }

                $affectedWos[$bonOut->work_order_id] = true;
            }
        });

        foreach (array_keys($affectedWos) as $woId) {
            WorkOrder::find($woId)?->calculateTotals();
        }

        $this->info(sprintf('Done. %d lines repaired across %d Work Orders.', count($plan), count($affectedWos)));
        return self::SUCCESS;
    }
}
