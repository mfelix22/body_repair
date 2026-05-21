<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\StockCostAdjustment;
use App\Models\UOM;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('items')) {
            return PermissionHelper::denyAccess('items', 'view');
        }

        $items = Item::with(['smallestUom', 'itemUoms.uom'])
            ->withSum('stocks', 'quantity')
            ->get();
        return view('items.index', compact('items'));
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('items')) {
            return PermissionHelper::denyAccess('items', 'create');
        }

        $uoms = UOM::where('is_active', true)->get();
        return view('items.create', compact('uoms'));
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('items')) {
            return PermissionHelper::denyAccess('items', 'create');
        }
        $validated = $request->validate([
            'item_type' => 'required|in:A,B,C,E,T,TE',
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'smallest_uom_id' => 'required|exists:uoms,id',
            'reorder_level' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'uoms' => 'required|array|min:1',
            'uoms.*.uom_id' => 'required|exists:uoms,id',
            'uoms.*.conversion_to_smallest' => 'required|numeric|min:0.000001',
            'uoms.*.price' => 'nullable|numeric|min:0',
            'uoms.*.is_default' => 'boolean',
            'initial_cost'   => 'nullable|numeric|min:0',
            'selling_price'  => 'nullable|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        // Auto-generate code based on item type
        $prefix = $validated['item_type'];
        $lastItem = Item::where('item_type', $prefix)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastItem && preg_match('/' . $prefix . '(\d+)/', $lastItem->code, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        } else {
            $nextNumber = 1;
        }

        $code = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $sellingPrice = null;
        if (Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting'])) {
            $sellingPrice = isset($validated['selling_price']) ? (float) $validated['selling_price'] : null;
        }

        $item = Item::create([
            'item_type'      => $validated['item_type'],
            'code'           => $code,
            'name'           => $validated['name'],
            'description'    => $validated['description'],
            'category'       => $validated['category'],
            'smallest_uom_id' => $validated['smallest_uom_id'],
            'reorder_level'  => $validated['reorder_level'],
            'selling_price'  => $sellingPrice,
            'is_active'      => $validated['is_active'],
            'is_complete'    => true,
            'is_manual_entry' => true,
        ]);

        // Create item UOMs
        foreach ($validated['uoms'] as $uomData) {
            ItemUOM::create([
                'item_id' => $item->id,
                'uom_id' => $uomData['uom_id'],
                'conversion_to_smallest' => $uomData['conversion_to_smallest'],
                'price' => $uomData['price'] ?? 0,
                'is_default' => $uomData['is_default'] ?? false,
            ]);
        }

        // Create initial stock record; only accounting/admin may set an initial cost
        $initialCost = 0;
        if (Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting'])) {
            $initialCost = $validated['initial_cost'] ?? 0;
        }
        Stock::create([
            'item_id' => $item->id,
            'quantity' => 0,
            'avg_cost' => $initialCost,
            'location' => 'default',
        ]);

        return redirect()->route('items.index')->with('success', 'Item created successfully!');
    }

    public function show(Item $item)
    {
        if (!PermissionHelper::canView('items')) {
            return PermissionHelper::denyAccess('items', 'view');
        }

        $item->load(['smallestUom', 'itemUoms.uom', 'stocks']);
        $costAdjustments = StockCostAdjustment::where('item_id', $item->id)
            ->with('adjustedBy')
            ->orderByDesc('created_at')
            ->get();
        return view('items.show', compact('item', 'costAdjustments'));
    }

    public function edit(Item $item)
    {
        if (!PermissionHelper::canUpdate('items')) {
            return PermissionHelper::denyAccess('items', 'update');
        }

        $item->load('itemUoms');
        $uoms = UOM::where('is_active', true)->get();
        return view('items.edit', compact('item', 'uoms'));
    }

    public function update(Request $request, Item $item)
    {
        if (!PermissionHelper::canUpdate('items')) {
            return PermissionHelper::denyAccess('items', 'update');
        }
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'smallest_uom_id' => 'required|exists:uoms,id',
            'reorder_level' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'uoms' => 'required|array|min:1',
            'uoms.*.uom_id' => 'required|exists:uoms,id',
            'uoms.*.conversion_to_smallest' => 'required|numeric|min:0.000001',
            'uoms.*.price' => 'nullable|numeric|min:0',
            'uoms.*.is_default' => 'boolean',
            'initial_cost'  => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $updateData = [
            'name'            => $validated['name'],
            'description'     => $validated['description'],
            'category'        => $validated['category'],
            'smallest_uom_id' => $validated['smallest_uom_id'],
            'reorder_level'   => $validated['reorder_level'],
            'is_active'       => $validated['is_active'],
            'is_complete'     => true,
        ];
        if (Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting'])) {
            $updateData['selling_price'] = isset($validated['selling_price']) ? (float) $validated['selling_price'] : null;
        }
        $item->update($updateData);

        // Delete existing UOMs and recreate
        $item->itemUoms()->delete();

        foreach ($validated['uoms'] as $uomData) {
            ItemUOM::create([
                'item_id' => $item->id,
                'uom_id' => $uomData['uom_id'],
                'conversion_to_smallest' => $uomData['conversion_to_smallest'],
                'price' => $uomData['price'] ?? 0,
                'is_default' => $uomData['is_default'] ?? false,
            ]);
        }

        // Only accounting/admin can set initial cost, and only for manually created items.
        if (
            $item->is_manual_entry &&
            Auth::user()->hasAnyRole(['super_admin', 'admin', 'accounting']) &&
            array_key_exists('initial_cost', $validated)
        ) {
            Stock::updateOrCreate(
                [
                    'item_id' => $item->id,
                    'location' => 'default',
                ],
                [
                    'quantity' => $item->getCurrentStock('default'),
                    'avg_cost' => $validated['initial_cost'] ?? 0,
                ]
            );
        }

        return redirect()->route('items.index')->with('success', 'Item updated successfully!');
    }

    public function destroy(Item $item)
    {
        if (!PermissionHelper::canDelete('items')) {
            return PermissionHelper::denyAccess('items', 'delete');
        }

        try {
            $item->delete();
            return redirect()->route('items.index')->with('success', 'Item deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->route('items.index')->with('error', 'Cannot delete item. It may be in use.');
        }
    }

    public function adjustCost(Request $request, Item $item)
    {
        if (!Auth::user()->hasAnyRole(['super_admin', 'accounting'])) {
            abort(403, 'Only Accounting or Super Admin can adjust average cost.');
        }

        $validated = $request->validate([
            'new_avg_cost' => 'required|numeric|min:0.01',
            'reason'       => 'required|string|min:10|max:500',
        ]);

        $stock = $item->stocks()->where('location', 'default')->first();

        if (!$stock) {
            return back()->with('error', 'No default stock record found for this item.');
        }

        $oldCost = (float) $stock->avg_cost;
        $newCost = (float) $validated['new_avg_cost'];

        if ($oldCost === $newCost) {
            return back()->with('error', 'New cost is the same as the current cost. No change made.');
        }

        $stock->update(['avg_cost' => $newCost]);

        StockCostAdjustment::create([
            'item_id'      => $item->id,
            'stock_id'     => $stock->id,
            'old_avg_cost' => $oldCost,
            'new_avg_cost' => $newCost,
            'reason'       => $validated['reason'],
            'adjusted_by'  => Auth::id(),
        ]);

        return redirect()->route('items.show', $item)
            ->with('success', 'Average cost updated from Rp ' . number_format($oldCost, 2) . ' to Rp ' . number_format($newCost, 2) . '.');
    }
}
