<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\ExtraIncome;
use App\Models\ExtraIncomeCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExtraIncomeController extends Controller
{
    public function index(Request $request)
    {
        $query = ExtraIncome::with(['bankAccount', 'category', 'createdBy'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->bank_account, function ($query, $bankAccount) {
                $query->where('bank_account_id', $bankAccount);
            })
            ->when($request->category, function ($query, $category) {
                $query->where('category_id', $category);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            });

        if ($request->sort) {
            [$column, $direction] = explode('-', $request->sort);
            $query->orderBy($column, $direction);
        } else {
            $query->latest('date');
        }

        // Category-wise statistics with proper null handling
        $categoryStats = ExtraIncome::select(
            DB::raw('COALESCE(categories.name, "Uncategorized") as category_name'),
            DB::raw('SUM(extra_incomes.amount) as total_amount'),
            DB::raw('COUNT(*) as transaction_count')
        )
            ->leftJoin('extra_income_categories as categories', 'extra_incomes.category_id', '=', 'categories.id')
            ->groupBy(DB::raw('COALESCE(categories.id, 0)'), DB::raw('COALESCE(categories.name, "Uncategorized")'))
            ->get()
            ->map(function ($stat) {
                return [
                    'category' => $stat->category_name,
                    'total_amount' => $stat->total_amount,
                    'transaction_count' => $stat->transaction_count,
                ];
            });

        $statistics = [
            'totalIncome' => ExtraIncome::sum('amount'),
            'thisMonthIncome' => ExtraIncome::whereMonth('date', now()->month)
                ->whereYear('date', now()->year)
                ->sum('amount'),
            'averageIncome' => ExtraIncome::avg('amount'),
            'totalTransactions' => ExtraIncome::count(),
            'categoryWise' => $categoryStats,
        ];

        return Inertia::render('Admin/ExtraIncome/Index', [
            'extraIncomes' => $query->paginate(10)->withQueryString(),
            'bankAccounts' => BankAccount::where('status', true)->get(),
            'categories' => ExtraIncomeCategory::where('status', true)->get(),
            'filters' => $request->only(['search', 'bank_account', 'category', 'date_from', 'date_to']),
            'statistics' => $statistics,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/ExtraIncome/Create', [
            'bankAccounts' => BankAccount::where('status', true)->get(),
            'categories' => ExtraIncomeCategory::where('status', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'category_id' => 'nullable|exists:extra_income_categories,id',
            'amount' => 'required|numeric|min:0',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated) {
            // Create extra income record
            $extraIncome = ExtraIncome::create([
                ...$validated,
                'created_by' => auth()->id(),
            ]);

            // Get category name for description
            $categoryName = $extraIncome->category ? $extraIncome->category->name : 'Uncategorized';

            // Create bank transaction
            BankTransaction::create([
                'bank_account_id' => $validated['bank_account_id'],
                'transaction_type' => 'in',
                'amount' => $validated['amount'],
                'description' => "Extra Income ({$categoryName}): {$validated['title']}",
                'date' => $validated['date'],
                'created_by' => auth()->id(),
            ]);

            // Update bank account balance
            $bankAccount = BankAccount::find($validated['bank_account_id']);
            $bankAccount->current_balance += $validated['amount'];
            $bankAccount->save();
        });

        return redirect()->route('admin.extra-incomes.index')
            ->with('success', 'Extra income added successfully');
    }

    public function edit(ExtraIncome $extraIncome)
    {
        return Inertia::render('Admin/ExtraIncome/Edit', [
            'extraIncome' => $extraIncome->load(['bankAccount', 'category']),
            'bankAccounts' => BankAccount::where('status', true)->get(),
            'categories' => ExtraIncomeCategory::where('status', true)->get(),
        ]);
    }

    public function update(Request $request, ExtraIncome $extraIncome)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'category_id' => 'nullable|exists:extra_income_categories,id',
            'amount' => 'required|numeric|min:0',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated, $extraIncome) {
            // Update bank account balance (reverse previous transaction)
            $oldBankAccount = BankAccount::find($extraIncome->bank_account_id);
            $oldBankAccount->current_balance -= $extraIncome->amount;
            $oldBankAccount->save();

            // Update extra income
            $extraIncome->update($validated);

            // Get updated category name
            $categoryName = $extraIncome->category ? $extraIncome->category->name : 'Uncategorized';

            // Update bank transaction
            BankTransaction::where([
                'bank_account_id' => $extraIncome->getOriginal('bank_account_id'),
                'amount' => $extraIncome->getOriginal('amount'),
                'date' => $extraIncome->getOriginal('date'),
            ])->update([
                'bank_account_id' => $validated['bank_account_id'],
                'amount' => $validated['amount'],
                'description' => "Extra Income ({$categoryName}): {$validated['title']}",
                'date' => $validated['date'],
            ]);

            // Update new bank account balance
            $newBankAccount = BankAccount::find($validated['bank_account_id']);
            $newBankAccount->current_balance += $validated['amount'];
            $newBankAccount->save();
        });

        return redirect()->route('admin.extra-incomes.index')
            ->with('success', 'Extra income updated successfully');
    }

    public function destroy(ExtraIncome $extraIncome)
    {
        DB::transaction(function () use ($extraIncome) {
            // Update bank account balance
            $bankAccount = BankAccount::find($extraIncome->bank_account_id);
            $bankAccount->current_balance -= $extraIncome->amount;
            $bankAccount->save();

            // Delete related bank transaction
            BankTransaction::where([
                'bank_account_id' => $extraIncome->bank_account_id,
                'amount' => $extraIncome->amount,
                'date' => $extraIncome->date,
            ])->delete();

            // Delete extra income
            $extraIncome->delete();
        });

        return redirect()->route('admin.extra-incomes.index')
            ->with('success', 'Extra income deleted successfully');
    }
}
