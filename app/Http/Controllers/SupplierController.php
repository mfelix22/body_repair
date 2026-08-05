<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!PermissionHelper::canView('suppliers')) {
            return PermissionHelper::denyAccess('suppliers', 'view');
        }

        $suppliers = Supplier::withCount('purchaseOrders')->latest()->get();
        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!PermissionHelper::canCreate('suppliers')) {
            return PermissionHelper::denyAccess('suppliers', 'create');
        }

        return view('suppliers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('suppliers')) {
            return PermissionHelper::denyAccess('suppliers', 'create');
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'contact_person'    => 'nullable|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'required|string|max:50',
            'address'           => 'required|string',
            'city'              => 'nullable|string|max:100',
            'postal_code'       => 'nullable|string|max:20',
            'npwp'              => 'nullable|string|max:50',
            'website'           => 'nullable|string|max:255',
            'bank_name'         => 'nullable|string|max:255',
            'bank_account_no'   => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
            'notes'             => 'nullable|string',
        ]);

        Supplier::create($validated);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        if (!PermissionHelper::canView('suppliers')) {
            return PermissionHelper::denyAccess('suppliers', 'view');
        }

        $supplier->load(['purchaseOrders' => function ($query) {
            $query->latest()->limit(10);
        }]);

        return view('suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        if (!PermissionHelper::canUpdate('suppliers')) {
            return PermissionHelper::denyAccess('suppliers', 'update');
        }

        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        if (!PermissionHelper::canUpdate('suppliers')) {
            return PermissionHelper::denyAccess('suppliers', 'update');
        }

        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'contact_person'    => 'nullable|string|max:255',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:50',
            'address'           => 'nullable|string',
            'city'              => 'nullable|string|max:100',
            'postal_code'       => 'nullable|string|max:20',
            'npwp'              => 'nullable|string|max:50',
            'website'           => 'nullable|string|max:255',
            'bank_name'         => 'nullable|string|max:255',
            'bank_account_no'   => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:255',
            'notes'             => 'nullable|string',
        ]);

        $supplier->update($validated);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        if (!PermissionHelper::canDelete('suppliers')) {
            return PermissionHelper::denyAccess('suppliers', 'delete');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}
