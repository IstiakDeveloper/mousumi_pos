<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductStockController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductStock::with(['product', 'createdBy'])
        ->selectRaw('
            product_id,
            MAX(created_at) as last_created_at,
            SUM(quantity) as total_quantity,
            SUM(product_stocks.total_cost) as total_stock_value,
            (SUM(product_stocks.total_cost) / SUM(product_stocks.quantity)) as unit_cost
        ')
        ->groupBy('product_id')
        ->orderBy('last_created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by stock status
        if ($request->filled('stock_status')) {
            $query->whereHas('product', function ($q) use ($request) {
                switch ($request->stock_status) {
                    case 'low':
                        $q->whereRaw('product_stocks.quantity <= products.alert_quantity');
                        break;
                    case 'out':
                        $q->where('product_stocks.quantity', '<=', 0);
                        break;
                    case 'in':
                        $q->whereRaw('product_stocks.quantity > products.alert_quantity');
                        break;
                }
            });
        }

        $stocks = $query->paginate(10)->through(function ($stock) {
            return [
                'id' => $stock->id,
                'product' => [
                    'id' => $stock->product->id,
                    'name' => $stock->product->name,
                    'sku' => $stock->product->sku,
                    'alert_quantity' => $stock->product->alert_quantity
                ],
                'quantity' => $stock->total_quantity, // Use aggregated value
                'total_cost' => $stock->total_stock_value, // Use aggregated value
                'unit_cost' => $stock->unit_cost,
                'created_by' => $stock->createdBy?->name ?? 'N/A',
                'created_at' => $stock->last_created_at, // Use aggregated value
                'stock_status' => $stock->total_quantity <= 0 ? 'out' : ($stock->total_quantity <= $stock->product->alert_quantity ? 'low' : 'in')
            ];
        });

        return Inertia::render('Admin/ProductStocks/Index', [
            'stocks' => $stocks,

            'filters' => $request->only(['search', 'stock_status']),
            'summary' => [
                'total_products' => ProductStock::distinct('product_id')->count(),
                'total_quantity' => ProductStock::sum('quantity'),
                'total_value' => ProductStock::sum('total_cost'),
                'low_stock_items' => ProductStock::whereRaw('quantity <= products.alert_quantity')
                    ->join('products', 'products.id', '=', 'product_stocks.product_id')
                    ->count()
            ]
        ]);
    }


    public function create()
    {
        return Inertia::render('Admin/ProductStocks/Create', [
            'products' => Product::with(['productStocks' => function ($query) {
                $query->latest();
            }])->get()->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => $product->productStocks->sum('quantity'),
                'last_unit_cost' => $product->productStocks->first()?->unit_cost ?? 0,
            ]),
            'bankAccounts' => BankAccount::where('status', true)
                ->select('id', 'account_name', 'bank_name', 'current_balance')
                ->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'total_cost' => 'required|numeric|min:0',
            'note' => 'nullable|string',
            'bank_account_id' => 'required|exists:bank_accounts,id'
        ]);

        try {
            DB::beginTransaction();

            // Check if bank has sufficient balance
            $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
            if ($bankAccount->current_balance < $validated['total_cost']) {
                throw new \Exception('Insufficient bank balance');
            }

            // Calculate unit cost
            $unitCost = $validated['total_cost'] / $validated['quantity'];

            // Create stock entry
            $stock = ProductStock::create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'total_cost' => $validated['total_cost'],
                'unit_cost' => $unitCost,
                'note' => $validated['note'],
                'created_by' => auth()->id()
            ]);

            // Create bank transaction
            BankTransaction::create([
                'bank_account_id' => $validated['bank_account_id'],
                'transaction_type' => 'withdrawal',
                'amount' => $validated['total_cost'],
                'description' => "Stock purchase for product ID: {$validated['product_id']}",
                'date' => now(),
                'created_by' => auth()->id()
            ]);

            // Update bank balance
            $bankAccount->update([
                'current_balance' => $bankAccount->current_balance - $validated['total_cost']
            ]);

            // Update product's cost price (average cost)
            $product = Product::findOrFail($validated['product_id']);
            $totalQuantity = $product->productStocks()->sum('quantity');
            $averageCost = $product->productStocks()->sum('total_cost') / $totalQuantity;

            $product->update([
                'cost_price' => $averageCost
            ]);

            DB::commit();

            return redirect()->route('admin.product-stocks.index')
                ->with('success', 'Stock added and bank transaction recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

}
