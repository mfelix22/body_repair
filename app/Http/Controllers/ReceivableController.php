<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Receivable;
use App\Models\ReceivableItem;
use App\Models\PurchaseOrder;
use App\Models\ItemUOM;
use App\Models\Stock;
use App\Models\StockTransaction;
use App\Models\Item;
use App\Models\AuditLog;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!PermissionHelper::canView('receivables')) {
            return PermissionHelper::denyAccess('receivables', 'view');
        }

        $month    = $request->input('month');
        $year     = $request->input('year', date('Y'));
        $category = $request->input('category');

        $query = Receivable::with(['purchaseOrder.supplier', 'supplier'])
            ->orderBy('received_date', 'desc')
            ->orderBy('id', 'desc');

        if ($month) {
            $query->whereMonth('received_date', (int) $month);
        }
        if ($year) {
            $query->whereYear('received_date', (int) $year);
        }
        if ($category) {
            $query->whereHas('items.item', function ($q) use ($category) {
                $q->where('item_type', $category);
            });
        }

        $receivables = $query->paginate(25)->appends($request->query());

        $allYears = Receivable::selectRaw('YEAR(received_date) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');
        $categories = DB::table('items')->select('item_type')
            ->distinct()->orderBy('item_type')->pluck('item_type');

        return view('receivables.index', compact('receivables', 'month', 'year', 'category', 'allYears', 'categories'));
    }

    /**
     * Show the form for creating a new resource from a PO.
     */
    public function create(Request $request)
    {
        if (!PermissionHelper::canCreate('receivables')) {
            return PermissionHelper::denyAccess('receivables', 'create');
        }

        $po_id = $request->query('po_id');

        if (!$po_id) {
            return redirect()->route('purchase_orders.index')
                ->with('error', 'Please select a Purchase Order first.');
        }

        $purchaseOrder = PurchaseOrder::with(['details.item.itemUoms.uom', 'details.uom', 'supplier'])
            ->findOrFail($po_id);

        // Check if PO is PPJ (service order) - Bon In only for PPB (items)
        if ($purchaseOrder->po_type === 'service_order') {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('info', 'PPJ (Service Order) does not require Bon In. A service is directly completed upon director approval.');
        }

        if (!in_array($purchaseOrder->status, ['approved', 'partial'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Bon In can only be created for approved or partial PO.');
        }

        // Only show details that still have remaining quantity to receive.
        $purchaseOrder->setRelation(
            'details',
            $purchaseOrder->details->filter(function ($detail) {
                return ($detail->quantity - ($detail->received_quantity ?? 0) - ($detail->closed_shortage_quantity ?? 0)) > 0;
            })->values()
        );

        if ($purchaseOrder->details->isEmpty()) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('info', 'All PO items are already fully received.');
        }

        return view('receivables.create', compact('purchaseOrder'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('receivables')) {
            return PermissionHelper::denyAccess('receivables', 'create');
        }

        // Get PO and verify it's PPB, not PPJ
        $po_id = $request->input('purchase_order_id');
        $purchaseOrder = PurchaseOrder::findOrFail($po_id);

        if ($purchaseOrder->po_type === 'service_order') {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'PPJ (Service Order) does not require Bon In. A service is directly completed upon director approval.');
        }

        if (!in_array($purchaseOrder->status, ['approved', 'partial'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Bon In can only be created for approved or partial PO.');
        }

        $validated = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'bon_in_type' => 'required|integer|in:1,2',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
        ]);

        $poDetails = $purchaseOrder->details->keyBy(function ($detail) {
            return $detail->item_id . '-' . $detail->uom_id;
        });

        $hasAnyReceivedQty = false;
        $itemsForCreate = [];

        foreach ($validated['items'] as $idx => $item) {
            $key = $item['item_id'] . '-' . $item['uom_id'];
            $poDetail = $poDetails->get($key);

            if (!$poDetail) {
                return back()->withInput()->withErrors([
                    "items.$idx.item_id" => 'Item/UOM does not match this PO detail.',
                ]);
            }

            $remainingQty = max(0, (float) $poDetail->quantity - (float) ($poDetail->received_quantity ?? 0) - (float) ($poDetail->closed_shortage_quantity ?? 0));
            $receivedQty = (float) $item['quantity_received'];

            if ($receivedQty > $remainingQty) {
                return back()->withInput()->withErrors([
                    "items.$idx.quantity_received" => "Received quantity cannot exceed remaining quantity ({$remainingQty}).",
                ]);
            }

            if ($receivedQty > 0) {
                $hasAnyReceivedQty = true;
            }

            $itemsForCreate[] = [
                'item_id' => $item['item_id'],
                'uom_id' => $item['uom_id'],
                'quantity_ordered' => $remainingQty,
                'quantity_received' => $receivedQty,
            ];
        }

        if (!$hasAnyReceivedQty) {
            return back()->withInput()->withErrors([
                'items' => 'At least one item must have received quantity greater than 0.',
            ]);
        }

        DB::beginTransaction();
        try {
            // Generate receive number
            $bonInType = (int) $validated['bon_in_type'];
            $lastReceivable = Receivable::where('bon_in_type', $bonInType)
                ->orderBy('id', 'desc')
                ->first();

            $base = $bonInType * 100000;
            if ($lastReceivable) {
                $lastSeq = (int) $lastReceivable->receive_number - $base;
                $nextSeq = $lastSeq + 1;
            } else {
                $nextSeq = 1;
            }
            $receiveNumber = (string) ($base + $nextSeq);

            // Detect variance: if any item has quantity_received < quantity_ordered
            $hasVariance = false;
            foreach ($itemsForCreate as $item) {
                if ($item['quantity_received'] < $item['quantity_ordered']) {
                    $hasVariance = true;
                    break;
                }
            }

            // Create receivable
            $receivable = Receivable::create([
                'receive_number' => $receiveNumber,
                'bon_in_type' => $bonInType,
                'purchase_order_id' => $validated['purchase_order_id'],
                'received_date' => now(),
                'status' => $hasVariance ? 'partial_received' : 'on_progress',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create receivable items
            foreach ($itemsForCreate as $item) {
                ReceivableItem::create([
                    'receivable_id' => $receivable->id,
                    'item_id' => $item['item_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => $item['quantity_received'],
                ]);
            }

            DB::commit();

            return redirect()->route('receivables.show', $receivable)
                ->with('success', 'Bon In created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create Bon In: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a standalone Bon In (type 3 only, no PO).
     */
    public function createStandalone(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['warehouse', 'admin', 'super_admin', 'accounting'])) {
            return redirect()->route('receivables.index')
                ->with('error', 'Only warehouse staff can create Bon In.');
        }

        // Get all items for the form
        $items = Item::where('is_active', true)
            ->with(['itemUoms.uom', 'smallestUom'])
            ->orderBy('name')
            ->get();

        return view('receivables.create_standalone', compact('items'));
    }

    /**
     * Store a standalone Bon In (type 3 only, no PO).
     */
    public function storeStandalone(Request $request)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['warehouse', 'admin', 'super_admin', 'accounting'])) {
            return redirect()->route('receivables.index')
                ->with('error', 'Only warehouse staff can create Bon In.');
        }

        $validated = $request->validate([
            'bon_in_type' => 'required|integer|in:3', // Adjustment In only
            'notes' => 'required|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity_received' => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            // Generate receive number
            $bonInType = (int) $validated['bon_in_type'];
            $lastReceivable = Receivable::where('bon_in_type', $bonInType)
                ->orderBy('id', 'desc')
                ->first();

            $base = $bonInType * 100000;
            if ($lastReceivable) {
                $lastSeq = (int) $lastReceivable->receive_number - $base;
                $nextSeq = $lastSeq + 1;
            } else {
                $nextSeq = 1;
            }
            $receiveNumber = (string) ($base + $nextSeq);

            // Create receivable (no PO)
            $receivable = Receivable::create([
                'receive_number' => $receiveNumber,
                'bon_in_type' => $bonInType,
                'purchase_order_id' => null, // No PO
                'supplier_id' => null,
                'supplier_name' => null,
                'received_date' => now(),
                'status' => 'on_progress',
                'notes' => $validated['notes'],
            ]);

            // Create receivable items
            foreach ($validated['items'] as $item) {
                ReceivableItem::create([
                    'receivable_id' => $receivable->id,
                    'item_id' => $item['item_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity_ordered' => 0, // No order quantity for standalone
                    'quantity_received' => $item['quantity_received'],
                ]);
            }

            DB::commit();

            return redirect()->route('receivables.show', $receivable)
                ->with('success', 'Bon In created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create Bon In: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Receivable $receivable)
    {
        $receivable->load([
            'purchaseOrder.supplier',
            'supplier',
            'items.item.itemUoms.uom',
            'items.item.smallestUom',
            'items.uom'
        ]);

        return view('receivables.show', compact('receivable'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Receivable $receivable)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['warehouse', 'admin', 'super_admin'])) {
            return redirect()->route('receivables.show', $receivable)
                ->with('error', 'Only warehouse staff can edit Bon In.');
        }

        // Only allow editing if status is on_progress or partial_received
        if (!in_array($receivable->status, ['on_progress', 'partial_received'])) {
            return redirect()->route('receivables.show', $receivable)
                ->with('error', 'Only Bon In with on_progress or partial_received status can be edited.');
        }

        $receivable->load([
            'purchaseOrder.details.item.itemUoms.uom',
            'purchaseOrder.supplier',
            'supplier',
            'items.item.itemUoms.uom',
            'items.uom'
        ]);

        // Get all items for dropdown (for standalone Bon In)
        $items = Item::with(['itemUoms.uom', 'smallestUom', 'stocks'])->get();
        $suppliers = Supplier::all();

        return view('receivables.edit', compact('receivable', 'items', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Receivable $receivable)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['warehouse', 'admin', 'super_admin'])) {
            return redirect()->route('receivables.show', $receivable)
                ->with('error', 'Only warehouse staff can update Bon In.');
        }

        // Only allow updating if status is on_progress or partial_received
        if (!in_array($receivable->status, ['on_progress', 'partial_received'])) {
            return redirect()->route('receivables.show', $receivable)
                ->with('error', 'Only Bon In with on_progress or partial_received status can be updated.');
        }

        $validated = $request->validate([
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0',
            'items.*.quantity_received' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Detect variance
            $hasVariance = false;
            foreach ($validated['items'] as $item) {
                if ($item['quantity_received'] < $item['quantity_ordered']) {
                    $hasVariance = true;
                    break;
                }
            }

            // Update receivable
            $receivable->update([
                'received_date' => $validated['received_date'],
                'status' => $hasVariance ? 'partial_received' : 'on_progress',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Delete old items and create new ones
            $receivable->items()->delete();
            foreach ($validated['items'] as $item) {
                ReceivableItem::create([
                    'receivable_id' => $receivable->id,
                    'item_id' => $item['item_id'],
                    'uom_id' => $item['uom_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'quantity_received' => $item['quantity_received'],
                ]);
            }

            DB::commit();

            return redirect()->route('receivables.show', $receivable)
                ->with('success', 'Bon In updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to update Bon In: ' . $e->getMessage());
        }
    }

    public function cancel(Receivable $receivable)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['warehouse', 'admin', 'super_admin'])) {
            return redirect()->route('receivables.show', $receivable)
                ->with('error', 'Only warehouse staff can cancel Bon In.');
        }

        if (!in_array($receivable->status, ['on_progress', 'partial_received'])) {
            return redirect()->route('receivables.show', $receivable)
                ->with('error', 'Only on progress or partial received Bon In can be cancelled.');
        }

        $receivable->update(['status' => 'cancelled']);

        return redirect()->route('receivables.show', $receivable)
            ->with('success', 'Bon In cancelled successfully.');
    }

    /**
     * Print Bon In for a receivable.
     */
    public function print(Receivable $receivable)
    {
        if (!PermissionHelper::canPrint('receivables')) {
            return PermissionHelper::denyAccess('receivables', 'view');
        }

        // Stamp printed_at but do NOT change the workflow status (mirrors PO behaviour)
        if ($receivable->status === 'completed' && !$receivable->printed_at) {
            $receivable->update([
                'printed_at' => now(),
                'printed_by' => auth()->id(),
            ]);
        }

        $receivable->load([
            'purchaseOrder.supplier',
            'supplier',
            'items.item',
            'items.uom'
        ]);
        return view('receivables.print', compact('receivable'));
    }

    /**
     * Complete the receivable and update stock.
     */
    public function complete(Receivable $receivable)
    {
        $user = auth()->user();
        if (!$user || !$user->hasAnyRole(['warehouse', 'admin', 'super_admin'])) {
            return back()->with('error', 'Only warehouse staff can complete Bon In.');
        }
        if (!in_array($receivable->status, ['on_progress', 'partial_received'])) {
            return back()->with('error', 'Only on progress or partial received Bon In can be completed.');
        }

        DB::beginTransaction();
        try {
            $po = $receivable->purchaseOrder;
            $hasPO = $po && $po->id;

            // Update stock for each received item
            foreach ($receivable->items as $receivableItem) {
                $item = $receivableItem->item;
                $uom = $receivableItem->uom;

                // Find conversion factor to smallest UOM from item master
                $itemUom = $item->itemUoms()
                    ->where('uom_id', $uom->id)
                    ->first();

                if (!$itemUom) {
                    throw new \Exception("Conversion factor not found for {$item->name} in {$uom->name}");
                }

                // Fetch PO detail once (used for conversion override, avg cost, and received qty update)
                $poDetail = $hasPO
                    ? $po->details()->where('item_id', $item->id)->where('uom_id', $uom->id)->first()
                    : null;

                // Use item master as the authoritative conversion source.
                // Only override with PO detail when it's explicitly > 1, meaning the user entered
                // a supplier-specific conversion (e.g. supplier carton ≠ item master carton).
                // A value of 1 on the PO detail means it was never properly set — do not use it.
                $itemMasterConversion = (float) $itemUom->conversion_to_smallest;
                $poDetailConversion   = $poDetail ? (float) $poDetail->conversion_to_smallest : 0;
                $conversionFactor     = ($poDetailConversion > 1)
                    ? $poDetailConversion
                    : $itemMasterConversion;

                $quantityInSmallestUom = $receivableItem->quantity_received * $conversionFactor;

                // Update or create stock (always use default location)
                $stock = Stock::firstOrNew([
                    'item_id' => $item->id,
                    'location' => 'default',
                ]);

                $oldQuantity = $stock->quantity ?? 0;
                $oldAvgCost = $stock->avg_cost ?? 0;

                // Calculate average cost
                if ($hasPO) {
                    // From PO: use PO unit price (converted to smallest UOM)
                    if ($poDetail && $quantityInSmallestUom > 0) {
                        $receivedUnitCost = $poDetail->unit_price / $conversionFactor;
                        $newQuantity = $oldQuantity + $quantityInSmallestUom;
                        $stock->avg_cost = $newQuantity > 0
                            ? (($oldQuantity * $oldAvgCost) + ($quantityInSmallestUom * $receivedUnitCost)) / $newQuantity
                            : $oldAvgCost;
                    }
                } else {
                    // Standalone (no PO): keep existing average cost
                    // For type 2 (customer specific) and type 3 (adjustment),
                    // cost will be handled separately if needed
                    $stock->avg_cost = $oldAvgCost;
                }

                $stock->quantity = $oldQuantity + $quantityInSmallestUom;
                $stock->save();

                // Create stock transaction record
                StockTransaction::create([
                    'item_id'          => $item->id,
                    'transaction_type' => 'in',
                    'quantity'         => $quantityInSmallestUom,
                    'balance_after'    => $stock->quantity,
                    'location'         => 'default',
                    'reference_type'   => $hasPO ? 'PO' : 'BON_IN',
                    'reference_id'     => $hasPO ? $po->id : $receivable->id,
                    'notes'            => $hasPO
                        ? "Bon In #{$receivable->receive_number} – from PO {$po->po_number}"
                        : "Standalone Bon In #{$receivable->receive_number}",
                    'created_by'       => auth()->id(),
                ]);

                // Update PO detail received quantity (only if there's a PO)
                if ($hasPO) {
                    if ($poDetail) {
                        $newReceivedQty = (float) $poDetail->received_quantity + (float) $receivableItem->quantity_received;
                        if ($newReceivedQty + (float) ($poDetail->closed_shortage_quantity ?? 0) > (float) $poDetail->quantity) {
                            throw new \Exception("Received quantity exceeds ordered quantity for {$item->name}.");
                        }

                        $poDetail->update([
                            'received_quantity' => $newReceivedQty,
                        ]);
                    }
                }
            }

            // Mark receivable as completed
            $receivable->update(['status' => 'completed']);

            // Update PO status based on received quantities (only if there's a PO)
            if ($hasPO) {
                $po->load('details');
                $hasOpenLines = $po->details->contains(function ($detail) {
                    return (float) $detail->quantity > ((float) $detail->received_quantity + (float) ($detail->closed_shortage_quantity ?? 0));
                });
                $hasClosedShortage = $po->details->contains(function ($detail) {
                    return (float) ($detail->closed_shortage_quantity ?? 0) > 0;
                });

                $po->update([
                    'status' => $hasOpenLines ? 'partial' : ($hasClosedShortage ? 'closed_shortage' : 'received'),
                ]);
            }

            DB::commit();

            return redirect()->route('receivables.show', $receivable)
                ->with('success', 'Bon In completed and stock updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to complete Bon In: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * 
     * DISABLED: In ERP systems, transactional records should NEVER be deleted.
     * Use the Cancel function instead to maintain audit trail and data integrity.
     */
    public function destroy(Receivable $receivable)
    {
        return back()->with('error', 'Delete is not allowed in ERP systems. Please use the Cancel button instead to maintain audit trail.');

        // Original delete code disabled for ERP compliance:
        // $user = auth()->user();
        // if (!$user || !$user->hasAnyRole(['warehouse', 'admin', 'super_admin'])) {
        //     return back()->with('error', 'Only warehouse staff can delete Bon In.');
        // }
        // if ($receivable->status === 'completed') {
        //     return back()->with('error', 'Cannot delete completed Bon In.');
        // }
        // $receivable->delete();
        // return redirect()->route('receivables.index')
        //     ->with('success', 'Bon In deleted successfully.');
    }
}
