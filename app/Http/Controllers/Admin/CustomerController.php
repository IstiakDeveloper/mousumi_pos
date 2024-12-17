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
            ->through(fn ($customer) => [
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
        $customer->load(['sales' => function ($query) {
            $query->latest()->with('saleItems.product');
        }]);

        // Get recent payments
        $recentPayments = SalePayment::with(['sale', 'bankAccount', 'createdBy'])
            ->whereHas('sale', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);
            })
            ->latest()
            ->take(5)
            ->get();

        // Sales summary
        $salesSummary = Sale::where('customer_id', $customer->id)
            ->selectRaw('
                COUNT(*) as total_invoices,
                SUM(total) as total_amount,
                SUM(paid) as total_paid,
                SUM(due) as total_due,
                COUNT(CASE WHEN payment_status = "paid" THEN 1 END) as paid_invoices,
                COUNT(CASE WHEN payment_status = "partial" THEN 1 END) as partial_invoices,
                COUNT(CASE WHEN payment_status = "due" THEN 1 END) as due_invoices
            ')
            ->first();

        // Monthly sales chart data
        $monthlySales = Sale::where('customer_id', $customer->id)
            ->whereBetween('created_at', [now()->subMonths(11), now()])
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                SUM(total) as total_amount,
                SUM(paid) as paid_amount,
                COUNT(*) as invoice_count
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('Admin/Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'address' => $customer->address,
                'credit_limit' => $customer->credit_limit,
                'balance' => $customer->balance,
                'points' => $customer->points,
                'status' => $customer->status,
                'created_at' => $customer->created_at->format('d M, Y')
            ],
            'sales' => $customer->sales->map(fn ($sale) => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'date' => $sale->created_at->format('d M, Y'),
                'total' => $sale->total,
                'paid' => $sale->paid,
                'due' => $sale->due,
                'payment_status' => $sale->payment_status,
                'items_count' => $sale->saleItems->count(),
                'items' => $sale->saleItems->map(fn ($item) => [
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal
                ])
            ]),
            'recent_payments' => $recentPayments->map(fn ($payment) => [
                'id' => $payment->id,
                'date' => $payment->created_at->format('d M, Y'),
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'invoice_no' => $payment->sale->invoice_no,
                'bank_account' => $payment->bankAccount ? $payment->bankAccount->bank_name : null,
                'created_by' => $payment->createdBy->name
            ]),
            'sales_summary' => $salesSummary,
            'monthly_sales' => $monthlySales,
        ]);
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
            'payment_method' => 'required|in:cash,card,bank,mobile_banking',
            'bank_account_id' => 'required_if:payment_method,bank,card|exists:bank_accounts,id',
            'transaction_id' => 'nullable|string',
            'note' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Get the sale
            $sale = Sale::findOrFail($validatedData['sale_id']);

            // Check if payment amount is valid
            if ($validatedData['amount'] > $sale->due) {
                throw new \Exception('Payment amount cannot be greater than due amount.');
            }

            // Create sale payment
            $payment = SalePayment::create([
                'sale_id' => $sale->id,
                'amount' => $validatedData['amount'],
                'payment_method' => $validatedData['payment_method'],
                'bank_account_id' => $validatedData['bank_account_id'] ?? null,
                'transaction_id' => $validatedData['transaction_id'],
                'note' => $validatedData['note'],
                'created_by' => auth()->id()
            ]);

            // Update sale
            $sale->increment('paid', $validatedData['amount']);
            $sale->decrement('due', $validatedData['amount']);
            $sale->payment_status = $sale->due > 0 ? 'partial' : 'paid';
            $sale->save();

            // Update customer balance
            $customer->decrement('balance', $validatedData['amount']);

            // Create bank transaction if payment is by bank or card
            if (in_array($validatedData['payment_method'], ['bank', 'card']) && isset($validatedData['bank_account_id'])) {
                $bankAccount = BankAccount::findOrFail($validatedData['bank_account_id']);

                $bankAccount->transactions()->create([
                    'transaction_type' => 'deposit',
                    'amount' => $validatedData['amount'],
                    'description' => "Payment received for invoice {$sale->invoice_no}",
                    'date' => now(),
                    'created_by' => auth()->id()
                ]);

                $bankAccount->increment('current_balance', $validatedData['amount']);
            }

            DB::commit();

            return back()->with('success', 'Payment added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
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
