<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleController extends Controller
{
    public function index()
    {
        $sales = Sale::with('customer', 'items', 'payments')->latest()->get();
        return Inertia::render('Admin/Sales/Index', compact('sales'));
    }

    public function create()
    {
        $customers = Customer::all();
        $products = Product::with('variants')->get();
        return Inertia::render('Admin/Sales/Create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.payment_method' => 'required|in:cash,card,bank,mobile_banking',
            'payments.*.bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payments.*.transaction_id' => 'nullable|string',
            'payments.*.note' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $sale = DB::transaction(function () use ($validatedData) {
            $sale = Sale::create([
                'invoice_no' => Sale::generateInvoiceNumber(),
                'customer_id' => $validatedData['customer_id'],
                'subtotal' => $this->calculateSubtotal($validatedData['items']),
                'tax' => $validatedData['tax'],
                'discount' => $validatedData['discount'],
                'total' => $this->calculateTotal($validatedData['items'], $validatedData['tax'], $validatedData['discount']),
                'paid' => $this->calculateTotalPayments($validatedData['payments'] ?? []),
                'payment_status' => 'due',
                'note' => $validatedData['note'],
                'created_by' => auth()->id(),
            ]);

            foreach ($validatedData['items'] as $itemData) {
                $sale->items()->create($itemData);
                $this->updateProductStock($itemData['product_id'], $itemData['quantity']);
            }

            foreach ($validatedData['payments'] ?? [] as $paymentData) {
                $sale->payments()->create($paymentData + ['created_by' => auth()->id()]);
            }

            $sale->updatePaymentStatus();

            return $sale;
        });

        return redirect()->route('admin.sales.show', $sale->id)->with('success', 'Sale created successfully.');
    }

    public function show(Sale $sale)
    {
        $sale->load('customer', 'items.product', 'items.productVariant', 'payments.bankAccount');
        return Inertia::render('Admin/Sales/Show', compact('sale'));
    }

    public function edit(Sale $sale)
    {
        $sale->load('customer', 'items.product', 'items.productVariant', 'payments.bankAccount');
        $customers = Customer::all();
        $products = Product::with('variants')->get();
        return Inertia::render('Admin/Sales/Edit', compact('sale', 'customers', 'products'));
    }

    public function update(Request $request, Sale $sale)
    {
        $validatedData = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'items' => 'required|array',
            'items.*.id' => 'sometimes|exists:sale_items,id',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.id' => 'sometimes|exists:sale_payments,id',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.payment_method' => 'required|in:cash,card,bank,mobile_banking',
            'payments.*.bank_account_id' => 'nullable|exists:bank_accounts,id',
            'payments.*.transaction_id' => 'nullable|string',
            'payments.*.note' => 'nullable|string',
            'note' => 'nullable|string',
        ]);

        $sale = DB::transaction(function () use ($sale, $validatedData) {
            $sale->update([
                'customer_id' => $validatedData['customer_id'],
                'subtotal' => $this->calculateSubtotal($validatedData['items']),
                'tax' => $validatedData['tax'],
                'discount' => $validatedData['discount'],
                'total' => $this->calculateTotal($validatedData['items'], $validatedData['tax'], $validatedData['discount']),
                'note' => $validatedData['note'],
            ]);

            $sale->items()->whereNotIn('id', collect($validatedData['items'])->pluck('id'))->delete();

            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    $item = $sale->items()->find($itemData['id']);
                    $item->update($itemData);
                    $this->updateProductStock($item->product_id, $itemData['quantity'] - $item->quantity);
                } else {
                    $item = $sale->items()->create($itemData);
                    $this->updateProductStock($item->product_id, $item->quantity);
                }
            }

            $sale->payments()->whereNotIn('id', collect($validatedData['payments'] ?? [])->pluck('id'))->delete();

            foreach ($validatedData['payments'] ?? [] as $paymentData) {
                if (isset($paymentData['id'])) {
                    $sale->payments()->find($paymentData['id'])->update($paymentData);
                } else {
                    $sale->payments()->create($paymentData + ['created_by' => auth()->id()]);
                }
            }

            $sale->updatePaymentStatus();

            return $sale;
        });

        return redirect()->route('admin.sales.show', $sale->id)->with('success', 'Sale updated successfully.');
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->route('admin.sales.index')->with('success', 'Sale deleted successfully.');
    }

    private function calculateSubtotal($items)
    {
        return collect($items)->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });
    }

    private function calculateTotal($items, $tax, $discount)
    {
        $subtotal = $this->calculateSubtotal($items);
        return ($subtotal + $tax) - $discount;
    }

    private function calculateTotalPayments($payments)
    {
        return collect($payments)->sum('amount');
    }

    private function updateProductStock($productId, $quantity)
    {
        $product = Product::find($productId);
        $product->decrement('stock_quantity', $quantity);
    }
}
