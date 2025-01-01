<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\SalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()
            ->withSum('sales as total_sales', 'total')
            ->withSum('sales as total_due', 'due');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->latest()
            ->paginate(10)
            ->through(fn($customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'total_sales' => $customer->total_sales ?? 0,
                'total_due' => $customer->total_due ?? 0,
                'status' => $customer->status,
                'created_at' => $customer->created_at->format('d M, Y')
            ]);

        return Inertia::render('Admin/Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function show(Customer $customer)
    {
        try {
            // Get sales with payment status
            $sales = Sale::where('customer_id', $customer->id)
                ->with(['saleItems.product'])
                ->latest()
                ->get()
                ->map(function ($sale) {
                    return [
                        'id' => $sale->id,
                        'invoice_no' => $sale->invoice_no,
                        'date' => $sale->created_at->format('d M, Y'),
                        'total' => number_format($sale->total, 2, '.', ''),
                        'paid' => number_format($sale->paid, 2, '.', ''),
                        'due' => number_format($sale->due, 2, '.', ''),
                        'payment_status' => $sale->payment_status,
                    ];
                });

            // Get recent payments
            $recentPayments = SalePayment::whereHas('sale', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);
            })
                ->with(['sale', 'bankAccount'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'date' => $payment->created_at->format('d M, Y'),
                        'amount' => number_format($payment->amount, 2, '.', ''),
                        'invoice_no' => $payment->sale->invoice_no,
                        'bank_account' => optional($payment->bankAccount)->bank_name
                    ];
                });

            // Calculate sales summary
            $salesSummary = Sale::where('customer_id', $customer->id)
                ->selectRaw('
                    COUNT(*) as total_invoices,
                    COALESCE(SUM(total), 0) as total_amount,
                    COALESCE(SUM(paid), 0) as total_paid,
                    COALESCE(SUM(due), 0) as total_due
                ')
                ->first();

            // Get active bank accounts
            $bankAccounts = BankAccount::where('status', true)
                ->select('id', 'account_name', 'bank_name', 'current_balance')
                ->get();

            return Inertia::render('Admin/Customers/Show', [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'address' => $customer->address,
                    'credit_limit' => number_format($customer->credit_limit, 2, '.', ''),
                    'balance' => number_format($customer->balance, 2, '.', ''),
                    'status' => $customer->status,
                ],
                'sales' => $sales,
                'recent_payments' => $recentPayments,
                'sales_summary' => [
                    'total_invoices' => $salesSummary->total_invoices ?? 0,
                    'total_amount' => number_format($salesSummary->total_amount ?? 0, 2, '.', ''),
                    'total_paid' => number_format($salesSummary->total_paid ?? 0, 2, '.', ''),
                    'total_due' => number_format($salesSummary->total_due ?? 0, 2, '.', '')
                ],
                'bankAccounts' => $bankAccounts
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in customer show: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while loading customer details.');
        }
    }
    public function create()
    {
        return Inertia::render('Admin/Customers/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers',
            'phone' => 'required|string|unique:customers',
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'boolean'
        ]);

        // Set default values
        $validatedData['balance'] = 0;
        $validatedData['points'] = 0;

        Customer::create($validatedData);

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Customer $customer)
    {
        return Inertia::render('Admin/Customers/Edit', [
            'customer' => $customer
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'phone' => 'required|string|unique:customers,phone,' . $customer->id,
            'address' => 'nullable|string',
            'credit_limit' => 'nullable|numeric|min:0',
            'status' => 'boolean'
        ]);

        $customer->update($validatedData);

        return redirect()
            ->route('admin.customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    public function addPayment(Request $request, Customer $customer)
    {
        $validatedData = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:0.01',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'note' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Get the sale and bank account
            $sale = Sale::findOrFail($validatedData['sale_id']);
            $bankAccount = BankAccount::findOrFail($validatedData['bank_account_id']);

            // Check if payment amount is valid
            if ($validatedData['amount'] > $sale->due) {
                throw new \Exception('Payment amount cannot be greater than due amount.');
            }

            // Create sale payment
            $payment = SalePayment::create([
                'sale_id' => $sale->id,
                'amount' => $validatedData['amount'],
                'payment_method' => 'bank', // Always bank
                'bank_account_id' => $bankAccount->id,
                'note' => $validatedData['note'],
                'created_by' => auth()->id()
            ]);

            // Update sale amounts and status
            $sale->increment('paid', $validatedData['amount']);
            $sale->decrement('due', $validatedData['amount']);
            $sale->payment_status = $sale->due <= 0 ? 'paid' : 'partial';
            $sale->save();

            // Update customer balance
            $customer->decrement('balance', $validatedData['amount']);

            // Create bank transaction and update balance
            $transaction = $bankAccount->transactions()->create([
                'transaction_type' => 'in',
                'amount' => $validatedData['amount'],
                'description' => "Payment received for invoice {$sale->invoice_no} from customer {$customer->name}",
                'date' => now(),
                'created_by' => auth()->id()
            ]);

            // Update bank account balance
            $bankAccount->current_balance += $validatedData['amount'];
            $bankAccount->save();

            DB::commit();

            return back()->with('success', 'Payment added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payment Error: ' . $e->getMessage());
            return back()->with('error', 'Error adding payment: ' . $e->getMessage());
        }
    }

    public function destroy(Customer $customer)
    {
        // Check if customer has any sales
        if ($customer->sales()->exists()) {
            return back()->with('error', 'Cannot delete customer with existing sales.');
        }

        $customer->delete();
        return back()->with('success', 'Customer deleted successfully.');
    }

    public function toggleStatus(Customer $customer)
    {
        $customer->update(['status' => !$customer->status]);
        return back()->with('success', 'Customer status updated successfully.');
    }
}
