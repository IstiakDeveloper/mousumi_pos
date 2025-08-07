<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['customer', 'createdBy'])
            ->latest();

        // Filter by date range
        if ($request->filled(['start_date', 'end_date'])) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by invoice number
        if ($request->filled('invoice_no')) {
            $query->where('invoice_no', 'like', '%' . $request->invoice_no . '%');
        }

        // Get sales with pagination
        $sales = $query->paginate(10)
            ->withQueryString()
            ->through(function ($sale) {
                return [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'date' => $sale->created_at->format('d M, Y h:i A'),
                    'customer' => $sale->customer ? [
                        'id' => $sale->customer->id,
                        'name' => $sale->customer->name,
                        'phone' => $sale->customer->phone,
                    ] : null,
                    'subtotal' => $sale->subtotal,
                    'discount' => $sale->discount,
                    'total' => $sale->total,
                    'paid' => $sale->paid,
                    'due' => $sale->due,
                    'payment_status' => $sale->payment_status,
                    'created_by' => $sale->createdBy->name,
                ];
            });

        // Get all payment statuses for filter
        $paymentStatuses = Sale::distinct()
            ->pluck('payment_status');

        // Get active customers for filter
        $customers = Customer::active()
            ->select('id', 'name', 'phone')
            ->get();

        // Calculate summary
        $summary = [
            'total_sales' => $query->count(),
            'total_amount' => $query->sum('total'),
            'total_paid' => $query->sum('paid'),
            'total_due' => $query->sum('due'),
        ];

        return Inertia::render('Admin/Sales/Index', [
            'sales' => $sales,
            'filters' => $request->only(['start_date', 'end_date', 'payment_status', 'customer_id', 'invoice_no']),
            'paymentStatuses' => $paymentStatuses,
            'customers' => $customers,
            'summary' => $summary,
        ]);
    }

    public function show($id)
    {
        $sale = Sale::with([
            'customer',
            'saleItems.product',
            'createdBy'
        ])->findOrFail($id);

        return Inertia::render('Admin/Sales/Show', [
            'sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'date' => $sale->created_at->format('d M, Y h:i A'),
                'customer' => $sale->customer ? [
                    'id' => $sale->customer->id,
                    'name' => $sale->customer->name,
                    'phone' => $sale->customer->phone,
                    'address' => $sale->customer->address,
                ] : null,
                'items' => $sale->saleItems->map(fn($item) => [
                    'product' => [
                        'name' => $item->product->name,
                        'sku' => $item->product->sku,
                    ],
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ]),
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount,
                'total' => $sale->total,
                'paid' => $sale->paid,
                'due' => $sale->due,
                'payment_status' => $sale->payment_status,
                'note' => $sale->note,
                'created_by' => $sale->createdBy->name,
            ],
        ]);
    }


    public function edit($id)
    {
        try {
            $sale = Sale::with([
                'customer:id,name,phone,balance,credit_limit',
                'saleItems.product',
                'createdBy'
            ])->findOrFail($id);

            // Get active customers for selection
            $customers = Customer::active()
                ->select('id', 'name', 'phone', 'balance', 'credit_limit')
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'balance' => (string) $customer->balance,
                        'credit_limit' => (string) $customer->credit_limit,
                    ];
                });

            // Get bank accounts for payment
            $bankAccounts = BankAccount::active()
                ->select('id', 'bank_name', 'account_number', 'current_balance')
                ->get();

            return Inertia::render('Admin/Sales/Edit', [
                'sale' => [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'customer_id' => $sale->customer_id,
                    'customer' => $sale->customer ? [
                        'id' => $sale->customer->id,
                        'name' => $sale->customer->name,
                        'phone' => $sale->customer->phone,
                        'balance' => (string) $sale->customer->balance,
                        'credit_limit' => (string) $sale->customer->credit_limit,
                    ] : null,
                    'items' => $sale->saleItems->map(fn($item) => [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'sku' => $item->product->sku,
                            'selling_price' => (string) $item->product->selling_price,
                        ],
                        'quantity' => (string) $item->quantity,
                        'unit_price' => (string) $item->unit_price,
                        'subtotal' => (string) $item->subtotal,
                    ]),
                    'subtotal' => (string) $sale->subtotal,
                    'discount' => (string) $sale->discount,
                    'total' => (string) $sale->total,
                    'paid' => (string) $sale->paid,
                    'due' => (string) $sale->due,
                    'payment_status' => $sale->payment_status,
                    'note' => $sale->note,
                    'bank_account_id' => $sale->bank_account_id,
                ],
                'customers' => $customers,
                'bankAccounts' => $bankAccounts,
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Sale not found or cannot be edited.']);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'subtotal' => 'required|numeric|min:0',
                'discount' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'paid' => 'required|numeric|min:0',
                'customer_id' => 'nullable|exists:customers,id',
                'bank_account_id' => 'nullable|exists:bank_accounts,id',
                'note' => 'nullable|string|max:1000',
            ]);

            DB::beginTransaction();

            $sale = Sale::with(['saleItems', 'customer', 'bankAccount'])->findOrFail($id);

            // Store original values for reversal
            $originalCustomerId = $sale->customer_id;
            $originalPaid = $sale->paid;
            $originalDue = $sale->due;
            $originalBankAccountId = $sale->bank_account_id;

            // Step 1: Reverse all original stock movements
            foreach ($sale->saleItems as $item) {
                // Get current stock
                $currentStock = ProductStock::where('product_id', $item->product_id)
                    ->orderBy('id', 'desc')
                    ->first();

                $beforeQuantity = $currentStock ? $currentStock->available_quantity : 0;
                $afterQuantity = $beforeQuantity + $item->quantity; // Add back the sold quantity

                // Create new stock record to reverse the sale
                ProductStock::create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity, // positive for reversal
                    'available_quantity' => $afterQuantity,
                    'type' => 'sale_reversal',
                    'unit_cost' => $currentStock ? $currentStock->unit_cost : 0,
                    'total_cost' => $currentStock ? ($currentStock->unit_cost * $item->quantity) : 0,
                    'created_by' => auth()->id(),
                ]);

                // Record stock movement for reversal
                StockMovement::create([
                    'product_id' => $item->product_id,
                    'reference_type' => 'sale_update',
                    'reference_id' => $sale->id,
                    'quantity' => $item->quantity,
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $afterQuantity,
                    'type' => 'in', // Stock coming back in
                    'created_by' => auth()->id(),
                ]);
            }

            // Step 2: Reverse customer balance if there was due amount
            if ($originalCustomerId && $originalDue > 0) {
                $originalCustomer = Customer::find($originalCustomerId);
                if ($originalCustomer) {
                    $originalCustomer->decrement('balance', $originalDue);
                }
            }

            // Step 3: Reverse bank transaction if there was payment
            if ($originalPaid > 0 && $originalBankAccountId) {
                $originalBankAccount = BankAccount::find($originalBankAccountId);
                if ($originalBankAccount) {
                    $originalBankAccount->transactions()->create([
                        'transaction_type' => 'out',
                        'amount' => $originalPaid,
                        'date' => now(),
                        'description' => "Reversed payment for updated invoice {$sale->invoice_no}",
                        'created_by' => auth()->id(),
                    ]);

                    $originalBankAccount->decrement('current_balance', $originalPaid);
                }
            }

            // Step 4: Delete old sale items
            $sale->saleItems()->delete();

            // Step 5: Calculate new values
            $due = $request->total - $request->paid;
            $paymentStatus = $request->paid >= $request->total ? 'paid' : ($request->paid > 0 ? 'partial' : 'due');

            // Step 6: Process new items and check stock
            foreach ($request->items as $item) {
                // Check stock availability for new quantity
                $currentStock = ProductStock::where('product_id', $item['product_id'])
                    ->orderBy('id', 'desc')
                    ->first();

                $beforeQuantity = $currentStock ? $currentStock->available_quantity : 0;
                $afterQuantity = $beforeQuantity - $item['quantity'];

                // Check if stock is available
                if ($afterQuantity < 0) {
                    throw new \Exception("Insufficient stock for product ID: {$item['product_id']}, Available: {$beforeQuantity}, Requested: {$item['quantity']}");
                }

                // Create new sale item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                // Create new stock record
                ProductStock::create([
                    'product_id' => $item['product_id'],
                    'quantity' => -$item['quantity'], // negative for sales
                    'available_quantity' => $afterQuantity,
                    'type' => 'sale',
                    'unit_cost' => $currentStock ? $currentStock->unit_cost : 0,
                    'total_cost' => $currentStock ? ($currentStock->unit_cost * $item['quantity']) : 0,
                    'created_by' => auth()->id(),
                ]);

                // Record stock movement
                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'quantity' => $item['quantity'],
                    'before_quantity' => $beforeQuantity,
                    'after_quantity' => $afterQuantity,
                    'type' => 'out',
                    'created_by' => auth()->id(),
                ]);
            }

            // Step 7: Update customer balance with new due amount
            if ($request->customer_id && $due > 0) {
                $customer = Customer::findOrFail($request->customer_id);

                // Check credit limit
                $newBalance = $customer->balance + $due;
                if ($newBalance > $customer->credit_limit) {
                    throw new \Exception("This sale would exceed the customer's credit limit. Current balance: {$customer->balance}, Credit limit: {$customer->credit_limit}, New due: {$due}");
                }

                $customer->increment('balance', $due);
            }

            // Step 8: Handle payment difference
            $paymentDifference = $request->paid - $originalPaid;

            if ($paymentDifference != 0 && $request->bank_account_id) {
                $bankAccount = BankAccount::findOrFail($request->bank_account_id);

                if ($paymentDifference > 0) {
                    // Payment increased - add to bank
                    $bankAccount->transactions()->create([
                        'transaction_type' => 'in',
                        'amount' => $paymentDifference,
                        'date' => now(),
                        'description' => "Additional payment for updated invoice {$sale->invoice_no}",
                        'created_by' => auth()->id(),
                    ]);

                    $bankAccount->increment('current_balance', $paymentDifference);
                } else {
                    // Payment decreased - deduct from bank
                    $decreaseAmount = abs($paymentDifference);

                    $bankAccount->transactions()->create([
                        'transaction_type' => 'out',
                        'amount' => $decreaseAmount,
                        'date' => now(),
                        'description' => "Payment reduction for updated invoice {$sale->invoice_no}",
                        'created_by' => auth()->id(),
                    ]);

                    $bankAccount->decrement('current_balance', $decreaseAmount);
                }
            } else if ($request->paid > 0 && $request->bank_account_id && $originalPaid == 0) {
                // First time payment
                $bankAccount = BankAccount::findOrFail($request->bank_account_id);
                $bankAccount->transactions()->create([
                    'transaction_type' => 'in',
                    'amount' => $request->paid,
                    'date' => now(),
                    'description' => "Payment received for updated invoice {$sale->invoice_no}",
                    'created_by' => auth()->id(),
                ]);

                $bankAccount->increment('current_balance', $request->paid);
            }

            // Step 9: Update sale record
            $sale->update([
                'customer_id' => $request->customer_id,
                'subtotal' => $request->subtotal,
                'discount' => $request->discount,
                'total' => $request->total,
                'paid' => $request->paid,
                'due' => $due,
                'payment_status' => $paymentStatus,
                'note' => $request->note,
                'bank_account_id' => $request->bank_account_id,
                'updated_by' => auth()->id(),
            ]);

            DB::commit();

            return redirect()->route('admin.sales.show', $sale->id)
                ->with('success', 'Sale updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $sale = Sale::with(['saleItems', 'customer'])->findOrFail($id);

            // Check if sale is within 7 days
            if ($sale->created_at->diffInDays(now()) > 7) {
                return response()->json([
                    'success' => false,
                    'massage' => 'Sales older than 7 days cannot be deleted.' // Changed 'message' to 'massage'
                ], 403);
            }

            // Restore product stock
            foreach ($sale->saleItems as $item) {
                $stock = ProductStock::firstOrCreate([
                    'product_id' => $item->product_id
                ]);
                $stock->increment('quantity', $item->quantity);
            }

            // Update customer balance if there was due amount
            if ($sale->customer && $sale->due > 0) {
                $sale->customer->decrement('balance', $sale->due);
            }

            // Reverse bank transaction if payment was made
            if ($sale->paid > 0 && $sale->bank_account_id) {
                $bankAccount = BankAccount::findOrFail($sale->bank_account_id);
                $bankAccount->decrement('current_balance', $sale->paid);

                // Create reverse transaction record
                $bankAccount->transactions()->create([
                    'transaction_type' => 'withdrawal',
                    'amount' => $sale->paid,
                    'date' => now(),
                    'description' => "Reversed payment for deleted invoice {$sale->invoice_no}",
                    'created_by' => auth()->id(),
                ]);
            }

            // Delete sale and its items
            $sale->delete();

            DB::commit();

            session()->flash('success', 'Sale deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'massage' => 'Error deleting sale: ' . $e->getMessage() // Changed 'message' to 'massage'
            ], 500);
        }
    }

    public function printReceipt($id)
    {
        // Fetch the sale with related data, including product categories
        $sale = Sale::with([
            'saleItems.product.category', // Include product category relationship
            'customer',
            'createdBy',
            'bankAccount'
        ])->findOrFail($id);

        // Group items by category
        $itemsByCategory = $sale->saleItems->groupBy(function ($item) {
            return $item->product->category->name ?? 'Uncategorized';
        });

        // Calculate category subtotals
        $categoryTotals = [];
        foreach ($itemsByCategory as $category => $items) {
            $categoryTotals[$category] = $items->sum('subtotal');
        }

        $pdf = PDF::loadView('admin.sales.receipt', [
            'sale' => $sale,
            'itemsByCategory' => $itemsByCategory, // Added this line
            'categoryTotals' => $categoryTotals,
            'company' => [
                'name' => config('app.name'),
                'address' => config('app.address'),
                'phone' => config('app.phone'),
                'email' => config('app.email'),
                'logo' => public_path('images/logo.png'),
            ]
        ]);

        // Set paper size for 80mm receipt (width: 80mm)
        $pdf->setPaper([0, 0, 226.772, 841.89], 'portrait');

        return $pdf->stream("receipt-{$sale->invoice_no}.pdf");
    }
}
