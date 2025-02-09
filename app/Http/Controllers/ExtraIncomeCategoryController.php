<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExtraIncomeCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExtraIncomeCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ExtraIncomeCategory::with(['createdBy'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->status !== null, function ($query) use ($request) {
                $query->where('status', $request->status);
            });

        if ($request->sort) {
            [$column, $direction] = explode('-', $request->sort);
            $query->orderBy($column, $direction);
        } else {
            $query->latest();
        }

        return Inertia::render('Admin/ExtraIncomeCategory/Index', [
            'categories' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/ExtraIncomeCategory/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:extra_income_categories',
            'description' => 'nullable|string',
            'status' => 'boolean'
        ]);

        ExtraIncomeCategory::create([
            ...$validated,
            'created_by' => auth()->id()
        ]);

        return redirect()->route('admin.extra-income-categories.index')
            ->with('success', 'Category created successfully');
    }

    public function edit(ExtraIncomeCategory $category)
    {
        return Inertia::render('Admin/ExtraIncomeCategory/Edit', [
            'category' => $category
        ]);
    }

    public function update(Request $request, ExtraIncomeCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:extra_income_categories,name,' . $category->id,
            'description' => 'nullable|string',
            'status' => 'boolean'
        ]);

        $category->update($validated);

        return redirect()->route('admin.extra-income-categories.index')
            ->with('success', 'Category updated successfully');
    }

    public function destroy(ExtraIncomeCategory $category)
    {
        if ($category->extraIncomes()->exists()) {
            return back()->with('error', 'Cannot delete category that has extra incomes');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully');
    }
}
