<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $units = Unit::query()
            ->when($request->search, function($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
            })
            ->when($request->status !== null, function($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Units/Index', [
            'units' => $units,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:units',
                'short_name' => 'required|string|max:50|unique:units',
                'status' => 'boolean'
            ]);

            Unit::create($validated);

            return redirect()->back()->with('success', 'Unit created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create unit: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Unit $unit)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:units,name,' . $unit->id,
                'short_name' => 'required|string|max:50|unique:units,short_name,' . $unit->id,
                'status' => 'boolean'
            ]);

            $unit->update($validated);

            return redirect()->back()->with('success', 'Unit updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update unit: ' . $e->getMessage());
        }
    }

    public function destroy(Unit $unit)
    {
        try {
            if ($unit->products()->exists()) {
                return redirect()->back()->with('error', 'Cannot delete unit as it is being used by products.');
            }

            $unit->delete();

            return redirect()->back()->with('success', 'Unit deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete unit: ' . $e->getMessage());
        }
    }
}
