<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf; // Update this import at the top of your controller


class PosController extends Controller
{
    public function index()
    {
        $customers = Customer::active()->select('id', 'name', 'phone')->get();
        $bankAccounts = BankAccount::active()->select('id', 'account_name', 'bank_name')->get();
        $categories = Category::where('status', true)->get(); // Add this line

        return Inertia::render('Admin/Pos/Index', [
            'customers' => $customers,
            'bankAccounts' => $bankAccounts,
            'categories' => $categories, // Add this line
            'paymentMethods' => [
                ['id' => 'cash', 'name' => 'Cash'],
                ['id' => 'card', 'name' => 'Card'],
                ['id' => 'bank', 'name' => 'Bank Transfer'],
                ['id' => 'mobile_banking', 'name' => 'Mobile Banking'],
            ],
        ]);
    }
    public function productsByCategory($categoryId = null)
    {
        $query = Product::with(['category', 'unit', 'productStocks'])
            ->where('status', true);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->get()->map(function ($product) {
            $stock = $product->productStocks->sum('quantity');
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => $product->selling_price,
                'stock' => $stock,
                'category' => $product->category->name,
                'unit' => $product->unit->name,
            ];
        });
    }
    public function products()
    {
        return Product::with(['category', 'unit', 'productStocks'])
            ->where('status', true)
            ->get()
            ->map(function ($product) {
                $stock = $product->productStocks->sum('quantity');
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => $product->selling_price,
                    'stock' => $stock,
                    'category' => $product->category->name,
                    'unit' => $product->unit->name,
                ];
            });
    }

    public function searchProducts(Request $request)
    {
        $query = Product::with(['category', 'unit', 'productStocks'])
            ->where('status', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', $search);
            });
        }

        $products = $query->take(10)->get()
            ->map(function ($product) {
                $stock = $product->productStocks->sum('quantity');
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => $product->selling_price,
                    'stock' => $stock,
                    'category' => $product->category->name,
                    'unit' => $product->unit->name,
                ];
            });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'tax' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank,mobile_banking',
            'bank_account_id' => 'required_if:payment_method,bank,card',
            'transaction_id' => 'required_if:payment_method,bank,mobile_banking',
        ]);

        try {
            DB::beginTransaction();

            // Create sale
            $sale = Sale::create([
                'invoice_no' => 'INV-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT),
                'customer_id' => $request->customer_id,
                'subtotal' => $request->subtotal,
                'tax' => $request->tax,
                'discount' => $request->discount,
                'total' => $request->total,
                'paid' => $request->paid,
                'due' => $request->total - $request->paid,
                'payment_status' => $request->paid >= $request->total ? 'paid' : ($request->paid > 0 ? 'partial' : 'due'),
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);

            // Create sale items
            foreach ($request->items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                // Update stock
                $stock = ProductStock::firstOrCreate([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                ]);
                $stock->decrement('quantity', $item['quantity']);
            }

            // Create payment record
            if ($request->paid > 0) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'amount' => $request->paid,
                    'payment_method' => $request->payment_method,
                    'bank_account_id' => $request->bank_account_id,
                    'transaction_id' => $request->transaction_id,
                    'note' => $request->note,
                    'created_by' => auth()->id(),
                ]);

                // Update bank account balance if payment method is bank or card
                if (in_array($request->payment_method, ['bank', 'card']) && $request->bank_account_id) {
                    $bankAccount = BankAccount::find($request->bank_account_id);
                    $bankAccount->increment('current_balance', $request->paid);
                }
            }

            DB::commit();

            // Load relationships and return
            $sale->load(['saleItems.product', 'customer', 'salePayments']);

            // Return directly with Inertia instead of redirect
            return to_route('admin.pos.index')->with('sale', $sale);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'An error occurred while processing the sale: ' . $e->getMessage()]);
        }
    }

    public function printReceipt($id)
    {
        $sale = Sale::with(['saleItems.product', 'customer', 'salePayments'])
            ->findOrFail($id);

        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'company' => [
                'name' => config('app.name'),
                'address' => config('app.address'),
                'phone' => config('app.phone'),
                'email' => config('app.email'),
            ]
        ]);

        return $pdf->stream('receipt-' . $sale->invoice_no . '.pdf');
    }
}
