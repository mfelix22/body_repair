<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\UOM;
use App\Models\UOMConversion;
use Illuminate\Http\Request;

class UOMController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('uoms')) {
            return PermissionHelper::denyAccess('uoms', 'view');
        }

        $uoms = UOM::withCount(['conversionsFrom', 'conversionsTo'])->get();
        return view('uoms.index', compact('uoms'));
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('uoms')) {
            return PermissionHelper::denyAccess('uoms', 'create');
        }

        return view('uoms.create');
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('uoms')) {
            return PermissionHelper::denyAccess('uoms', 'create');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:uoms',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        UOM::create($validated);

        return redirect()->route('uoms.index')->with('success', 'UOM created successfully!');
    }

    public function show(UOM $uom)
    {
        if (!PermissionHelper::canView('uoms')) {
            return PermissionHelper::denyAccess('uoms', 'view');
        }

        $uom->load(['conversionsFrom.toUom', 'conversionsTo.fromUom']);
        return view('uoms.show', compact('uom'));
    }

    public function edit(UOM $uom)
    {
        if (!PermissionHelper::canUpdate('uoms')) {
            return PermissionHelper::denyAccess('uoms', 'update');
        }

        return view('uoms.edit', compact('uom'));
    }

    public function update(Request $request, UOM $uom)
    {
        if (!PermissionHelper::canUpdate('uoms')) {
            return PermissionHelper::denyAccess('uoms', 'update');
        }

        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:uoms,code,' . $uom->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $uom->update($validated);

        return redirect()->route('uoms.index')->with('success', 'UOM updated successfully!');
    }

    public function destroy(UOM $uom)
    {
        if (!PermissionHelper::canDelete('uoms')) {
            return PermissionHelper::denyAccess('uoms', 'delete');
        }

        try {
            $uom->delete();
            return redirect()->route('uoms.index')->with('success', 'UOM deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->route('uoms.index')->with('error', 'Cannot delete UOM. It may be in use.');
        }
    }

    public function conversions()
    {
        $conversions = UOMConversion::with(['fromUom', 'toUom'])->get();
        $uoms = UOM::where('is_active', true)->get();
        return view('uoms.conversions', compact('conversions', 'uoms'));
    }

    public function storeConversion(Request $request)
    {
        $validated = $request->validate([
            'from_uom_id' => 'required|exists:uoms,id',
            'to_uom_id' => 'required|exists:uoms,id|different:from_uom_id',
            'conversion_factor' => 'required|numeric|min:0.000001',
        ]);

        UOMConversion::updateOrCreate(
            ['from_uom_id' => $validated['from_uom_id'], 'to_uom_id' => $validated['to_uom_id']],
            ['conversion_factor' => $validated['conversion_factor']]
        );

        return redirect()->route('uoms.conversions')->with('success', 'Conversion created successfully!');
    }

    public function destroyConversion(UOMConversion $conversion)
    {
        try {
            $conversion->delete();
            return redirect()->route('uoms.conversions')->with('success', 'Conversion deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->route('uoms.conversions')->with('error', 'Cannot delete conversion.');
        }
    }
}
