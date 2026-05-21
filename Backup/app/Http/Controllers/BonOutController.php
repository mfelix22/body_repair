<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\BonOut;
use App\Models\BonOutItem;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\WorkOrder;
use App\Models\AuditLog;
use App\Models\WorkOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BonOutController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'view');
        }
        $bonOuts = BonOut::with(['creator', 'workOrder.customer'])
            ->latest()
            ->paginate(20);

        return view('bon_outs.index', compact('bonOuts'));
    }

    /**
     * Show the form to create a Bon Out for an in-progress Work Order.
     * Pre-fills items from WO items but allows adding new materials.
     * Multiple Bon Outs can be created for the same Work Order (multi-day work).
     */
    public function createFromWO(WorkOrder $workOrder)
    {
        if (!PermissionHelper::canCreate('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'create');
        }

        // Allow creating Bon Out only for in-progress Work Orders
        if ($workOrder->status !== 'in_progress') {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Bon Out can only be created for in-progress Work Orders. Please start the Work Order first.');
        }

        // Load WO data with stock information
        $workOrder->load(['items.item.smallestUom', 'items.item.itemUoms.uom', 'items.item.stocks', 'customer']);

        // Get all items for adding new materials
        $allItems = Item::with(['smallestUom', 'stocks'])->where('is_active', true)->orderBy('name')->get();

        return view('bon_outs.create', compact('workOrder', 'allItems'));
    }

    /**
     * Show the form to create a standalone Bon Out (Stock Adjustment Out / Type 3).
     * No Work Order required — user picks items and quantities.
     */
    public function createStandalone()
    {
        if (!PermissionHelper::canCreate('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'create');
        }

        $items = Item::with(['smallestUom', 'stocks'])
            ->orderBy('name')
            ->get();

        return view('bon_outs.create_standalone', compact('items'));
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'create');
        }

        $isStandalone = !$request->filled('work_order_id');

        if ($isStandalone) {
            return $this->storeStandalone($request);
        }

        return $this->storeFromWO($request);
    }

    /**
     * Store a Bon Out linked to a Work Order (Type 1 or 2).
     * Allows adding new materials not in the original WO.
     * Only saves items with actual_quantity > 0.
     */
    private function storeFromWO(Request $request)
    {
        $validated = $request->validate([
            'work_order_id'              => 'required|exists:work_orders,id',
            'bon_out_type'               => 'required|in:1,3',
            'notes'                      => 'nullable|string',
            'items'                      => 'required|array|min:1',
            'items.*.item_id'            => 'required|exists:items,id',
            'items.*.actual_quantity'    => 'required|numeric|min:0',
            'items.*.work_order_item_id' => 'nullable|exists:work_order_items,id',
            'items.*.unit_price'         => 'nullable|numeric|min:0',
        ]);

        $workOrder = WorkOrder::with('items.item')->findOrFail($validated['work_order_id']);

        if ($workOrder->status !== 'in_progress') {
            return back()->with('error', 'Work Order must be in progress to create a Bon Out.');
        }

        // Filter out items with zero quantity - only save items actually used
        $itemsToSave = array_filter($validated['items'], function($item) {
            return $item['actual_quantity'] > 0;
        });

        if (empty($itemsToSave)) {
            return back()->with('error', 'Please enter at least one item with quantity greater than zero.');
        }

        // Check stock availability for all items
        $stockErrors = [];
        foreach ($itemsToSave as $itemData) {
            $item = Item::with('stocks')->findOrFail($itemData['item_id']);
            $availableStock = $item->stocks->sum('quantity');
            $requestedQty = $itemData['actual_quantity'];

            if ($requestedQty > $availableStock) {
                $stockErrors[] = "{$item->name}: Requested {$requestedQty}, but only {$availableStock} available in stock.";
            }
        }

        if (!empty($stockErrors)) {
            return back()->withInput()->with('error', 'Insufficient stock:<br>' . implode('<br>', $stockErrors));
        }

        // Auto-generate bon out number (category-based like Bon In)
        // Type 1 = Workshop materials, Type 2 = Regular purchase, Type 3 = Stock adjustment
        $bonOutType = (int) $validated['bon_out_type'];
        $lastBonOut = BonOut::where('bon_out_type', $bonOutType)->orderBy('id', 'desc')->first();
        $base = $bonOutType * 100000;
        $lastSeq = $lastBonOut ? (int) $lastBonOut->bon_out_number - $base : 0;
        $nextSeq = $lastSeq + 1;
        $bonOutNumber = (string) ($base + $nextSeq);

        DB::beginTransaction();
        try {
            $bonOut = BonOut::create([
                'work_order_id'  => $workOrder->id,
                'bon_out_number' => $bonOutNumber,
                'bon_out_type'   => $bonOutType,
                'issued_date'    => now()->toDateString(),
                'issued_to'      => $workOrder->customer->name ?? null,
                'purpose'        => "Bon Out for WO {$workOrder->wo_number}",
                'notes'          => $validated['notes'] ?? null,
                'status'         => 'on_progress',
                'created_by'     => auth()->id(),
            ]);

            foreach ($itemsToSave as $itemData) {
                $item = Item::with('smallestUom')->findOrFail($itemData['item_id']);
                
                // Get demand_quantity from WO item if it exists, otherwise 0 (for new materials)
                $demandQuantity = 0;
                if (!empty($itemData['work_order_item_id'])) {
                    $woItem = $workOrder->items->firstWhere('id', $itemData['work_order_item_id']);
                    $demandQuantity = $woItem ? $woItem->demand_quantity : 0;
                }

                // Selling price only applies to extra materials (not WO items)
                $unitPrice = empty($itemData['work_order_item_id'])
                    ? (isset($itemData['unit_price']) && $itemData['unit_price'] > 0 ? $itemData['unit_price'] : null)
                    : null;

                BonOutItem::create([
                    'bon_out_id'          => $bonOut->id,
                    'work_order_item_id'  => $itemData['work_order_item_id'] ?? null,
                    'item_id'             => $item->id,
                    'uom_id'              => $item->smallestUom?->id,
                    'demand_quantity'     => $demandQuantity,
                    'actual_quantity'     => $itemData['actual_quantity'],
                    'unit_price'          => $unitPrice,
                ]);

                // Update WO item actual_quantity if it's from the WO
                if (!empty($itemData['work_order_item_id'])) {
                    $woItem = WorkOrderItem::find($itemData['work_order_item_id']);
                    if ($woItem) {
                        // Accumulate actual quantity (for multi-day bon outs)
                        $currentActual = $woItem->actual_quantity ?? 0;
                        $woItem->update(['actual_quantity' => $currentActual + $itemData['actual_quantity']]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('bon_outs.show', $bonOut)
                ->with('success', 'Bon Out created successfully. Review and complete to deduct stock.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create Bon Out: ' . $e->getMessage());
        }
    }

    /**
     * Store a standalone Bon Out (Type 3 — Stock Adjustment Out).
     * No Work Order required; user selects items and quantities to write off.
     */
    private function storeStandalone(Request $request)
    {
        $validated = $request->validate([
            'bon_out_type'         => 'required|in:2,3',
            'purpose'              => 'required|string|max:255',
            'notes'                => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.item_id'      => 'required|exists:items,id',
            'items.*.quantity'     => 'required|numeric|min:0.01',
        ]);

        // Auto-generate bon out number based on type (200001+ for type 2, 300001+ for type 3)
        $bonOutType = (int) $validated['bon_out_type'];
        $lastBonOut = BonOut::where('bon_out_type', $bonOutType)->orderBy('id', 'desc')->first();
        $base = $bonOutType * 100000;
        $lastSeq = $lastBonOut ? (int) $lastBonOut->bon_out_number - $base : 0;
        $nextSeq = $lastSeq + 1;
        $bonOutNumber = (string) ($base + $nextSeq);

        DB::beginTransaction();
        try {
            $bonOut = BonOut::create([
                'work_order_id'  => null,
                'bon_out_number' => $bonOutNumber,
                'bon_out_type'   => $bonOutType,
                'issued_date'    => now()->toDateString(),
                'issued_to'      => auth()->user()->name,
                'purpose'        => $validated['purpose'],
                'notes'          => $validated['notes'] ?? null,
                'status'         => 'on_progress',
                'created_by'     => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = Item::with('smallestUom')->findOrFail($itemData['item_id']);

                BonOutItem::create([
                    'bon_out_id'      => $bonOut->id,
                    'item_id'         => $item->id,
                    'uom_id'          => $item->smallestUom?->id,
                    'demand_quantity' => $itemData['quantity'],
                    'actual_quantity' => $itemData['quantity'],
                ]);
            }

            DB::commit();

            $typeLabel = $bonOutType === 2 ? 'Regular Purchase Bon Out' : 'Adjustment Bon Out';
            return redirect()->route('bon_outs.show', $bonOut)
                ->with('success', "{$typeLabel} created. Review and complete to deduct stock.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create Bon Out: ' . $e->getMessage());
        }
    }

    public function show(BonOut $bonOut)
    {
        if (!PermissionHelper::canView('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'view');
        }

        $bonOut->load([
            'creator',
            'completer',
            'workOrder.customer',
            'items.item.smallestUom',
            'items.item.itemUoms.uom',
        ]);

        return view('bon_outs.show', compact('bonOut'));
    }

    /**
     * Complete the Bon Out:
     *  - Deduct actual quantities from stock
     *  - No leftover returns (stock wasn't reserved upfront)
     *  - No invoice generation (invoice created when WO is completed)
     */
    public function complete(BonOut $bonOut)
    {
        if (!PermissionHelper::canUpdate('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'update');
        }

        if ($bonOut->status !== 'on_progress') {
            return back()->with('error', 'Only on-progress Bon Out can be completed.');
        }

        $bonOut->load(['items.item.smallestUom', 'workOrder']);

        $isStandalone = $bonOut->bon_out_type == 3;

        DB::beginTransaction();
        try {
            $cogmMaterial = 0.0;

            foreach ($bonOut->items as $bonOutItem) {
                $item      = $bonOutItem->item;
                $actualQty = (float) $bonOutItem->actual_quantity;

                // Get or create stock record
                $stock = Stock::firstOrCreate(
                    ['item_id' => $item->id, 'location' => 'default'],
                    ['quantity' => 0, 'avg_cost' => 0]
                );

                // Capture avg_cost before any change for COGM calculation
                $avgCostAtIssue = (float) $stock->avg_cost;

                // Store unit cost on the bon out item for COGS tracking
                $bonOutItem->update(['unit_cost' => $avgCostAtIssue]);

                if ($actualQty > 0) {
                    // Deduct actual consumed quantity from stock
                    $stock->quantity = max(0, $stock->quantity - $actualQty);
                    $stock->save();

                    $refNotes = $isStandalone
                        ? "Stock adjustment out via Bon Out #{$bonOut->bon_out_number}"
                        : "Issued via Bon Out #{$bonOut->bon_out_number} for WO #{$bonOut->workOrder?->wo_number}";

                    StockTransaction::create([
                        'item_id'          => $item->id,
                        'transaction_type' => 'out',
                        'quantity'         => -$actualQty,
                        'balance_after'    => $stock->quantity,
                        'location'         => 'default',
                        'reference_type'   => $isStandalone ? 'ADJUSTMENT_OUT' : 'BON_OUT',
                        'reference_id'     => $bonOut->id,
                        'notes'            => $refNotes,
                        'created_by'       => auth()->id(),
                    ]);

                    // Accumulate material cost for COGM
                    $cogmMaterial += $actualQty * $avgCostAtIssue;
                }
            }

            // Push extra items with selling price into WO billing
            if (!$isStandalone && $bonOut->work_order_id) {
                $woNeedsRecalc = false;
                foreach ($bonOut->items->where('work_order_item_id', null) as $extraItem) {
                    if ($extraItem->unit_price > 0 && $extraItem->actual_quantity > 0) {
                        WorkOrderItem::create([
                            'work_order_id'   => $bonOut->work_order_id,
                            'item_id'         => $extraItem->item_id,
                            'uom_id'          => $extraItem->uom_id,
                            'demand_quantity' => $extraItem->actual_quantity,
                            'actual_quantity' => $extraItem->actual_quantity,
                            'unit_price'      => $extraItem->unit_price,
                            'total_price'     => $extraItem->actual_quantity * $extraItem->unit_price,
                        ]);
                        $woNeedsRecalc = true;
                    }
                }
                if ($woNeedsRecalc) {
                    $bonOut->workOrder->calculateTotals();
                }
            }

            $bonOut->update([
                'status'       => 'completed',
                'total_cogs'   => $cogmMaterial,
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);

            DB::commit();

            $msg = $isStandalone
                ? 'Adjustment Bon Out completed. Stock has been deducted.'
                : 'Bon Out completed successfully. Stock has been deducted.';

            return redirect()->route('bon_outs.show', $bonOut)
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to complete Bon Out: ' . $e->getMessage());
        }
    }

    public function print(BonOut $bonOut)
    {
        if (!PermissionHelper::canView('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'view');
        }

        $bonOut->load(['workOrder.customer', 'items.item.smallestUom', 'creator']);
        AuditLog::logPrint($bonOut, $bonOut->bon_out_number);
        return view('bon_outs.print', compact('bonOut'));
    }

    public function cancel(BonOut $bonOut)
    {
        if (!PermissionHelper::canUpdate('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'update');
        }

        if ($bonOut->status !== 'on_progress') {
            return back()->with('error', 'Only on-progress Bon Out can be cancelled.');
        }

        $bonOut->update(['status' => 'cancelled']);

        return redirect()->route('bon_outs.show', $bonOut)
            ->with('success', 'Bon Out cancelled.');
    }

    public function destroy(BonOut $bonOut)
    {
        if (!PermissionHelper::canDelete('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'delete');
        }

        if ($bonOut->status === 'completed') {
            return back()->with('error', 'Cannot delete a completed Bon Out.');
        }

        $bonOut->delete();

        return redirect()->route('bon_outs.index')
            ->with('success', 'Bon Out deleted.');
    }
}
