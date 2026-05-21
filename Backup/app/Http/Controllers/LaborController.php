<?php

namespace App\Http\Controllers;

use App\Helpers\PermissionHelper;
use App\Models\Labor;
use Illuminate\Http\Request;

class LaborController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::canView('labors')) {
            return PermissionHelper::denyAccess('labors', 'view');
        }
        $labors = Labor::orderBy('labor_code')->get();
        return view('labors.index', compact('labors'));
    }

    public function create()
    {
        if (!PermissionHelper::canCreate('labors')) {
            return PermissionHelper::denyAccess('labors', 'create');
        }
        return view('labors.create');
    }

    public function store(Request $request)
    {
        if (!PermissionHelper::canCreate('labors')) {
            return PermissionHelper::denyAccess('labors', 'create');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        // Auto-generate labor code: LAB-0001
        $last = Labor::orderBy('id', 'desc')->first();
        $nextSeq = $last ? (int) substr($last->labor_code, 4) + 1 : 1;
        $laborCode = 'LAB-' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        Labor::create([
            'labor_code'  => $laborCode,
            'description' => $validated['description'],
            'price'       => $validated['price'],
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('labors.index')
            ->with('success', "Labor {$laborCode} created.");
    }

    public function edit(Labor $labor)
    {
        if (!PermissionHelper::canUpdate('labors')) {
            return PermissionHelper::denyAccess('labors', 'update');
        }
        return view('labors.edit', compact('labor'));
    }

    public function update(Request $request, Labor $labor)
    {
        if (!PermissionHelper::canUpdate('labors')) {
            return PermissionHelper::denyAccess('labors', 'update');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'is_active'   => 'nullable|boolean',
        ]);

        $labor->update([
            'description' => $validated['description'],
            'price'       => $validated['price'],
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('labors.index')
            ->with('success', "Labor {$labor->labor_code} updated.");
    }

    public function destroy(Labor $labor)
    {
        if (!PermissionHelper::canDelete('labors')) {
            return PermissionHelper::denyAccess('labors', 'delete');
        }

        $labor->delete();

        return redirect()->route('labors.index')
            ->with('success', 'Labor deleted.');
    }
}
