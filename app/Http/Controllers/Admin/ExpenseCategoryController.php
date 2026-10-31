<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::latest()->paginate(10);

        return Inertia::render('Admin/ExpenseCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        if ($this->isReservedAssetName($request->input('name'))) {
            return redirect()->back()->withErrors([
                'name' => 'Fixed assets are recorded under Fixed Assets, not as an expense category.',
            ])->withInput();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        ExpenseCategory::create($validated);

        return redirect()->back()->with('success', 'Category created successfully');
    }

    public function update(Request $request, ExpenseCategory $category)
    {
        if ($this->isReservedAssetName($request->input('name'))) {
            return redirect()->back()->withErrors([
                'name' => 'Fixed assets are recorded under Fixed Assets, not as an expense category.',
            ])->withInput();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,'.$category->id,
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', 'Category updated successfully');
    }

    public function destroy(ExpenseCategory $category)
    {
        // Check if category is being used by any expenses
        if ($category->expenses()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete category as it is being used by expenses');
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully');
    }

    private function isReservedAssetName(?string $name): bool
    {
        return $name !== null && preg_match('/fixed\s*asset|স্থায়ী\s*সম্পদ/ui', $name) === 1;
    }
}
