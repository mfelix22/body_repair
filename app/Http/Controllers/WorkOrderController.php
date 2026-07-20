<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\WorkOrderLabor;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\ItemUOM;
use App\Models\Labor;
use App\Models\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    public function index()
    {
        $wos = WorkOrder::with(['customer', 'creator', 'proformaInvoice'])
            ->withCount(['items', 'labors' => function ($query) {
                $query->whereNotNull('labor_id');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        return view('work_orders.index', compact('wos'));
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('work_orders')) {
            return PermissionHelper::denyAccess('work_orders', 'create');
        }

        $customers = Customer::where('is_active', true)
            ->with(['vehicles' => function ($q) {
                $q->where('is_active', true)->orderBy('plate_number');
            }])
            ->get();
        $items = Item::where('is_active', true)->with(['itemUoms.uom', 'smallestUom', 'stocks'])->get();

        $completedWos = WorkOrder::where('status', 'completed')
            ->orWhere('status', 'invoiced')
            ->with('customer')
            ->orderBy('work_date', 'desc')
            ->get(['id', 'wo_number', 'customer_id', 'vehicle_plate', 'work_date']);

        $masterPanels = Panel::where('is_active', true)->orderBy('panel_code')->get();
        $masterLabors = Labor::where('is_active', true)->where('labor_code', 'not like', 'PNL-%')->orderBy('labor_code')->get();

        return view('work_orders.create', compact('customers', 'items', 'completedWos', 'masterPanels', 'masterLabors'));
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('work_orders')) {
            return PermissionHelper::denyAccess('work_orders', 'create');
        }

        $validated = $request->validate([
            'customer_id'          => 'required|exists:customers,id',
            'billing_customer_id'  => 'nullable|exists:customers,id',
            'vehicle_id'           => 'nullable|exists:vehicles,id',
            'account_code'         => 'required|in:C,INT_WS,INT_W3',
            'reference_wo_id'      => 'nullable|exists:work_orders,id',
            'work_date'            => 'required|date',
            'deadline'             => 'nullable|date',
            'vehicle_info'         => 'nullable|string|max:200',
            'vehicle_merk'         => 'nullable|string|max:100',
            'vehicle_type_year'    => 'nullable|string|max:100',
            'vehicle_plate'        => 'nullable|string|max:100',
            'vehicle_km'           => 'nullable|integer|min:0',
            'vehicle_price_tier'   => 'nullable|in:0_300,300_500,500_800,800_2000',
            'chasis_no'            => 'nullable|string|max:100',
            'description'          => 'nullable|string',
            'notes'                => 'nullable|string',
            'sa_sales'             => 'nullable|string|max:100',
            'items'                => 'nullable|array',
            'items.*.item_id'      => 'required|exists:items,id',
            'items.*.demand_quantity' => 'required|numeric|min:0.01',
            'items.*.remark'       => 'nullable|string|max:255',
            'panels'               => 'nullable|array',
            'panels.*.panel_id'    => 'required|exists:panels,id',
            'panels.*.qty'         => 'nullable|numeric|min:0.01',
            'panels.*.remarks'     => 'nullable|string',
            'labors'               => 'nullable|array',
            'labors.*.labor_id'    => 'nullable|exists:labors,id',
            'labors.*.qty'         => 'nullable|numeric|min:0.01',
            'labors.*.remarks'     => 'nullable|string',
        ]);

        $priceTier = $validated['vehicle_price_tier'] ?? null;

        // Auto-generate WO number: YYMM/HAS/SEQ (monthly reset)
        $yy = date('y');
        $mm = date('m');
        $prefix = $yy . $mm . '/HAS/';
        $lastWO = WorkOrder::where('wo_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();
        $nextNumber = $lastWO ? intval(substr($lastWO->wo_number, -3)) + 1 : 1;
        $woNumber = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        // Auto-save vehicle to master data if checkbox is ticked
        $vehicleId = $validated['vehicle_id'] ?? null;
        if ($request->boolean('save_to_vehicle_master') && !empty($validated['vehicle_plate'])) {
            $plate    = strtoupper(trim($validated['vehicle_plate']));
            $existing = Vehicle::where('plate_number', $plate)->first();
            if ($existing) {
                $vehicleId = $existing->id;
            } else {
                $newVehicle = Vehicle::create([
                    'customer_id'  => $validated['customer_id'],
                    'plate_number' => $plate,
                    'brand'        => $validated['vehicle_merk'] ?? null,
                    'model'        => $validated['vehicle_type_year'] ?? null,
                    'chasis_no'    => $validated['chasis_no'] ?? null,
                    'is_active'    => true,
                ]);
                $vehicleId = $newVehicle->id;
            }
        }

        $wo = WorkOrder::create([
            'wo_number'            => $woNumber,
            'customer_id'          => $validated['customer_id'],
            'billing_customer_id'  => $validated['billing_customer_id'] ?? null,
            'vehicle_id'           => $vehicleId,
            'account_code'      => $validated['account_code'],
            'work_date'         => $validated['work_date'],
            'deadline'          => $validated['deadline'] ?? null,
            'vehicle_info'      => $validated['vehicle_info'] ?? null,
            'vehicle_merk'      => $validated['vehicle_merk'] ?? null,
            'vehicle_type_year' => $validated['vehicle_type_year'] ?? null,
            'vehicle_plate'     => $validated['vehicle_plate'] ?? null,
            'vehicle_km'          => $validated['vehicle_km'] ?? null,
            'vehicle_price_tier'  => $priceTier,
            'chasis_no'           => $validated['chasis_no'] ?? null,
            'description'         => $validated['description'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'sa_sales'            => $validated['sa_sales'] ?? null,
            'reference_wo_id'     => $validated['reference_wo_id'] ?? null,
            'status'              => 'on_progress',
            'labor_total'       => 0,
            'material_total'    => 0,
            'grand_total'       => 0,
            'created_by'        => auth()->id(),
        ]);

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                WorkOrderItem::create([
                    'work_order_id'   => $wo->id,
                    'item_id'         => $itemData['item_id'],
                    'demand_quantity' => $itemData['demand_quantity'],
                    'remark'          => $itemData['remark'] ?? null,
                    'unit_price'      => null,
                    'total_price'     => null,
                ]);
            }
        }

        if (!empty($validated['panels'])) {
            foreach ($validated['panels'] as $panelData) {
                $panel = Panel::findOrFail($panelData['panel_id']);
                $qty = (float) ($panelData['qty'] ?? 1);
                $rate = self::getPanelRateForTier($panel, $priceTier);
                $totalPrice = $qty * $rate;
                WorkOrderLabor::create([
                    'work_order_id' => $wo->id,
                    'panel_id'      => $panel->id,
                    'description'   => $panel->description,
                    'qty'           => $qty,
                    'rate'          => $rate,
                    'total_price'   => $totalPrice,
                    'remarks'       => $panelData['remarks'] ?? null,
                    'is_extra'      => false,
                ]);
            }
        }

        if (!empty($validated['labors'])) {
            foreach ($validated['labors'] as $laborData) {
                if (empty($laborData['labor_id'])) {
                    continue;
                }
                $labor = Labor::findOrFail($laborData['labor_id']);
                $qty = (float) ($laborData['qty'] ?? 1);
                $rate = self::getLaborRateForTier($labor, $priceTier);
                $totalPrice = $qty * $rate;
                WorkOrderLabor::create([
                    'work_order_id' => $wo->id,
                    'labor_id'      => $labor->id,
                    'description'   => $labor->description,
                    'qty'           => $qty,
                    'rate'          => $rate,
                    'total_price'   => $totalPrice,
                    'remarks'       => $laborData['remarks'] ?? null,
                    'is_extra'      => false,
                ]);
            }
        }

        $wo->calculateTotals();

        return redirect()->route('work_orders.index')->with('success', 'Work Order created successfully!');
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['customer', 'billingCustomer', 'creator', 'items.item.smallestUom', 'labors.labor', 'labors.panel', 'referenceWo', 'invoice', 'invoices.creditNote', 'bonOuts', 'proformaInvoice']);

        // Pass active general labors for the Add Labor modal
        $masterLabors = Labor::where('is_active', true)->where('labor_code', 'not like', 'PNL-%')->orderBy('labor_code')->get();

        return view('work_orders.show', compact('workOrder', 'masterLabors'));
    }

    public function edit(WorkOrder $workOrder)
    {
        if (!in_array($workOrder->status, ['on_progress', 'in_progress'])) {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Only on progress or in progress Work Orders can be edited.');
        }

        $workOrder->load('items', 'labors');
        $customers = Customer::where('is_active', true)
            ->with(['vehicles' => function ($q) {
                $q->where('is_active', true)->orderBy('plate_number');
            }])
            ->get();
        // Include inactive items that are already referenced by this WO so existing rows still display correctly
        $woItemIds = $workOrder->items->pluck('item_id')->filter()->toArray();
        $items = Item::where(function ($q) use ($woItemIds) {
            $q->where('is_active', true)->orWhereIn('id', $woItemIds);
        })->with(['itemUoms.uom', 'smallestUom', 'stocks'])->get();

        $completedWos = WorkOrder::whereIn('status', ['completed', 'invoiced'])
            ->with('customer')
            ->orderBy('work_date', 'desc')
            ->get(['id', 'wo_number', 'customer_id', 'vehicle_plate', 'work_date']);

        $masterPanels = Panel::where('is_active', true)->orderBy('panel_code')->get();
        $masterLabors = Labor::where('is_active', true)->where('labor_code', 'not like', 'PNL-%')->orderBy('labor_code')->get();

        return view('work_orders.edit', compact('workOrder', 'customers', 'items', 'completedWos', 'masterPanels', 'masterLabors'));
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        if (!in_array($workOrder->status, ['on_progress', 'in_progress'])) {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Only on progress or in progress Work Orders can be edited.');
        }

        $validated = $request->validate([
            'customer_id'          => 'required|exists:customers,id',
            'billing_customer_id'  => 'nullable|exists:customers,id',
            'vehicle_id'           => 'nullable|exists:vehicles,id',
            'account_code'         => 'required|in:C,INT_WS,INT_W3',
            'work_date'            => 'required|date',
            'deadline'             => 'nullable|date',
            'vehicle_info'         => 'nullable|string|max:200',
            'vehicle_merk'         => 'nullable|string|max:100',
            'vehicle_type_year'    => 'nullable|string|max:100',
            'vehicle_plate'        => 'nullable|string|max:20',
            'vehicle_km'           => 'nullable|integer|min:0',
            'vehicle_price_tier'   => 'nullable|in:0_300,300_500,500_800,800_2000',
            'chasis_no'            => 'nullable|string|max:100',
            'description'          => 'nullable|string',
            'notes'                => 'nullable|string',
            'sa_sales'             => 'nullable|string|max:100',
            'reference_wo_id'      => 'nullable|exists:work_orders,id',
            'items'             => 'nullable|array',
            'items.*.item_id'   => 'required|exists:items,id',
            'items.*.demand_quantity' => 'required|numeric|min:0.01',
            'items.*.remark'    => 'nullable|string|max:255',
            'panels'                  => 'nullable|array',
            'panels.*.panel_id'       => 'required|exists:panels,id',
            'panels.*.qty'            => 'nullable|numeric|min:0.01',
            'panels.*.remarks'        => 'nullable|string',
            'labors'                  => 'nullable|array',
            'labors.*.labor_id'       => 'nullable|exists:labors,id',
            'labors.*.qty'            => 'nullable|numeric|min:0.01',
            'labors.*.remarks'        => 'nullable|string',
        ]);

        $priceTier = $validated['vehicle_price_tier'] ?? null;

        // Auto-save vehicle to master data if checkbox is ticked
        $vehicleId = $validated['vehicle_id'] ?? $workOrder->vehicle_id;
        if ($request->boolean('save_to_vehicle_master') && !empty($validated['vehicle_plate'])) {
            $plate    = strtoupper(trim($validated['vehicle_plate']));
            $existing = Vehicle::where('plate_number', $plate)->first();
            if ($existing) {
                $vehicleId = $existing->id;
            } else {
                $newVehicle = Vehicle::create([
                    'customer_id'  => $validated['customer_id'],
                    'plate_number' => $plate,
                    'brand'        => $validated['vehicle_merk'] ?? null,
                    'model'        => $validated['vehicle_type_year'] ?? null,
                    'chasis_no'    => $validated['chasis_no'] ?? null,
                    'is_active'    => true,
                ]);
                $vehicleId = $newVehicle->id;
            }
        }

        $workOrder->update([
            'customer_id'          => $validated['customer_id'],
            'billing_customer_id'  => $validated['billing_customer_id'] ?? null,
            'vehicle_id'           => $vehicleId,
            'account_code'      => $validated['account_code'],
            'work_date'         => $validated['work_date'],
            'deadline'          => $validated['deadline'] ?? null,
            'vehicle_info'      => $validated['vehicle_info'] ?? null,
            'vehicle_merk'      => $validated['vehicle_merk'] ?? null,
            'vehicle_type_year' => $validated['vehicle_type_year'] ?? null,
            'vehicle_plate'     => $validated['vehicle_plate'] ?? null,
            'vehicle_km'          => $validated['vehicle_km'] ?? null,
            'vehicle_price_tier'  => $priceTier,
            'chasis_no'           => $validated['chasis_no'] ?? null,
            'description'         => $validated['description'] ?? null,
            'notes'               => $validated['notes'] ?? null,
            'sa_sales'            => $validated['sa_sales'] ?? null,
            'reference_wo_id'     => $validated['reference_wo_id'] ?? null,
            'labor_total'         => 0,
            'material_total'    => 0,
            'grand_total'       => 0,
        ]);

        // Delete old items and base labors/panels (extra labors added via addLabor are preserved)
        $workOrder->items()->delete();
        $workOrder->labors()->where('is_extra', false)->delete();

        // Add new items
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                WorkOrderItem::create([
                    'work_order_id'   => $workOrder->id,
                    'item_id'         => $itemData['item_id'],
                    'demand_quantity' => $itemData['demand_quantity'],
                    'remark'          => $itemData['remark'] ?? null,
                    'unit_price'      => null,
                    'total_price'     => null,
                ]);
            }
        }

        // Add new panels
        if (!empty($validated['panels'])) {
            foreach ($validated['panels'] as $panelData) {
                $panel = Panel::findOrFail($panelData['panel_id']);
                $qty = (float) ($panelData['qty'] ?? 1);
                $rate = self::getPanelRateForTier($panel, $priceTier);
                $totalPrice = $qty * $rate;
                WorkOrderLabor::create([
                    'work_order_id' => $workOrder->id,
                    'panel_id'      => $panel->id,
                    'description'   => $panel->description,
                    'qty'           => $qty,
                    'rate'          => $rate,
                    'total_price'   => $totalPrice,
                    'remarks'       => $panelData['remarks'] ?? null,
                    'is_extra'      => false,
                ]);
            }
        }

        // Add new labors
        if (!empty($validated['labors'])) {
            foreach ($validated['labors'] as $laborData) {
                if (empty($laborData['labor_id'])) {
                    continue;
                }
                $labor = Labor::findOrFail($laborData['labor_id']);
                $qty = (float) ($laborData['qty'] ?? 1);
                $rate = self::getLaborRateForTier($labor, $priceTier);
                $totalPrice = $qty * $rate;
                WorkOrderLabor::create([
                    'work_order_id' => $workOrder->id,
                    'labor_id'      => $labor->id,
                    'description'   => $labor->description,
                    'qty'           => $qty,
                    'rate'          => $rate,
                    'total_price'   => $totalPrice,
                    'remarks'       => $laborData['remarks'] ?? null,
                    'is_extra'      => false,
                ]);
            }
        }

        $workOrder->calculateTotals();

        return redirect()->route('work_orders.show', $workOrder)
            ->with('success', 'Work Order updated successfully!');
    }

    public function printView(WorkOrder $workOrder)
    {
        $workOrder->load(['customer', 'creator', 'items.item.smallestUom', 'labors.labor', 'labors.panel']);
        return view('work_orders.print', compact('workOrder'));
    }

    public function start(WorkOrder $workOrder)
    {
        if (!PermissionHelper::canUpdate('work_orders')) {
            return PermissionHelper::denyAccess('work_orders', 'update');
        }

        if ($workOrder->status !== 'on_progress') {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Only on progress Work Orders can be started.');
        }

        DB::beginTransaction();
        try {
            $workOrder->update(['status' => 'in_progress', 'started_at' => now()]);

            DB::commit();

            return redirect()->route('work_orders.show', $workOrder)
                ->with('success', 'Work Order started. Please issue actual materials via Bon Out.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Failed to start Work Order: ' . $e->getMessage());
        }
    }

    public function complete(Request $request, WorkOrder $workOrder)
    {
        if (!PermissionHelper::canUpdate('work_orders')) {
            return PermissionHelper::denyAccess('work_orders', 'update');
        }

        if ($workOrder->status !== 'in_progress') {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Only in-progress Work Orders can be completed.');
        }

        if (!$workOrder->bonOuts()->exists()) {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Cannot complete Work Order: at least one Bon Out is required before completing.');
        }

        if ($workOrder->hasIncompleteBonOuts()) {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Cannot complete Work Order: there are Bon Out(s) still in progress. Please complete all Bon Outs first.');
        }

        $workOrder->update(['status' => 'completed', 'completed_at' => now()]);

        return redirect()->route('work_orders.show', $workOrder)
            ->with('success', 'Work Order completed successfully. Finance can now create the Invoice.');
    }

    /**
     * Add an extra priced labor to a WO.
     * Allowed while WO has no invoice and no proforma.
     */
    public function addLabor(Request $request, WorkOrder $workOrder)
    {
        if (!PermissionHelper::canUpdate('work_orders')) {
            return PermissionHelper::denyAccess('work_orders', 'update');
        }

        if ($workOrder->status === 'invoiced') {
            return back()->with('error', 'Cannot add labor to an invoiced Work Order.');
        }

        if ($workOrder->proformaInvoice) {
            return back()->with('error', 'Cannot add labor: a Proforma Invoice has already been created for this Work Order.');
        }

        $validated = $request->validate([
            'labor_id'    => 'required|exists:labors,id',
            'qty'         => 'required|numeric|min:0.01',
            'rate'        => 'required|numeric|min:0',
            'remarks'     => 'nullable|string|max:255',
        ]);

        $labor = Labor::findOrFail($validated['labor_id']);
        $qty         = (float) $validated['qty'];
        $rate        = self::getLaborRateForTier($labor, $workOrder->vehicle_price_tier);
        $totalPrice  = $qty * $rate;

        WorkOrderLabor::create([
            'work_order_id' => $workOrder->id,
            'labor_id'      => $labor->id,
            'description'   => $labor->description,
            'qty'           => $qty,
            'rate'          => $rate,
            'total_price'   => $totalPrice,
            'remarks'       => $validated['remarks'] ?? null,
            'is_extra'      => true,
        ]);

        // Recalculate WO totals
        $workOrder->calculateTotals();

        return back()->with('success', 'Labor "' . $labor->description . '" (Rp ' . number_format($totalPrice, 0, ',', '.') . ') added to WO.');
    }

    /**
     * Remove a priced extra labor from a WO.
     * Same locking rules as addLabor.
     */
    public function removeLabor(WorkOrder $workOrder, WorkOrderLabor $labor)
    {
        if (!PermissionHelper::canUpdate('work_orders')) {
            return PermissionHelper::denyAccess('work_orders', 'update');
        }

        if ($workOrder->status === 'invoiced') {
            return back()->with('error', 'Cannot remove labor from an invoiced Work Order.');
        }

        if ($workOrder->proformaInvoice) {
            return back()->with('error', 'Cannot remove labor: a Proforma Invoice already exists.');
        }

        if ($labor->work_order_id !== $workOrder->id) {
            abort(403);
        }

        // Only allow removing extra labors (not base labors selected at WO creation)
        if (!$labor->is_extra) {
            return back()->with('error', 'Only extra labor items can be removed here. Use Edit WO to change the original labor list.');
        }

        $labor->delete();
        $workOrder->calculateTotals();

        return back()->with('success', 'Labor item removed.');
    }

    /**
     * Return the correct price for a Labor based on the vehicle price tier chosen by SA.
     * Falls back to the base price if tier columns are null.
     */
    public static function getLaborRateForTier(Labor $labor, ?string $tier): float
    {
        return match ($tier) {
            '0_300'    => (float) ($labor->price_0_300    ?? $labor->price ?? 0),
            '300_500'  => (float) ($labor->price_300_500  ?? $labor->price ?? 0),
            '500_800'  => (float) ($labor->price_500_800  ?? $labor->price ?? 0),
            '800_2000' => (float) ($labor->price_800_2000 ?? $labor->price ?? 0),
            default    => (float) ($labor->price ?? 0),
        };
    }

    /**
     * Return the correct price for a Panel based on the vehicle price tier chosen by SA.
     * Falls back to the base price if tier columns are null.
     */
    public static function getPanelRateForTier(Panel $panel, ?string $tier): float
    {
        return match ($tier) {
            '0_300'    => (float) ($panel->price_0_300    ?? $panel->price ?? 0),
            '300_500'  => (float) ($panel->price_300_500  ?? $panel->price ?? 0),
            '500_800'  => (float) ($panel->price_500_800  ?? $panel->price ?? 0),
            '800_2000' => (float) ($panel->price_800_2000 ?? $panel->price ?? 0),
            default    => (float) ($panel->price ?? 0),
        };
    }

    public function destroy(WorkOrder $workOrder)
    {
        if (!PermissionHelper::canDelete('work_orders')) {
            return PermissionHelper::denyAccess('work_orders', 'delete');
        }

        if ($workOrder->status !== 'on_progress') {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Only on progress Work Orders can be deleted.');
        }

        // No need to return stock — stock is only deducted when WO is started (in_progress)
        // on_progress WOs haven't had stock deducted yet

        $workOrder->delete();

        return redirect()->route('work_orders.index')->with('success', 'Work Order deleted.');
    }
}
