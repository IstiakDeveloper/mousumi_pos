<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::with(['category', 'bankAccount', 'createdBy'])
            ->when($request->category_id, fn($q) => $q->where('expense_category_id', $request->category_id))
            ->when($request->bank_id, fn($q) => $q->where('bank_account_id', $request->bank_id))
            ->when($request->from_date, fn($q) => $q->whereDate('date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('date', '<=', $request->to_date))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => ExpenseCategory::where('status', true)->get(),
            'bankAccounts' => BankAccount::where('status', true)->get(),
            'filters' => $request->only(['category_id', 'bank_id', 'from_date', 'to_date'])
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'date' => 'required|date',
            'attachment' => 'nullable|file|max:2048'
        ]);

        DB::beginTransaction();
        try {
            // Handle file upload if exists
            if ($request->hasFile('attachment')) {
                $validated['attachment'] = $request->file('attachment')
                    ->store('expenses/attachments', 'public');
            }

            $validated['created_by'] = auth()->id();

            // Create expense
            $expense = Expense::create($validated);

            // Create bank transaction
            BankTransaction::create([
                'bank_account_id' => $validated['bank_account_id'],
                'transaction_type' => 'out',
                'amount' => $validated['amount'],
                'description' => "Expense: {$validated['description']}",
                'date' => $validated['date'],
                'created_by' => auth()->id()
            ]);

            // Update bank balance
            $bankAccount = BankAccount::find($validated['bank_account_id']);
            $bankAccount->current_balance -= $validated['amount'];
            $bankAccount->save();

            DB::commit();
            return redirect()->back()->with('success', 'Expense created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to create expense');
        }
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'reference_no' => 'nullable|string',
            'date' => 'required|date',
            'attachment' => 'nullable|file|max:2048'
        ]);

        DB::beginTransaction();
        try {
            $oldAmount = $expense->amount;
            $oldBankAccountId = $expense->bank_account_id;

            // Handle file upload if exists
            if ($request->hasFile('attachment')) {
                // Delete old attachment if exists
                if ($expense->attachment) {
                    Storage::disk('public')->delete($expense->attachment);
                }
                $validated['attachment'] = $request->file('attachment')
                    ->store('expenses/attachments', 'public');
            }

            // Update expense
            $expense->update($validated);

            // Update bank transactions and balances
            if ($oldBankAccountId !== $validated['bank_account_id']) {
                // Restore old bank account balance
                $oldBank = BankAccount::find($oldBankAccountId);
                $oldBank->current_balance += $oldAmount;
                $oldBank->save();

                // Update new bank account balance
                $newBank = BankAccount::find($validated['bank_account_id']);
                $newBank->current_balance -= $validated['amount'];
                $newBank->save();

                // Create new transaction for new bank
                BankTransaction::create([
                    'bank_account_id' => $validated['bank_account_id'],
                    'transaction_type' => 'out',
                    'amount' => $validated['amount'],
                    'description' => "Expense: {$validated['description']}",
                    'date' => $validated['date'],
                    'created_by' => auth()->id()
                ]);
            } else if ($oldAmount !== $validated['amount']) {
                // Update existing bank balance
                $bank = BankAccount::find($validated['bank_account_id']);
                $bank->current_balance += $oldAmount - $validated['amount'];
                $bank->save();
            }

            DB::commit();
            return redirect()->back()->with('success', 'Expense updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update expense');
        }
    }

    public function destroy(Expense $expense)
    {
        DB::beginTransaction();
        try {
            // Restore bank balance
            $bankAccount = BankAccount::find($expense->bank_account_id);
            $bankAccount->current_balance += $expense->amount;
            $bankAccount->save();

            // Soft delete expense
            $expense->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Expense deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete expense');
        }
    }

    public function restore($id)
    {
        DB::beginTransaction();
        try {
            $expense = Expense::withTrashed()->findOrFail($id);

            // Update bank balance
            $bankAccount = BankAccount::find($expense->bank_account_id);
            $bankAccount->current_balance -= $expense->amount;
            $bankAccount->save();

            // Restore expense
            $expense->restore();

            DB::commit();
            return redirect()->back()->with('success', 'Expense restored successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to restore expense');
        }
    }
}
