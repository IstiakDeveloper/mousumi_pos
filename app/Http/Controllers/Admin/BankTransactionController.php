<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BankTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = BankTransaction::with(['bankAccount', 'createdBy']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhereHas('bankAccount', function ($q) use ($search) {
                        $q->where('account_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by bank account
        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        // Sorting
        $sortField = $request->input('sort_field', 'date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $bankTransactions = $query->paginate($perPage)->appends($request->query());

        // Summary statistics
        $summary = [
            'available_balance' => BankAccount::sum('current_balance'),
            'total_in' => BankTransaction::where('transaction_type', 'in')->sum('amount'),
            'total_out' => BankTransaction::where('transaction_type', 'out')->sum('amount'),
        ];

        // Bank accounts for filter dropdown
        $bankAccounts = BankAccount::select('id', 'account_name')->get();

        return Inertia::render('Admin/BankTransactions/Index', [
            'bankTransactions' => $bankTransactions,
            'summary' => $summary,
            'bankAccounts' => $bankAccounts,
            'filters' => $request->only(['search', 'transaction_type', 'bank_account_id', 'date_from', 'date_to', 'sort_field', 'sort_order', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'date' => 'required|date|before_or_equal:today',
        ]);

        // Convert date to Carbon timestamp for proper created_at setting
        $transactionDate = \Carbon\Carbon::parse($validated['date'])->startOfDay();

        // Create transaction with manual timestamp
        $transaction = new BankTransaction([
            'bank_account_id' => $validated['bank_account_id'],
            'transaction_type' => $validated['transaction_type'],
            'amount' => $validated['amount'],
            'description' => $validated['description'] ?? null,
            'date' => $validated['date'],
            'created_by' => Auth::id(),
        ]);

        $transaction->created_at = $transactionDate;
        $transaction->updated_at = $transactionDate;
        $transaction->save();

        // Observer will handle running_balance and current_balance updates

        return redirect()->route('admin.bank-transactions.index')
            ->with('success', 'Bank transaction created successfully.');
    }

    public function update(Request $request, BankTransaction $bankTransaction)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:500',
            'date' => 'required|date|before_or_equal:today',
        ]);

        // Convert date to Carbon timestamp
        $transactionDate = \Carbon\Carbon::parse($validated['date'])->startOfDay();

        // Update transaction data
        $bankTransaction->bank_account_id = $validated['bank_account_id'];
        $bankTransaction->transaction_type = $validated['transaction_type'];
        $bankTransaction->amount = $validated['amount'];
        $bankTransaction->description = $validated['description'] ?? null;
        $bankTransaction->date = $validated['date'];
        $bankTransaction->created_at = $transactionDate;
        $bankTransaction->updated_at = $transactionDate;
        $bankTransaction->save();

        // Observer will handle balance updates

        return redirect()->route('admin.bank-transactions.index')
            ->with('success', 'Bank transaction updated successfully.');
    }

    public function create()
    {
        $bankAccounts = BankAccount::all();

        return Inertia::render('Admin/BankTransactions/Create', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function edit(BankTransaction $bankTransaction)
    {
        $bankAccounts = BankAccount::all();

        return Inertia::render('Admin/BankTransactions/Edit', [
            'bankTransaction' => $bankTransaction,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function destroy(BankTransaction $bankTransaction)
    {
        try {
            // Observer will handle balance updates
            $bankTransaction->delete();

            return redirect()->route('admin.bank-transactions.index')
                ->with('success', 'Bank transaction deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('admin.bank-transactions.index')
                ->with('error', 'Error deleting transaction: '.$e->getMessage());
        }
    }
}
