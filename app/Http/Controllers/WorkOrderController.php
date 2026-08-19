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
use App\Models\Insurance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $masterLabors = Labor::where('is_active', true)->orderBy('labor_code')->get();
        $insurances   = Insurance::where('is_active', true)->orderBy('name')->get();

        return view('work_orders.create', compact('customers', 'items', 'completedWos', 'masterLabors', 'insurances'));
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('work_orders')) {
            return PermissionHelper::denyAccess('work_orders', 'create');
        }

        $validated = $request->validate([
            'wo_number'            => 'nullable|string|max:50|unique:work_orders,wo_number',
            'customer_id'          => 'required|exists:customers,id',
            'billing_customer_id'  => 'nullable|exists:customers,id',
            'vehicle_id'           => 'nullable|exists:vehicles,id',
            'account_code'         => 'required|in:C,INT_WS,INT_W3,ASURANSI',
            'insurance_id'         => 'required_if:account_code,ASURANSI|nullable|exists:insurances,id',
            'reference_wo_id'      => 'nullable|exists:work_orders,id',
            'work_date'            => 'required|date',
            'deadline'             => 'nullable|date',
            'vehicle_info'         => 'nullable|string|max:200',
            'vehicle_merk'         => 'nullable|string|max:100',
            'vehicle_type_year'    => 'nullable|string|max:100',
            'vehicle_color'        => 'nullable|string|max:100',
            'vehicle_plate'        => 'nullable|string|max:100',
            'vehicle_km'           => 'nullable|integer|min:0',
            'vehicle_price_tier'   => 'nullable|in:0_300,300_500,500_800,800_2000',
            'chasis_no'            => 'nullable|string|max:100',
            'description'          => 'nullable|string',
            'notes'                => 'nullable|string',
            'sa_sales'             => 'nullable|string|max:100',
            'items'                => 'nullable|array',
            'items.*.item_id'      => 'nullable|exists:items,id',
            'items.*.demand_quantity' => 'required|numeric|min:0.01',
            'items.*.remark'       => 'nullable|string|max:255',
            'labors'               => 'nullable|array',
            'labors.*.labor_id'    => 'nullable|exists:labors,id',
            'labors.*.qty'         => 'nullable|numeric|min:0.01',
            'labors.*.rate'        => 'nullable|numeric|min:0',
            'labors.*.remarks'     => 'nullable|string',
            'labors.*.is_three_coat'     => 'nullable|boolean',
            'labors.*.is_special_repair' => 'nullable|boolean',
        ]);

        $priceTier = $validated['vehicle_price_tier'] ?? null;

        // Use manual WO number if provided; otherwise auto-generate: YYMM/HAS/SEQ (monthly reset)
        $woNumber = $validated['wo_number'] ?? null;
        if (empty($woNumber)) {
            $yy = date('y');
            $mm = date('m');
            $prefix = $yy . $mm . '/HAS/';
            $lastWO = WorkOrder::where('wo_number', 'like', $prefix . '%')
                ->orderBy('id', 'desc')
                ->first();
            $nextNumber = $lastWO ? intval(substr($lastWO->wo_number, -3)) + 1 : 1;
            $woNumber = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }

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
                    'color'        => $validated['vehicle_color'] ?? null,
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
            'insurance_id'      => $validated['insurance_id'] ?? null,
            'work_date'         => $validated['work_date'],
            'deadline'          => $validated['deadline'] ?? null,
            'vehicle_info'      => $validated['vehicle_info'] ?? null,
            'vehicle_merk'      => $validated['vehicle_merk'] ?? null,
            'vehicle_type_year' => $validated['vehicle_type_year'] ?? null,
            'vehicle_color'     => $validated['vehicle_color'] ?? null,
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
            'created_by'        => Auth::id(),
        ]);

        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                if (empty($itemData['item_id'])) {
                    continue;
                }
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

        if (!empty($validated['labors'])) {
            foreach ($validated['labors'] as $laborData) {
                if (empty($laborData['labor_id'])) {
                    continue;
                }
                $labor = Labor::findOrFail($laborData['labor_id']);
                $qty = (float) ($laborData['qty'] ?? 1);
                $isThreeCoat = (bool) ($laborData['is_three_coat'] ?? false);
                $isSpecialRepair = (bool) ($laborData['is_special_repair'] ?? false);
                if (isset($laborData['rate']) && $laborData['rate'] !== '') {
                    $rate = (float) $laborData['rate'];
                } else {
                    $rate = self::getLaborRateForTier($labor, $priceTier);
                    $rate = self::applyPanelSurcharges($rate, $isThreeCoat, $isSpecialRepair);
                }
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
                    'is_three_coat'     => $isThreeCoat,
                    'is_special_repair' => $isSpecialRepair,
                ]);
            }
        }

        $wo->calculateTotals();

        return redirect()->route('work_orders.index')->with('success', 'Work Order created successfully!');
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['customer', 'billingCustomer', 'creator', 'items.item.smallestUom', 'items.uom', 'labors.labor', 'labors.panel', 'referenceWo', 'invoice', 'invoices.creditNote', 'bonOuts', 'proformaInvoice', 'estimasis', 'activeEstimasi']);

        return view('work_orders.show', compact('workOrder'));
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

        $insurances   = Insurance::where('is_active', true)->orderBy('name')->get();
        $masterLabors = Labor::where('is_active', true)->orderBy('labor_code')->get();

        return view('work_orders.edit', compact('workOrder', 'customers', 'items', 'completedWos', 'masterLabors', 'insurances'));
    }

    public function update(Request $request, WorkOrder $workOrder)
    {
        if (!in_array($workOrder->status, ['on_progress', 'in_progress'])) {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Only on progress or in progress Work Orders can be edited.');
        }

        $validated = $request->validate([
            'wo_number'            => 'required|string|max:50|unique:work_orders,wo_number,' . $workOrder->id,
            'customer_id'          => 'required|exists:customers,id',
            'billing_customer_id'  => 'nullable|exists:customers,id',
            'vehicle_id'           => 'nullable|exists:vehicles,id',
            'account_code'         => 'required|in:C,INT_WS,INT_W3,ASURANSI',
            'work_date'            => 'required|date',
            'deadline'             => 'nullable|date',
            'vehicle_info'         => 'nullable|string|max:200',
            'vehicle_merk'         => 'nullable|string|max:100',
            'vehicle_type_year'    => 'nullable|string|max:100',
            'vehicle_color'        => 'nullable|string|max:100',
            'vehicle_plate'        => 'nullable|string|max:20',
            'vehicle_km'           => 'nullable|integer|min:0',
            'vehicle_price_tier'   => 'nullable|in:0_300,300_500,500_800,800_2000',
            'chasis_no'            => 'nullable|string|max:100',
            'description'          => 'nullable|string',
            'notes'                => 'nullable|string',
            'sa_sales'             => 'nullable|string|max:100',
            'reference_wo_id'      => 'nullable|exists:work_orders,id',
            'items'             => 'nullable|array',
            'items.*.item_id'   => 'nullable|exists:items,id',
            'items.*.demand_quantity' => 'required|numeric|min:0.01',
            'items.*.remark'    => 'nullable|string|max:255',
            'labors'                  => 'nullable|array',
            'labors.*.labor_id'       => 'nullable|exists:labors,id',
            'labors.*.qty'            => 'nullable|numeric|min:0.01',
            'labors.*.rate'           => 'nullable|numeric|min:0',
            'labors.*.remarks'        => 'nullable|string',
            'labors.*.is_three_coat'     => 'nullable|boolean',
            'labors.*.is_special_repair' => 'nullable|boolean',
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
                    'color'        => $validated['vehicle_color'] ?? null,
                    'chasis_no'    => $validated['chasis_no'] ?? null,
                    'is_active'    => true,
                ]);
                $vehicleId = $newVehicle->id;
            }
        }

        $workOrder->update([
            'wo_number'            => $validated['wo_number'],
            'customer_id'          => $validated['customer_id'],
            'billing_customer_id'  => $validated['billing_customer_id'] ?? null,
            'vehicle_id'           => $vehicleId,
            'account_code'      => $validated['account_code'],
            'insurance_id'      => $validated['insurance_id'] ?? null,
            'work_date'         => $validated['work_date'],
            'deadline'          => $validated['deadline'] ?? null,
            'vehicle_info'      => $validated['vehicle_info'] ?? null,
            'vehicle_merk'      => $validated['vehicle_merk'] ?? null,
            'vehicle_type_year' => $validated['vehicle_type_year'] ?? null,
            'vehicle_color'     => $validated['vehicle_color'] ?? null,
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
                if (empty($itemData['item_id'])) {
                    continue;
                }
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

        // Add new labors
        if (!empty($validated['labors'])) {
            foreach ($validated['labors'] as $laborData) {
                if (empty($laborData['labor_id'])) {
                    continue;
                }
                $labor = Labor::findOrFail($laborData['labor_id']);
                $qty = (float) ($laborData['qty'] ?? 1);
                $isThreeCoat = (bool) ($laborData['is_three_coat'] ?? false);
                $isSpecialRepair = (bool) ($laborData['is_special_repair'] ?? false);
                if (isset($laborData['rate']) && $laborData['rate'] !== '') {
                    $rate = (float) $laborData['rate'];
                } else {
                    $rate = self::getLaborRateForTier($labor, $priceTier);
                    $rate = self::applyPanelSurcharges($rate, $isThreeCoat, $isSpecialRepair);
                }
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
                    'is_three_coat'     => $isThreeCoat,
                    'is_special_repair' => $isSpecialRepair,
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

        // if (!$workOrder->bonOuts()->exists()) {
        //     return redirect()->route('work_orders.show', $workOrder)
        //         ->with('error', 'Cannot complete Work Order: at least one Bon Out is required before completing.');
        // }

        if ($workOrder->hasIncompleteBonOuts()) {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Cannot complete Work Order: there are Bon Out(s) still in progress. Please complete all Bon Outs first.');
        }

        $workOrder->update(['status' => 'completed', 'completed_at' => now()]);

        return redirect()->route('work_orders.show', $workOrder)
            ->with('success', 'Work Order completed successfully. Finance can now create the Invoice.');
    }

    public function cancel(Request $request, WorkOrder $workOrder)
    {
        if (!PermissionHelper::canUpdate('work_orders')) {
            return PermissionHelper::denyAccess('work_orders', 'update');
        }

        if ($workOrder->status !== 'on_progress') {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Only pending Work Orders can request cancellation.');
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:1000',
        ]);

        $workOrder->update([
            'status'              => 'pending_cancellation',
            'cancellation_reason' => $validated['cancellation_reason'],
        ]);

        return redirect()->route('work_orders.show', $workOrder)
            ->with('success', 'Cancellation request submitted. Awaiting Sigit approval.');
    }

    public function approveCancel(WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'pending_cancellation') {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'This Work Order is not pending cancellation.');
        }

        $user  = Auth::user();
        $sigit = User::where('name', 'like', '%Sigit%')->first();

        if (!$sigit || $user->id !== $sigit->id) {
            return redirect()->route('work_orders.show', $workOrder)
                ->with('error', 'Only Sigit can approve this cancellation.');
        }

        $workOrder->update(['status' => 'cancelled']);

        return redirect()->route('work_orders.show', $workOrder)
            ->with('success', 'Work Order cancellation approved.');
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
        $rate        = (float) $validated['rate'];
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

    /** Flat surcharge added to the panel rate when "Three Coat/Candy" is ticked. */
    public const THREE_COAT_SURCHARGE = 1250000.0;

    /** Multiplier applied to the panel rate when "Special Repair" is ticked. */
    public const SPECIAL_REPAIR_MULTIPLIER = 1.5;

    /**
     * Apply the "Three Coat/Candy" (+Rp 1.250.000) and "Special Repair" (x1.5)
     * surcharges chosen by the Service Advisor for a panel to its base rate.
     */
    public static function applyPanelSurcharges(float $rate, bool $isThreeCoat, bool $isSpecialRepair): float
    {
        if ($isSpecialRepair) {
            $rate *= self::SPECIAL_REPAIR_MULTIPLIER;
        }
        if ($isThreeCoat) {
            $rate += self::THREE_COAT_SURCHARGE;
        }
        return $rate;
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
