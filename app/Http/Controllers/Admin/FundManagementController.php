<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Fund;
use DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FundManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Fund::with(['bankAccount', 'createdBy'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('from_who', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->bank_account, function ($query, $bankAccount) {
                $query->where('bank_account_id', $bankAccount);
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            });

        $statistics = [
            'totalFundsIn' => Fund::where('type', 'in')->sum('amount'),
            'totalFundsOut' => Fund::where('type', 'out')->sum('amount'),
            'netFunds' => Fund::where('type', 'in')->sum('amount') - Fund::where('type', 'out')->sum('amount'),
            'totalTransactions' => Fund::count(),
        ];

        return Inertia::render('Admin/FundManagement/Index', [
            'funds' => $query->latest('date')->paginate(10)->withQueryString(),
            'bankAccounts' => BankAccount::where('status', true)->get(),
            'filters' => $request->only(['search', 'bank_account', 'type', 'date_from', 'date_to']),
            'statistics' => $statistics,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/FundManagement/Create', [
            'bankAccounts' => BankAccount::where('status', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0',
            'from_who' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated) {
            // Create fund record
            $fund = Fund::create([
                ...$validated,
                'created_by' => auth()->id(),
            ]);

            // Create bank transaction
            BankTransaction::create([
                'bank_account_id' => $validated['bank_account_id'],
                'transaction_type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => "Fund {$validated['type']}: {$validated['from_who']}",
                'date' => $validated['date'],
                'created_by' => auth()->id(),
            ]);

            // Update bank account balance
            $bankAccount = BankAccount::find($validated['bank_account_id']);
            if ($validated['type'] === 'in') {
                $bankAccount->current_balance += $validated['amount'];
            } else {
                $bankAccount->current_balance -= $validated['amount'];
            }
            $bankAccount->save();
        });

        return redirect()->route('admin.funds.index')
            ->with('success', 'Fund transaction recorded successfully');
    }

    public function edit(Fund $fund)
    {
        return Inertia::render('Admin/FundManagement/Edit', [
            'fund' => $fund->load('bankAccount'),
            'bankAccounts' => BankAccount::where('status', true)->get(),
        ]);
    }

    public function update(Request $request, Fund $fund)
    {
        $validated = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0',
            'from_who' => 'required|string|max:255',
            'description' => 'nullable|string',
            'date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated, $fund) {
            // Revert previous transaction from bank balance
            $oldBankAccount = BankAccount::find($fund->bank_account_id);
            if ($fund->type === 'in') {
                $oldBankAccount->current_balance -= $fund->amount;
            } else {
                $oldBankAccount->current_balance += $fund->amount;
            }
            $oldBankAccount->save();

            // Update fund record
            $fund->update($validated);

            // Update bank transaction
            BankTransaction::where([
                'bank_account_id' => $fund->getOriginal('bank_account_id'),
                'amount' => $fund->getOriginal('amount'),
                'date' => $fund->getOriginal('date'),
            ])->update([
                'bank_account_id' => $validated['bank_account_id'],
                'transaction_type' => $validated['type'],
                'amount' => $validated['amount'],
                'description' => "Fund {$validated['type']}: {$validated['from_who']}",
                'date' => $validated['date'],
            ]);

            // Apply new transaction to bank balance
            $newBankAccount = BankAccount::find($validated['bank_account_id']);
            if ($validated['type'] === 'in') {
                $newBankAccount->current_balance += $validated['amount'];
            } else {
                $newBankAccount->current_balance -= $validated['amount'];
            }
            $newBankAccount->save();
        });

        return redirect()->route('admin.funds.index')
            ->with('success', 'Fund transaction updated successfully');
    }

    public function destroy(Fund $fund)
    {
        DB::transaction(function () use ($fund) {
            // Update bank account balance
            $bankAccount = BankAccount::find($fund->bank_account_id);
            if ($fund->type === 'in') {
                $bankAccount->current_balance -= $fund->amount;
            } else {
                $bankAccount->current_balance += $fund->amount;
            }
            $bankAccount->save();

            // Delete related bank transaction
            BankTransaction::where([
                'bank_account_id' => $fund->bank_account_id,
                'amount' => $fund->amount,
                'date' => $fund->date,
            ])->delete();

            // Delete fund record
            $fund->delete();
        });

        return redirect()->route('admin.funds.index')
            ->with('success', 'Fund transaction deleted successfully');
    }
}
