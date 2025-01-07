<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Inertia\Inertia;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $bankAccounts = BankAccount::all();
        return Inertia::render('Admin/BankAccounts/Index', [
            'bankAccounts' => $bankAccounts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/BankAccounts/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'account_name' => 'required',
            'account_number' => 'required',
            'bank_name' => 'required',
            'branch_name' => 'nullable',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'required|numeric',
            'status' => 'boolean',
        ]);

        BankAccount::create($validatedData);

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account created successfully.');
    }

    public function edit(BankAccount $bankAccount)
    {
        return Inertia::render('Admin/BankAccounts/Edit', [
            'bankAccount' => $bankAccount,
        ]);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validatedData = $request->validate([
            'account_name' => 'required',
            'account_number' => 'required',
            'bank_name' => 'required',
            'branch_name' => 'nullable',
            'opening_balance' => 'required|numeric',
            'current_balance' => 'required|numeric',
            'status' => 'boolean',
        ]);

        $bankAccount->update($validatedData);

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account updated successfully.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();
        return redirect()->route('admin.bank-accounts.index')->with('success', 'Bank account deleted successfully.');
    }
}
