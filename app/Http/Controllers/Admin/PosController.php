<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\BankAccount;
use App\Models\ProductStock;
use App\Models\Category;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class PosController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Pos/Index', [
            'customers' => Customer::active()
                ->select('id', 'name', 'phone', 'balance', 'credit_limit')
                ->get(),
            'bankAccounts' => BankAccount::active()
                ->select('id', 'account_name', 'bank_name', 'current_balance')
                ->get(),
            'categories' => Category::where('status', true)
                ->select('id', 'name')
                ->get(),
        ]);
    }

    public function products()
    {
        return Product::with(['productStocks', 'primaryImage'])
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
                    'image' => $product->primaryImage ? $product->primaryImage->image : null,
                ];
            });
    }

    public function productsByCategory(Request $request)
    {
        $query = Product::with(['productStocks', 'primaryImage'])
            ->where('status', true);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        return $query->get()->map(function ($product) {
            $stock = $product->productStocks->sum('quantity');
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => $product->selling_price,
                'stock' => $stock,
                'image' => $product->primaryImage ? $product->primaryImage->image : null,
            ];
        });
    }

    public function searchProducts(Request $request)
    {
        $query = Product::with(['productStocks', 'primaryImage'])
            ->where('status', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', $search);
            });
        }

        return $query->take(10)->get()->map(function ($product) {
            $stock = $product->productStocks->sum('quantity');
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => $product->selling_price,
                'stock' => $stock,
                'image' => $product->primaryImage ? $product->primaryImage->image : null,
            ];
        });
    }
    public function searchByBarcode(Request $request)
    {
        $barcode = $request->input('barcode');
        $product = Product::where('barcode', $barcode)
            ->orWhere('sku', $barcode)
            ->first();

        return response()->json($product ? [$product] : []);
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid' => 'required|numeric|min:0',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        try {
            DB::beginTransaction();

            // Create sale
            $sale = Sale::create([
                'invoice_no' => 'INV-' . date('Ymd') . '-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT),
                'customer_id' => $request->customer_id,
                'subtotal' => $request->subtotal,
                'discount' => $request->discount,
                'total' => $request->total,
                'paid' => $request->paid,
                'due' => $request->total - $request->paid,
                'payment_status' => $request->paid >= $request->total ? 'paid' : ($request->paid > 0 ? 'partial' : 'due'),
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);

            // Create sale items and update stock
            foreach ($request->items as $item) {
                // Create sale item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                // Get current stock
                $currentStock = ProductStock::where('product_id', $item['product_id'])
                    ->orderBy('id', 'desc')
                    ->first();

                $beforeQuantity = $currentStock ? $currentStock->available_quantity : 0;
                $afterQuantity = $beforeQuantity - $item['quantity'];

                // Check if stock is available
                if ($afterQuantity < 0) {
                    throw new \Exception("Insufficient stock for product ID: {$item['product_id']}");
                }

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

            // Update customer balance if customer exists and there is due amount
            if ($request->customer_id && $sale->due > 0) {
                $customer = Customer::find($request->customer_id);
                if ($customer) {
                    // Check credit limit
                    $newBalance = $customer->balance + $sale->due;
                    if ($newBalance > $customer->credit_limit) {
                        throw new \Exception('This sale would exceed the customer\'s credit limit.');
                    }
                    $customer->increment('balance', $sale->due);
                }
            }

            // Create bank transaction
            if ($request->paid > 0) {
                $bankAccount = BankAccount::findOrFail($request->bank_account_id);
                $bankAccount->transactions()->create([
                    'transaction_type' => 'in',
                    'amount' => $request->paid,
                    'date' => now(),
                    'description' => "Payment received for invoice {$sale->invoice_no}",
                    'created_by' => auth()->id(),
                ]);
                $bankAccount->increment('current_balance', $request->paid);
            }

            DB::commit();

            return to_route('admin.pos.index')->with('sale', $sale);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function printReceipt($id)
    {
        $sale = Sale::with(['saleItems.product', 'customer', 'createdBy'])
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

        return $pdf->stream("receipt-{$sale->invoice_no}.pdf");
    }
}
