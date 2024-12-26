<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BankTransactionController extends Controller
{
    public function index()
    {
        $bankTransactions = BankTransaction::with('bankAccount')->get();

        // Simplified summary statistics for in/out only
        $summary = [
            'available_balance' => BankAccount::sum('current_balance'),
            'total_in' => BankTransaction::where('transaction_type', 'in')->sum('amount'),
            'total_out' => BankTransaction::where('transaction_type', 'out')->sum('amount'),
        ];

        return Inertia::render('Admin/BankTransactions/Index', [
            'bankTransactions' => $bankTransactions,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_type' => 'required|in:in,out',  // Modified to only allow in/out
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable',
            'date' => 'required|date',
        ]);

        $bankAccount = BankAccount::findOrFail($validatedData['bank_account_id']);

        DB::transaction(function () use ($validatedData, $bankAccount) {
            $transaction = BankTransaction::create(array_merge(
                $validatedData,
                ['created_by' => auth()->id()]
            ));

            // Simplified logic for in/out
            if ($transaction->transaction_type === 'in') {
                $bankAccount->current_balance += $transaction->amount;
            } else {
                $bankAccount->current_balance -= $transaction->amount;
            }

            $bankAccount->save();
        });

        return redirect()->route('admin.bank-transactions.index')
            ->with('success', 'Bank transaction created successfully.');
    }

    public function update(Request $request, BankTransaction $bankTransaction)
    {
        $validatedData = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_type' => 'required|in:in,out',  // Modified to only allow in/out
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable',
            'date' => 'required|date',
        ]);

        $bankAccount = BankAccount::findOrFail($validatedData['bank_account_id']);

        DB::transaction(function () use ($validatedData, $bankTransaction, $bankAccount) {
            // Revert the previous transaction
            if ($bankTransaction->transaction_type === 'in') {
                $bankAccount->current_balance -= $bankTransaction->amount;
            } else {
                $bankAccount->current_balance += $bankTransaction->amount;
            }

            // Apply the new transaction
            if ($validatedData['transaction_type'] === 'in') {
                $bankAccount->current_balance += $validatedData['amount'];
            } else {
                $bankAccount->current_balance -= $validatedData['amount'];
            }

            $bankAccount->save();
            $bankTransaction->update($validatedData);
        });

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
            DB::beginTransaction();

            // Get the associated bank account
            $bankAccount = BankAccount::findOrFail($bankTransaction->bank_account_id);

            // Reverse the transaction effect on bank balance
            if ($bankTransaction->transaction_type === 'in') {
                // If it was an 'in' transaction, subtract the amount
                $bankAccount->current_balance -= $bankTransaction->amount;
            } else {
                // If it was an 'out' transaction, add the amount back
                $bankAccount->current_balance += $bankTransaction->amount;
            }

            // Save the updated bank balance
            $bankAccount->save();

            // Delete the transaction
            $bankTransaction->delete();

            DB::commit();

            return redirect()->route('admin.bank-transactions.index')
                ->with('success', 'Bank transaction deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.bank-transactions.index')
                ->with('error', 'Error deleting transaction: ' . $e->getMessage());
        }
    }
}
