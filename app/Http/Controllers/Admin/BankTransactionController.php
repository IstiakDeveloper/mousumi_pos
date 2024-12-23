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

        // Calculate summary statistics
        $summary = [
            'available_balance' => BankAccount::sum('current_balance'),
            'total_loans' => BankTransaction::where('transaction_type', 'loan_taken')->sum('amount'),
            'total_loan_paid' => BankTransaction::where('transaction_type', 'loan_payment')->sum('amount'),
            'due_balance' => BankTransaction::where('transaction_type', 'loan_taken')->sum('amount') -
                BankTransaction::where('transaction_type', 'loan_payment')->sum('amount'),
        ];

        return Inertia::render('Admin/BankTransactions/Index', [
            'bankTransactions' => $bankTransactions,
            'summary' => $summary,
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
            'transaction_type' => 'required|in:deposit,withdrawal,transfer,loan_taken,loan_payment',
            'amount' => 'required|numeric',
            'description' => 'nullable',
            'date' => 'required|date',
        ]);

        $bankAccount = BankAccount::findOrFail($validatedData['bank_account_id']);

        DB::transaction(function () use ($validatedData, $bankAccount) {
            $transaction = BankTransaction::create(array_merge(
                $validatedData,
                ['created_by' => auth()->id()]
            ));

            switch ($transaction->transaction_type) {
                case 'deposit':
                case 'loan_taken':
                    $bankAccount->current_balance += $transaction->amount;
                    break;
                case 'withdrawal':
                case 'loan_payment':
                    $bankAccount->current_balance -= $transaction->amount;
                    break;
            }

            $bankAccount->save();
        });

        return redirect()->route('admin.bank-transactions.index')
            ->with('success', 'Bank transaction created successfully.');
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
            'transaction_type' => 'required|in:deposit,withdrawal,transfer,loan_taken,loan_payment',
            'amount' => 'required|numeric',
            'description' => 'nullable',
            'date' => 'required|date',
        ]);

        $bankAccount = BankAccount::findOrFail($validatedData['bank_account_id']);

        DB::transaction(function () use ($validatedData, $bankTransaction, $bankAccount) {
            // Revert the previous transaction
            switch ($bankTransaction->transaction_type) {
                case 'deposit':
                case 'loan_taken':
                    $bankAccount->current_balance -= $bankTransaction->amount;
                    break;
                case 'withdrawal':
                case 'loan_payment':
                    $bankAccount->current_balance += $bankTransaction->amount;
                    break;
            }

            // Apply the new transaction
            switch ($validatedData['transaction_type']) {
                case 'deposit':
                case 'loan_taken':
                    $bankAccount->current_balance += $validatedData['amount'];
                    break;
                case 'withdrawal':
                case 'loan_payment':
                    $bankAccount->current_balance -= $validatedData['amount'];
                    break;
            }

            $bankAccount->save();
            $bankTransaction->update($validatedData);
        });

        return redirect()->route('admin.bank-transactions.index')
            ->with('success', 'Bank transaction updated successfully.');
    }

    public function destroy(BankTransaction $bankTransaction)
    {
        $bankTransaction->delete();
        return redirect()->route('admin.bank-transactions.index')->with('success', 'Bank transaction deleted successfully.');
    }
}
