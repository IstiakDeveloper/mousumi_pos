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
        return Inertia::render('Admin/BankTransactions/Index', [
            'bankTransactions' => $bankTransactions,
        ]);
    }

    public function create()
    {
        $bankAccounts = BankAccount::all();
        return Inertia::render('Admin/BankTransactions/Create', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_type' => 'required|in:deposit,withdrawal,transfer',
            'amount' => 'required|numeric',
            'description' => 'nullable',
            'date' => 'required|date',
        ]);

        $bankAccount = BankAccount::findOrFail($validatedData['bank_account_id']);

        DB::transaction(function () use ($validatedData, $bankAccount) {
            $transaction = BankTransaction::create($validatedData);

            if ($transaction->transaction_type === 'deposit') {
                $bankAccount->current_balance += $transaction->amount;
            } elseif ($transaction->transaction_type === 'withdrawal') {
                $bankAccount->current_balance -= $transaction->amount;
            }

            $bankAccount->save();
        });

        return redirect()->route('admin.bank-transactions.index')->with('success', 'Bank transaction created successfully.');
    }

    public function edit(BankTransaction $bankTransaction)
    {
        $bankAccounts = BankAccount::all();
        return Inertia::render('Admin/BankTransactions/Edit', [
            'bankTransaction' => $bankTransaction,
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function update(Request $request, BankTransaction $bankTransaction)
    {
        $validatedData = $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'transaction_type' => 'required|in:deposit,withdrawal,transfer',
            'amount' => 'required|numeric',
            'description' => 'nullable',
            'date' => 'required|date',
        ]);

        $bankAccount = BankAccount::findOrFail($validatedData['bank_account_id']);

        DB::transaction(function () use ($validatedData, $bankTransaction, $bankAccount) {
            // Revert the previous transaction
            if ($bankTransaction->transaction_type === 'deposit') {
                $bankAccount->current_balance -= $bankTransaction->amount;
            } elseif ($bankTransaction->transaction_type === 'withdrawal') {
                $bankAccount->current_balance += $bankTransaction->amount;
            }

            // Apply the new transaction
            if ($validatedData['transaction_type'] === 'deposit') {
                $bankAccount->current_balance += $validatedData['amount'];
            } elseif ($validatedData['transaction_type'] === 'withdrawal') {
                $bankAccount->current_balance -= $validatedData['amount'];
            }

            $bankAccount->save();
            $bankTransaction->update($validatedData);
        });

        return redirect()->route('admin.bank-transactions.index')->with('success', 'Bank transaction updated successfully.');
    }

    public function destroy(BankTransaction $bankTransaction)
    {
        $bankTransaction->delete();
        return redirect()->route('admin.bank-transactions.index')->with('success', 'Bank transaction deleted successfully.');
    }
}
