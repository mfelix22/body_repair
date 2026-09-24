<?php

namespace App\Http\Controllers;

use App\Helpers\ExcelExporter;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BonOutController extends Controller
{
    private const STATUSES = [
        'on_progress' => 'On Progress',
        'completed'   => 'Completed',
        'cancelled'   => 'Cancelled',
    ];

    public function index(Request $request)
    {
        if (!PermissionHelper::canView('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'view');
        }

        $month  = $request->input('month');
        $year   = $request->input('year', date('Y'));
        $status = $request->input('status');

        $query = BonOut::with(['creator', 'workOrder.customer'])
            ->orderBy('issued_date', 'desc')
            ->orderBy('id', 'desc');

        if ($month) {
            $query->whereMonth('issued_date', (int) $month);
        }
        if ($year) {
            $query->whereYear('issued_date', (int) $year);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $bonOuts = $query->get();

        $allYears  = BonOut::selectRaw('YEAR(issued_date) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');
        $statuses = self::STATUSES;

        return view('bon_outs.index', compact('bonOuts', 'month', 'year', 'status', 'allYears', 'statuses'));
    }

    public function exportExcel(Request $request)
    {
        if (!PermissionHelper::canView('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'view');
        }

        $month  = $request->input('month');
        $year   = $request->input('year', date('Y'));
        $status = $request->input('status');

        $query = BonOut::with(['creator', 'workOrder.customer'])
            ->orderBy('issued_date', 'desc')
            ->orderBy('id', 'desc');

        if ($month) {
            $query->whereMonth('issued_date', (int) $month);
        }
        if ($year) {
            $query->whereYear('issued_date', (int) $year);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $bonOuts = $query->get();

        $typeLabels = [1 => 'Workshop', 2 => 'Regular', 3 => 'Adjustment'];

        $rows = [];
        foreach ($bonOuts as $bonOut) {
            $rows[] = [
                $bonOut->bon_out_number,
                $typeLabels[$bonOut->bon_out_type] ?? '-',
                $bonOut->issued_date->format('Y-m-d'),
                $bonOut->workOrder->wo_number ?? '-',
                $bonOut->workOrder->customer->name ?? '-',
                $bonOut->workOrder->vehicle_plate ?? '-',
                self::STATUSES[$bonOut->status] ?? ucwords(str_replace('_', ' ', $bonOut->status)),
                $bonOut->creator->name ?? '-',
            ];
        }

        return ExcelExporter::download(
            'Bon Out',
            ['Bon Out #', 'Type', 'Date', 'Work Order', 'Customer', 'Vehicle', 'Status', 'Created By'],
            $rows,
            ['A' => 20, 'B' => 12, 'C' => 12, 'D' => 16, 'E' => 28, 'F' => 12, 'G' => 14, 'H' => 18],
            'Bon-Out-' . now()->format('Ymd') . '.xlsx'
        );
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
            'items.*.bon_out_section'    => 'nullable|in:A,B,C,D,E',
            'items.*.remark'             => 'nullable|string|max:255',
        ]);

        // Sparepart section (E) items must be billed — require a selling price
        foreach ($validated['items'] as $itemData) {
            if (($itemData['bon_out_section'] ?? null) === 'E' && (float) ($itemData['actual_quantity'] ?? 0) > 0) {
                if (empty($itemData['unit_price']) || (float) $itemData['unit_price'] <= 0) {
                    return back()->withInput()->with('error', 'Sparepart (Section E) items must have a Selling Price greater than 0, since they are billed to the customer.');
                }
            }
        }

        $workOrder = WorkOrder::with('items.item')->findOrFail($validated['work_order_id']);

        if ($workOrder->status !== 'in_progress') {
            return back()->with('error', 'Work Order must be in progress to create a Bon Out.');
        }

        // Filter out items with zero quantity - only save items actually used
        $itemsToSave = array_filter($validated['items'], function ($item) {
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
                'created_by'     => Auth::id(),
            ]);

            foreach ($itemsToSave as $itemData) {
                $item = Item::with('smallestUom')->findOrFail($itemData['item_id']);

                // Get demand_quantity from WO item if it exists, otherwise 0 (for new materials)
                $demandQuantity = 0;
                if (!empty($itemData['work_order_item_id'])) {
                    $woItem = $workOrder->items()->find($itemData['work_order_item_id']);
                    $demandQuantity = $woItem ? $woItem->demand_quantity : 0;
                }

                // Selling price applies to any item with a price entered (spareparts always require one)
                $unitPrice = (isset($itemData['unit_price']) && (float) $itemData['unit_price'] > 0)
                    ? (float) $itemData['unit_price']
                    : null;

                BonOutItem::create([
                    'bon_out_id'          => $bonOut->id,
                    'work_order_item_id'  => $itemData['work_order_item_id'] ?? null,
                    'item_id'             => $item->id,
                    'uom_id'              => $item->smallestUom?->id,
                    'demand_quantity'     => $demandQuantity,
                    'actual_quantity'     => $itemData['actual_quantity'],
                    'unit_price'          => $unitPrice,
                    'remark'              => $itemData['remark'] ?? null,
                    'bon_out_section'     => $itemData['bon_out_section'] ?? null,
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
                'issued_to'      => Auth::user()?->name,
                'purpose'        => $validated['purpose'],
                'notes'          => $validated['notes'] ?? null,
                'status'         => 'on_progress',
                'created_by'     => Auth::id(),
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
            'canceller',
            'workOrder.customer',
            'workOrder.activeInvoice',
            'items.item.smallestUom',
            'items.item.itemUoms.uom',
        ]);

        return view('bon_outs.show', compact('bonOut'));
    }

    public function edit(BonOut $bonOut)
    {
        if (!PermissionHelper::canUpdate('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'update');
        }

        if ($bonOut->status !== 'on_progress') {
            return redirect()->route('bon_outs.show', $bonOut)
                ->with('error', 'Only on-progress Bon Outs can be edited.');
        }

        $bonOut->load([
            'workOrder.customer',
            'items.item.smallestUom',
            'items.item.stocks',
        ]);

        $allItems = Item::with(['smallestUom', 'stocks'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('bon_outs.edit', compact('bonOut', 'allItems'));
    }

    public function update(Request $request, BonOut $bonOut)
    {
        if (!PermissionHelper::canUpdate('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'update');
        }

        if ($bonOut->status !== 'on_progress') {
            return redirect()->route('bon_outs.show', $bonOut)
                ->with('error', 'Only on-progress Bon Outs can be edited.');
        }

        $validated = $request->validate([
            'notes'                      => 'nullable|string',
            'items'                      => 'required|array|min:1',
            'items.*.bon_out_item_id'    => 'nullable|exists:bon_out_items,id',
            'items.*.item_id'            => 'required|exists:items,id',
            'items.*.actual_quantity'    => 'required|numeric|min:0',
            'items.*.work_order_item_id' => 'nullable|exists:work_order_items,id',
            'items.*.unit_price'         => 'nullable|numeric|min:0',
            'items.*.bon_out_section'    => 'nullable|in:A,B,C,D,E',
            'items.*.remark'             => 'nullable|string|max:255',
        ]);

        // Sparepart section (E) items must be billed — require a selling price
        foreach ($validated['items'] as $itemData) {
            if (($itemData['bon_out_section'] ?? null) === 'E' && (float) ($itemData['actual_quantity'] ?? 0) > 0) {
                if (empty($itemData['unit_price']) || (float) $itemData['unit_price'] <= 0) {
                    return back()->withInput()->with('error', 'Sparepart (Section E) items must have a Selling Price greater than 0, since they are billed to the customer.');
                }
            }
        }

        // Filter out zero-quantity rows for new items (existing rows can be 0 = not used today)
        $itemsToProcess = array_filter($validated['items'], function ($item) {
            return (float) $item['actual_quantity'] >= 0;
        });

        if (empty($itemsToProcess)) {
            return back()->with('error', 'Please enter at least one item.');
        }

        DB::beginTransaction();
        try {
            $bonOut->update(['notes' => $validated['notes'] ?? null]);

            // Handle existing items — update their actual_quantity
            $existingIds = [];
            foreach ($itemsToProcess as $itemData) {
                if (!empty($itemData['bon_out_item_id'])) {
                    $bonOutItem = BonOutItem::findOrFail($itemData['bon_out_item_id']);

                    // Keep the linked WO line's actual_quantity in sync with the delta
                    if ($bonOutItem->work_order_item_id) {
                        $delta  = (float) $itemData['actual_quantity'] - (float) $bonOutItem->actual_quantity;
                        $woItem = WorkOrderItem::find($bonOutItem->work_order_item_id);
                        if ($woItem && $delta != 0) {
                            $woItem->update([
                                'actual_quantity' => max(0, (float) $woItem->actual_quantity + $delta),
                            ]);
                        }
                    }

                    $bonOutItem->update([
                        'actual_quantity' => $itemData['actual_quantity'],
                        'unit_price'      => $itemData['unit_price'] ?? $bonOutItem->unit_price,
                        'bon_out_section' => $itemData['bon_out_section'] ?? $bonOutItem->bon_out_section,
                        'remark'          => $itemData['remark'] ?? $bonOutItem->remark,
                    ]);
                    $existingIds[] = $bonOutItem->id;
                }
            }

            // Handle new items (no bon_out_item_id)
            foreach ($itemsToProcess as $itemData) {
                if (empty($itemData['bon_out_item_id']) && (float) $itemData['actual_quantity'] > 0) {
                    $item = Item::with('smallestUom')->findOrFail($itemData['item_id']);

                    $demandQty = 0;
                    if (!empty($itemData['work_order_item_id'])) {
                        $woItem = WorkOrderItem::find($itemData['work_order_item_id']);
                        $demandQty = $woItem ? $woItem->demand_quantity : 0;
                    }

                    $unitPrice = (isset($itemData['unit_price']) && (float) $itemData['unit_price'] > 0)
                        ? (float) $itemData['unit_price']
                        : null;

                    BonOutItem::create([
                        'bon_out_id'         => $bonOut->id,
                        'work_order_item_id' => $itemData['work_order_item_id'] ?? null,
                        'item_id'            => $item->id,
                        'uom_id'             => $item->smallestUom?->id,
                        'demand_quantity'    => $demandQty,
                        'actual_quantity'    => $itemData['actual_quantity'],
                        'unit_price'         => $unitPrice,
                        'bon_out_section'    => $itemData['bon_out_section'] ?? null,
                        'remark'             => $itemData['remark'] ?? null,
                    ]);

                    // New WO-linked lines also consume demand — accumulate like storeFromWO
                    if ($woItem) {
                        $woItem->update([
                            'actual_quantity' => (float) $woItem->actual_quantity + (float) $itemData['actual_quantity'],
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('bon_outs.show', $bonOut)
                ->with('success', 'Bon Out updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update Bon Out: ' . $e->getMessage());
        }
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
                        'unit_cost'        => $avgCostAtIssue,
                        'balance_after'    => $stock->quantity,
                        'location'         => 'default',
                        'reference_type'   => $isStandalone ? 'ADJUSTMENT_OUT' : 'BON_OUT',
                        'reference_id'     => $bonOut->id,
                        'notes'            => $refNotes,
                        'created_by'       => Auth::id(),
                    ]);

                    // Accumulate material cost for COGM
                    $cogmMaterial += $actualQty * $avgCostAtIssue;
                }
            }

            // Push items with selling price into WO billing
            if (!$isStandalone && $bonOut->work_order_id) {
                $woNeedsRecalc = false;
                foreach ($bonOut->items as $bonOutItem) {
                    if ((float) $bonOutItem->actual_quantity <= 0 || (float) $bonOutItem->unit_price <= 0) {
                        continue;
                    }

                    if ($bonOutItem->work_order_item_id) {
                        // Selling price came from a Bon Out for an original WO demand line
                        $woItem = WorkOrderItem::find($bonOutItem->work_order_item_id);
                        if ($woItem) {
                            $newTotal = (float) $bonOutItem->actual_quantity * (float) $bonOutItem->unit_price;
                            $existingTotal = (float) ($woItem->total_price ?? 0);
                            $woItem->update([
                                'unit_price'  => $bonOutItem->unit_price,
                                'total_price' => $existingTotal + $newTotal,
                            ]);
                            $woNeedsRecalc = true;
                        }
                    } else {
                        // Extra material not originally on WO — create a new billed line
                        WorkOrderItem::create([
                            'work_order_id'   => $bonOut->work_order_id,
                            'bon_out_item_id' => $bonOutItem->id,
                            'item_id'         => $bonOutItem->item_id,
                            'uom_id'          => $bonOutItem->uom_id,
                            'demand_quantity' => $bonOutItem->actual_quantity,
                            'actual_quantity' => $bonOutItem->actual_quantity,
                            'unit_price'      => $bonOutItem->unit_price,
                            'total_price'     => (float) $bonOutItem->actual_quantity * (float) $bonOutItem->unit_price,
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
                'completed_by' => Auth::id(),
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

        $bonOut->load(['workOrder.customer', 'items.item.smallestUom', 'items.workOrderItem', 'creator']);
        return view('bon_outs.print', compact('bonOut'));
    }

    /**
     * Cancel a Bon Out:
     *  - On-progress: release the WO demand quantities accumulated at creation
     *  - Completed: additionally return issued quantities to stock and revert
     *    any billed material lines pushed into the Work Order
     */
    public function cancel(BonOut $bonOut)
    {
        if (!PermissionHelper::canUpdate('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'update');
        }

        if (!in_array($bonOut->status, ['on_progress', 'completed'])) {
            return back()->with('error', 'This Bon Out is already cancelled.');
        }

        $wasCompleted = $bonOut->status === 'completed';

        $bonOut->load(['items', 'workOrder']);

        // Billed material lines are locked in once the WO has a live invoice
        if (
            $wasCompleted && $bonOut->workOrder
            && $bonOut->workOrder->invoice()->where('status', '!=', 'cancelled')->exists()
        ) {
            return back()->with('error', 'Cannot cancel this Bon Out: the linked Work Order already has an Invoice. Cancel the invoice first.');
        }

        DB::beginTransaction();
        try {
            if ($wasCompleted) {
                // Return issued quantities to stock at the same cost they left with
                foreach ($bonOut->items as $bonOutItem) {
                    $qty = (float) $bonOutItem->actual_quantity;
                    if ($qty <= 0) {
                        continue;
                    }

                    $stock = Stock::firstOrCreate(
                        ['item_id' => $bonOutItem->item_id, 'location' => 'default'],
                        ['quantity' => 0, 'avg_cost' => 0]
                    );

                    $unitCost = (float) ($bonOutItem->unit_cost ?? 0);
                    $stock->addQuantity($qty, $unitCost > 0 ? $unitCost : null);

                    StockTransaction::create([
                        'item_id'          => $bonOutItem->item_id,
                        'transaction_type' => 'in',
                        'quantity'         => $qty,
                        'unit_cost'        => $unitCost > 0 ? $unitCost : null,
                        'balance_after'    => $stock->quantity,
                        'location'         => 'default',
                        'reference_type'   => 'BON_OUT_CANCEL',
                        'reference_id'     => $bonOut->id,
                        'notes'            => "Returned via cancellation of Bon Out #{$bonOut->bon_out_number}",
                        'created_by'       => Auth::id(),
                    ]);
                }

                // Reverse billed material amounts pushed into WO billing at completion
                if ($bonOut->bon_out_type != 3 && $bonOut->work_order_id) {
                    $woNeedsRecalc = false;
                    foreach ($bonOut->items as $bonOutItem) {
                        $qty   = (float) $bonOutItem->actual_quantity;
                        $price = (float) ($bonOutItem->unit_price ?? 0);
                        if ($qty <= 0 || $price <= 0) {
                            continue;
                        }

                        if ($bonOutItem->work_order_item_id) {
                            // Original WO demand line — subtract this Bon Out's contribution
                            $woItem = WorkOrderItem::find($bonOutItem->work_order_item_id);
                            if ($woItem) {
                                $remaining = (float) ($woItem->total_price ?? 0) - ($qty * $price);
                                $woItem->update($remaining > 0
                                    ? ['total_price' => $remaining]
                                    : ['unit_price' => null, 'total_price' => null]);
                                $woNeedsRecalc = true;
                            }
                        } else {
                            // Extra billed line created by this Bon Out — remove it
                            $extraLine = WorkOrderItem::where('bon_out_item_id', $bonOutItem->id)->first()
                                ?? WorkOrderItem::where('work_order_id', $bonOut->work_order_id)
                                ->whereNull('bon_out_item_id')
                                ->where('item_id', $bonOutItem->item_id)
                                ->where('actual_quantity', $bonOutItem->actual_quantity)
                                ->where('unit_price', $bonOutItem->unit_price)
                                ->orderByDesc('id')
                                ->first();
                            if ($extraLine) {
                                $extraLine->delete();
                                $woNeedsRecalc = true;
                            }
                        }
                    }
                    if ($woNeedsRecalc) {
                        $bonOut->workOrder->calculateTotals();
                    }
                }
            }

            // WO demand lines accumulate actual_quantity at creation — release it
            $this->releaseWorkOrderUsage($bonOut);

            $bonOut->update([
                'status'       => 'cancelled',
                'cancelled_by' => Auth::id(),
                'cancelled_at' => now(),
            ]);

            DB::commit();

            $msg = $wasCompleted
                ? 'Bon Out cancelled. Issued quantities have been returned to stock.'
                : 'Bon Out cancelled.';

            return redirect()->route('bon_outs.show', $bonOut)->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to cancel Bon Out: ' . $e->getMessage());
        }
    }

    /**
     * Subtract this Bon Out's quantities from the linked WO demand lines.
     * Creation accumulates actual_quantity on work_order_items, so cancelling
     * or deleting must give it back.
     */
    private function releaseWorkOrderUsage(BonOut $bonOut): void
    {
        if (!$bonOut->work_order_id) {
            return;
        }

        $bonOut->loadMissing('items');
        foreach ($bonOut->items as $bonOutItem) {
            if (!$bonOutItem->work_order_item_id) {
                continue;
            }

            $woItem = WorkOrderItem::find($bonOutItem->work_order_item_id);
            if ($woItem) {
                $woItem->update([
                    'actual_quantity' => max(0, (float) $woItem->actual_quantity - (float) $bonOutItem->actual_quantity),
                ]);
            }
        }
    }

    public function destroy(BonOut $bonOut)
    {
        if (!PermissionHelper::canDelete('bon_outs')) {
            return PermissionHelper::denyAccess('bon_outs', 'delete');
        }

        if ($bonOut->status === 'completed') {
            return back()->with('error', 'Cannot delete a completed Bon Out.');
        }

        DB::beginTransaction();
        try {
            // On-progress Bon Outs still hold WO demand quantities — release them.
            // Cancelled Bon Outs were already released by cancel().
            if ($bonOut->status === 'on_progress') {
                $this->releaseWorkOrderUsage($bonOut);
            }

            $bonOut->delete();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete Bon Out: ' . $e->getMessage());
        }

        return redirect()->route('bon_outs.index')
            ->with('success', 'Bon Out deleted.');
    }
}
