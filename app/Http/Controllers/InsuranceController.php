<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use Illuminate\Http\Request;

class InsuranceController extends Controller
{
    public function index()
    {
        $insurances = Insurance::orderBy('name')->get();
        return view('insurances.index', compact('insurances'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        Insurance::create([
            'name'      => $validated['name'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('insurances.index')->with('success', 'Insurance created successfully.');
    }

    public function edit(Insurance $insurance)
    {
        return view('insurances.edit', compact('insurance'));
    }

    public function update(Request $request, Insurance $insurance)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $insurance->update([
            'name'      => $validated['name'],
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('insurances.index')->with('success', 'Insurance updated successfully.');
    }

    public function destroy(Insurance $insurance)
    {
        $insurance->delete();
        return redirect()->route('insurances.index')->with('success', 'Insurance deleted successfully.');
    }
}
