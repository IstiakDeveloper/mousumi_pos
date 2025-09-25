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
use NumberFormatter;

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
                $latestStock = $product->productStocks->sortByDesc('id')->first();
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'selling_price' => $product->selling_price,
                    'stock' => $latestStock ? round($latestStock->available_quantity) : 0,
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
            $latestStock = $product->productStocks->sortByDesc('id')->first();
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => $product->selling_price,
                'stock' => $latestStock ? round($latestStock->available_quantity) : 0,
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
            $latestStock = $product->productStocks->sortByDesc('id')->first();
            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'selling_price' => $product->selling_price,
                'stock' => $latestStock ? round($latestStock->available_quantity) : 0,
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
        \Log::info('POS Store Request: ' . json_encode($request->all()));

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
                'bank_account_id' => 'required|exists:bank_accounts,id',
                'customer_id' => 'nullable|exists:customers,id',
            ]);

            \Log::info('Validation passed');

            DB::beginTransaction();
            \Log::info('Transaction started');

            // Generate a unique invoice number
            $date = date('Ymd');
            $invoiceNumber = null;
            $attempt = 0;

            do {
                $attempt++;
                $count = Sale::where('invoice_no', 'like', "INV-{$date}-%")->count();
                $invoiceNumber = 'INV-' . $date . '-' . str_pad($count + $attempt, 4, '0', STR_PAD_LEFT);
                $exists = Sale::where('invoice_no', $invoiceNumber)->exists();
            } while ($exists && $attempt < 10); // Limit attempts to prevent infinite loop

            if ($attempt >= 10) {
                throw new \Exception("Failed to generate a unique invoice number after multiple attempts");
            }

            // Create sale with the new unique invoice number
            $sale = Sale::create([
                'invoice_no' => $invoiceNumber,
                'customer_id' => $request->customer_id,
                'subtotal' => $request->subtotal,
                'discount' => $request->discount,
                'total' => $request->total,
                'paid' => $request->paid,
                'due' => $request->total - $request->paid,
                'payment_status' => $request->paid >= $request->total ? 'paid' : ($request->paid > 0 ? 'partial' : 'due'),
                'note' => $request->note ?? null,
                'created_by' => auth()->id(),
            ]);

            \Log::info('Sale created with ID: ' . $sale->id);

            // Process each item
            foreach ($request->items as $index => $item) {
                \Log::info("Processing item {$index}: " . json_encode($item));

                // Check stock before attempting to create the sale item
                $currentStock = ProductStock::where('product_id', $item['product_id'])
                    ->orderBy('id', 'desc')
                    ->first();

                $beforeQuantity = $currentStock ? $currentStock->available_quantity : 0;
                $afterQuantity = $beforeQuantity - $item['quantity'];

                \Log::info("Stock check: before={$beforeQuantity}, after={$afterQuantity}");

                // Check if stock is available
                if ($afterQuantity < 0) {
                    throw new \Exception("Insufficient stock for product ID: {$item['product_id']}, Available: {$beforeQuantity}, Requested: {$item['quantity']}");
                }

                // Create sale item
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $item['quantity'] * $item['unit_price'],
                ]);

                \Log::info("Sale item created for product ID: {$item['product_id']}");

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

                \Log::info("Product stock updated for product ID: {$item['product_id']}");

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

                \Log::info("Stock movement recorded for product ID: {$item['product_id']}");
            }

            // Update customer balance if needed
            if ($request->customer_id && $sale->due > 0) {
                $customer = Customer::find($request->customer_id);

                if ($customer) {
                    \Log::info("Checking customer credit limit: current balance={$customer->balance}, credit limit={$customer->credit_limit}, new due={$sale->due}");

                    // Check credit limit
                    $newBalance = $customer->balance + $sale->due;
                    if ($newBalance > $customer->credit_limit) {
                        throw new \Exception("This sale would exceed the customer's credit limit. Current balance: {$customer->balance}, Credit limit: {$customer->credit_limit}, New due: {$sale->due}");
                    }

                    $customer->increment('balance', $sale->due);
                    \Log::info("Customer balance updated: {$newBalance}");
                }
            }

            // Handle payment if any
            if ($request->paid > 0) {
                \Log::info("Processing payment of {$request->paid} to bank account ID: {$request->bank_account_id}");

                $bankAccount = BankAccount::findOrFail($request->bank_account_id);
                $bankAccount->transactions()->create([
                    'transaction_type' => 'in',
                    'amount' => $request->paid,
                    'date' => now(),
                    'description' => "Payment received for invoice {$sale->invoice_no}",
                    'created_by' => auth()->id(),
                ]);

                $bankAccount->increment('current_balance', $request->paid);
                \Log::info("Bank account balance updated");
            }

            DB::commit();
            \Log::info("Transaction committed successfully with invoice number: {$invoiceNumber}");

            return to_route('admin.pos.index')->with('sale', $sale);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('POS Validation Error: ' . json_encode($e->errors()));
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('POS Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function printReceipt($id)
    {
        // Fetch the sale with related data, including product categories
        $sale = Sale::with([
            'saleItems.product.category', // Include product category relationship
            'customer',
            'createdBy'
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

        $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
        $amountInWords = ucfirst($f->format($sale->total)) . " taka only";

        $pdf = Pdf::loadView('pdf.receipt', [
            'sale' => $sale,
            'itemsByCategory' => $itemsByCategory, // Pass the grouped items
            'categoryTotals' => $categoryTotals,
            'amountInWords' => $amountInWords,
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
