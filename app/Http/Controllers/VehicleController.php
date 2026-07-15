<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Vehicle;
use App\Models\Customer;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('vehicles')) {
            return PermissionHelper::denyAccess('vehicles', 'view');
        }

        $vehicles = Vehicle::with('customer')
            ->orderBy('plate_number')
            ->get();
        return view('vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('vehicles')) {
            return PermissionHelper::denyAccess('vehicles', 'create');
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        return view('vehicles.create', compact('customers'));
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('vehicles')) {
            return PermissionHelper::denyAccess('vehicles', 'create');
        }

        $validated = $request->validate([
            'customer_id'  => 'required|exists:customers,id',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'brand'        => 'nullable|string|max:100',
            'model'        => 'nullable|string|max:100',
            'year'         => 'nullable|string|max:10',
            'color'        => 'nullable|string|max:50',
            'chasis_no'    => 'nullable|string|max:100',
            'notes'        => 'nullable|string',
        ]);

        $vehicle = Vehicle::create($validated);

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Vehicle registered successfully.');
    }

    public function show(Vehicle $vehicle)
    {
        if (!PermissionHelper::canView('vehicles')) {
            return PermissionHelper::denyAccess('vehicles', 'view');
        }

        $vehicle->load('customer');

        $workOrders = \App\Models\WorkOrder::with(['invoice', 'labors.labor'])
            ->where(function ($q) use ($vehicle) {
                $q->where('vehicle_id', $vehicle->id)
                    ->orWhere(function ($q2) use ($vehicle) {
                        $q2->whereNull('vehicle_id')
                            ->where('vehicle_plate', $vehicle->plate_number);
                    });
            })
            ->orderBy('work_date', 'desc')
            ->get();

        $vehicle->setRelation('workOrders', $workOrders);

        $stats = [
            'total_services'     => $vehicle->workOrders->count(),
            'completed_services' => $vehicle->workOrders->whereIn('status', ['completed', 'invoiced'])->count(),
            'active_work_orders' => $vehicle->workOrders->whereIn('status', ['on_progress', 'in_progress'])->count(),
            'total_billed'       => $vehicle->workOrders->sum(fn($wo) => $wo->invoice?->grand_total ?? $wo->grand_total ?? 0),
            'last_service_date'  => $vehicle->workOrders->whereIn('status', ['completed', 'invoiced'])->first()?->work_date,
        ];

        return view('vehicles.show', compact('vehicle', 'stats'));
    }

    public function edit(Vehicle $vehicle)
    {
        if (!PermissionHelper::canUpdate('vehicles')) {
            return PermissionHelper::denyAccess('vehicles', 'update');
        }

        $customers = Customer::where('is_active', true)->orderBy('name')->get();
        return view('vehicles.edit', compact('vehicle', 'customers'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        if (!PermissionHelper::canUpdate('vehicles')) {
            return PermissionHelper::denyAccess('vehicles', 'update');
        }

        $validated = $request->validate([
            'customer_id'  => 'required|exists:customers,id',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,' . $vehicle->id,
            'brand'        => 'nullable|string|max:100',
            'model'        => 'nullable|string|max:100',
            'year'         => 'nullable|string|max:10',
            'color'        => 'nullable|string|max:50',
            'chasis_no'    => 'nullable|string|max:100',
            'notes'        => 'nullable|string',
            'is_active'    => 'nullable|boolean',
        ]);

        $vehicle->update($validated);

        return redirect()->route('vehicles.show', $vehicle)
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Vehicle $vehicle)
    {
        if (!PermissionHelper::canDelete('vehicles')) {
            return PermissionHelper::denyAccess('vehicles', 'delete');
        }

        $vehicle->delete();
        return redirect()->route('vehicles.index')
            ->with('success', 'Vehicle deleted.');
    }

    /**
     * API endpoint: return vehicles belonging to a customer (for WO create dropdown)
     */
    public function byCustomer(Customer $customer)
    {
        $vehicles = $customer->vehicles()
            ->where('is_active', true)
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'brand', 'model', 'year', 'color', 'chasis_no']);

        return response()->json($vehicles);
    }
}
