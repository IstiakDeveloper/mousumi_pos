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
    // First, get the latest stock entry for each product
    $query = ProductStock::with(['product', 'createdBy'])
        ->select(
            'product_stocks.*',
            DB::raw('(SELECT SUM(ps2.quantity)
                     FROM product_stocks ps2
                     WHERE ps2.product_id = product_stocks.product_id) as total_quantity'),
            DB::raw('(SELECT SUM(ps2.total_cost)
                     FROM product_stocks ps2
                     WHERE ps2.product_id = product_stocks.product_id) as total_stock_value')
        )
        ->whereIn('product_stocks.id', function($query) {
            $query->select(DB::raw('MAX(id)'))
                ->from('product_stocks')
                ->groupBy('product_id');
        })
        ->orderBy('created_at', 'desc');

    $stocks = $query->paginate(10)->through(function ($stock) {
        // Calculate current stock value based on latest unit cost
        $currentStockValue = $stock->total_quantity > 0 ?
            $stock->total_quantity * $stock->unit_cost : 0;

        return [
            'id' => $stock->id,
            'product' => [
                'id' => $stock->product->id,
                'name' => $stock->product->name,
                'sku' => $stock->product->sku,
                'alert_quantity' => $stock->product->alert_quantity
            ],
            'quantity' => $stock->total_quantity,
            'total_cost' => $currentStockValue, // Using current value based on latest unit cost
            'unit_cost' => $stock->unit_cost,
            'created_by' => $stock->createdBy?->name ?? 'N/A',
            'created_at' => $stock->created_at,
            'stock_status' => $stock->total_quantity <= 0 ? 'out' :
                ($stock->total_quantity <= $stock->product->alert_quantity ? 'low' : 'in')
        ];
    });

    // Calculate summary with correct stock values
    $summaryQuery = DB::table('product_stocks as ps1')
        ->select([
            DB::raw('COUNT(DISTINCT product_id) as total_products'),
            DB::raw('SUM(CASE
                WHEN ps1.id IN (
                    SELECT MAX(ps2.id)
                    FROM product_stocks ps2
                    GROUP BY ps2.product_id
                )
                THEN quantity
                ELSE 0
            END) as total_quantity'),
            DB::raw('SUM(CASE
                WHEN ps1.id IN (
                    SELECT MAX(ps2.id)
                    FROM product_stocks ps2
                    GROUP BY ps2.product_id
                )
                THEN quantity * unit_cost
                ELSE 0
            END) as total_value')
        ])
        ->first();

    // Calculate low stock items
    $lowStockItems = DB::table('product_stocks as ps1')
        ->join('products', 'products.id', '=', 'ps1.product_id')
        ->where('ps1.id', function($query) {
            $query->select(DB::raw('MAX(ps2.id)'))
                ->from('product_stocks as ps2')
                ->whereColumn('ps2.product_id', 'ps1.product_id');
        })
        ->whereRaw('ps1.quantity <= products.alert_quantity')
        ->count();

    return Inertia::render('Admin/ProductStocks/Index', [
        'stocks' => $stocks,
        'summary' => [
            'total_products' => $summaryQuery->total_products,
            'total_quantity' => $summaryQuery->total_quantity,
            'total_value' => round($summaryQuery->total_value, 2),
            'low_stock_items' => $lowStockItems
        ]
    ]);
}


    public function create()
    {
        return Inertia::render('Admin/ProductStocks/Create', [
            'products' => Product::with([
                'productStocks' => function ($query) {
                    $query->latest();
                }
            ])->get()->map(fn($product) => [
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

            // Calculate unit cost for this entry
            $unitCost = $validated['total_cost'] / $validated['quantity'];

            // Create stock entry
            $stock = ProductStock::create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'total_quantity' => $validated['total_cost'] / $unitCost,
                'total_cost' => $validated['total_cost'],
                'unit_cost' => $unitCost,
                'note' => $validated['note'],
                'created_by' => auth()->id()
            ]);

            // Create bank transaction
            BankTransaction::create([
                'bank_account_id' => $validated['bank_account_id'],
                'transaction_type' => 'out',
                'amount' => $validated['total_cost'],
                'description' => "Stock purchase for product ID: {$validated['product_id']}",
                'date' => now(),
                'created_by' => auth()->id()
            ]);

            // Update bank balance
            $bankAccount->update([
                'current_balance' => $bankAccount->current_balance - $validated['total_cost']
            ]);

            // Calculate new weighted average cost
            $product = Product::findOrFail($validated['product_id']);
            $stocks = $product->productStocks()
                ->select(
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('SUM(quantity * unit_cost) as total_value')
                )
                ->first();

            // Calculate weighted average only if there are stocks
            if ($stocks->total_quantity > 0) {
                $weightedAverageCost = $stocks->total_value / $stocks->total_quantity;

                $product->update([
                    'cost_price' => round($weightedAverageCost, 2)
                ]);
            }

            DB::commit();

            return redirect()->route('admin.product-stocks.index')
                ->with('success', 'Stock added and bank transaction recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Find the stock entry
            $stock = ProductStock::with('product')->findOrFail($id);

            // Find and delete the original bank transaction
            $bankTransaction = BankTransaction::where([
                ['description', 'like', "%Stock purchase for product ID: {$stock->product_id}%"],
                ['amount', $stock->total_cost],
                ['transaction_type', 'out']
            ])->first();

            if ($bankTransaction) {
                // Update bank balance (add back the money)
                $bankAccount = BankAccount::findOrFail($bankTransaction->bank_account_id);
                $bankAccount->update([
                    'current_balance' => $bankAccount->current_balance + $stock->total_cost
                ]);

                // Delete the bank transaction
                $bankTransaction->delete();
            }

            // Delete the stock entry
            $stock->delete();

            // Update product's average cost
            $product = $stock->product;
            $remainingStocks = $product->productStocks()
                ->select(DB::raw('SUM(quantity) as total_quantity, SUM(total_cost) as total_cost'))
                ->first();

            if ($remainingStocks->total_quantity > 0) {
                $newAverageCost = $remainingStocks->total_cost / $remainingStocks->total_quantity;
                $product->update(['cost_price' => $newAverageCost]);
            } else {
                $product->update(['cost_price' => 0]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock entry deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 422);
        }
    }
}
