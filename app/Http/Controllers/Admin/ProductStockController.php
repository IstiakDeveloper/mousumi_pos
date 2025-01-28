<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Traits\ManagesStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductStockController extends Controller
{
    use ManagesStock;

    public function index(Request $request)
    {
        $query = ProductStock::with(['createdBy'])
            ->select(
                'product_stocks.*',
                'products.name as product_name',
                'products.sku',
                'products.alert_quantity',
                // Get latest available quantity from any type
                DB::raw('(
                    SELECT available_quantity
                    FROM product_stocks ps2
                    WHERE ps2.product_id = product_stocks.product_id
                    ORDER BY id DESC LIMIT 1
                ) as current_available'),
                // Calculate total purchase quantity and value (only from purchase type)
                DB::raw('(
                    SELECT COALESCE(SUM(quantity), 0)
                    FROM product_stocks ps2
                    WHERE ps2.product_id = product_stocks.product_id
                    AND ps2.type = "purchase"
                ) as total_purchase_quantity'),
                DB::raw('(
                    SELECT COALESCE(SUM(quantity * unit_cost), 0)
                    FROM product_stocks ps2
                    WHERE ps2.product_id = product_stocks.product_id
                    AND ps2.type = "purchase"
                ) as total_purchase_cost')
            )
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->whereIn('product_stocks.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('product_stocks')
                    ->groupBy('product_id');
            })
            ->orderBy('product_stocks.product_id', 'asc');

        $stocks = $query->paginate(10)->through(function ($stock) {
            // Calculate weighted average unit cost from purchases only
            $averageUnitCost = $stock->total_purchase_quantity > 0
                ? bcdiv($stock->total_purchase_cost, $stock->total_purchase_quantity, 6)
                : 0;

            // Calculate current stock value using weighted average
            $currentStockValue = bcmul($stock->current_available, $averageUnitCost, 6);

            return [
                'id' => $stock->id,
                'product' => [
                    'id' => $stock->product_id,
                    'name' => $stock->product_name,
                    'sku' => $stock->sku,
                    'alert_quantity' => $stock->alert_quantity
                ],
                'quantity' => $stock->current_available,
                'total_purchased' => $stock->total_purchase_quantity,
                'average_unit_cost' => round($averageUnitCost, 2),
                'current_stock_value' => round($currentStockValue, 2),
                'total_purchase_cost' => round($stock->total_purchase_cost, 2),
                'created_by' => $stock->createdBy?->name ?? 'N/A',
                'created_at' => $stock->created_at,
                'stock_status' => $stock->current_available <= 0 ? 'out' : ($stock->current_available <= $stock->alert_quantity ? 'low' : 'in')
            ];
        });

        // Summary query
        $summaryQuery = DB::table('product_stocks as ps')
            ->select([
                DB::raw('COUNT(DISTINCT ps.product_id) as total_products'),
                // Get total current quantity from latest entries
                DB::raw('COALESCE(SUM(
                    CASE WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        GROUP BY product_id
                    )
                    THEN ps.available_quantity
                    ELSE 0
                    END
                ), 0) as total_quantity'),
                // Calculate total stock value using weighted averages from purchases only
                DB::raw('COALESCE(SUM(
                    CASE WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        GROUP BY product_id
                    )
                    THEN (
                        ps.available_quantity * (
                            SELECT
                                CASE
                                    WHEN SUM(quantity) > 0
                                    THEN SUM(quantity * unit_cost) / SUM(quantity)
                                    ELSE 0
                                END
                            FROM product_stocks ps2
                            WHERE ps2.product_id = ps.product_id
                            AND ps2.type = "purchase"
                        )
                    )
                    ELSE 0
                    END
                ), 0) as total_value')
            ])
            ->first();

        // Low stock calculation
        $lowStockItems = DB::table('product_stocks as ps')
            ->join('products as p', 'p.id', '=', 'ps.product_id')
            ->whereIn('ps.id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('product_stocks')
                    ->groupBy('product_id');
            })
            ->where('ps.available_quantity', '<=', DB::raw('p.alert_quantity'))
            ->where('ps.available_quantity', '>', 0)
            ->count();

        return Inertia::render('Admin/ProductStocks/Index', [
            'stocks' => $stocks,
            'summary' => [
                'total_products' => $summaryQuery->total_products,
                'total_quantity' => round($summaryQuery->total_quantity, 2),
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
            'quantity' => 'required|numeric|min:0.01',
            'total_cost' => 'required|numeric|min:0.01',
            'note' => 'nullable|string',
            'bank_account_id' => 'required|exists:bank_accounts,id'
        ]);

        try {
            DB::beginTransaction();

            // Check bank balance
            $bankAccount = BankAccount::findOrFail($validated['bank_account_id']);
            if (bccomp($bankAccount->current_balance, $validated['total_cost'], 4) < 0) {
                throw new \Exception('Insufficient bank balance');
            }

            // Calculate unit cost precisely
            $unitCost = bcdiv($validated['total_cost'], $validated['quantity'], 6);

            // Get current available quantity
            $currentStock = ProductStock::where('product_id', $validated['product_id'])
                ->orderBy('id', 'desc')
                ->first();

            $currentAvailableQuantity = $currentStock ? $currentStock->available_quantity : 0;
            $newAvailableQuantity = bcadd($currentAvailableQuantity, $validated['quantity'], 6);

            // Create stock entry
            $stock = ProductStock::create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'available_quantity' => $newAvailableQuantity,
                'total_cost' => $validated['total_cost'],
                'unit_cost' => $unitCost,
                'type' => 'purchase',
                'note' => $validated['note'],
                'created_by' => auth()->id()
            ]);

            // Record stock movement
            StockMovement::create([
                'product_id' => $validated['product_id'],
                'reference_type' => 'purchase',
                'reference_id' => $stock->id,
                'quantity' => $validated['quantity'],
                'before_quantity' => $currentAvailableQuantity,
                'after_quantity' => $newAvailableQuantity,
                'type' => 'in',
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
            $newBalance = bcsub($bankAccount->current_balance, $validated['total_cost'], 4);
            $bankAccount->update(['current_balance' => $newBalance]);

            // Update product's weighted average cost
            $this->updateProductWeightedAverageCost($validated['product_id']);

            DB::commit();

            return redirect()->route('admin.product-stocks.index')
                ->with('success', 'Stock added and bank transaction recorded successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    private function updateProductWeightedAverageCost($productId)
    {
        $product = Product::findOrFail($productId);

        $stocks = $product->productStocks()
            ->select(
                DB::raw('COALESCE(SUM(CAST(quantity AS DECIMAL(15,6))), 0) as total_quantity'),
                DB::raw('COALESCE(SUM(CAST(quantity AS DECIMAL(15,6)) * CAST(unit_cost AS DECIMAL(15,6))), 0) as total_value')
            )
            ->where('quantity', '>', 0)
            ->first();

        if ($stocks->total_quantity > 0) {
            $weightedAverageCost = bcdiv($stocks->total_value, $stocks->total_quantity, 6);
            $product->update(['cost_price' => $weightedAverageCost]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $stock = ProductStock::with('product')->findOrFail($id);

            // Get current available quantity
            $latestStock = ProductStock::where('product_id', $stock->product_id)
                ->orderBy('id', 'desc')
                ->first();

            if ($latestStock->available_quantity < $stock->quantity) {
                throw new \Exception('Cannot delete this stock entry as it would result in negative stock.');
            }

            // Calculate new available quantity
            $newAvailableQuantity = bcsub($latestStock->available_quantity, $stock->quantity, 6);

            // Create a new stock entry to record the deletion
            ProductStock::create([
                'product_id' => $stock->product_id,
                'quantity' => -$stock->quantity,
                'available_quantity' => $newAvailableQuantity,
                'unit_cost' => $stock->unit_cost,
                'total_cost' => bcmul(-$stock->quantity, $stock->unit_cost, 6),
                'type' => 'adjustment',
                'note' => 'Stock entry deletion adjustment',
                'created_by' => auth()->id()
            ]);

            // Record stock movement
            StockMovement::create([
                'product_id' => $stock->product_id,
                'reference_type' => 'adjustment',
                'reference_id' => $stock->id,
                'quantity' => $stock->quantity,
                'before_quantity' => $latestStock->available_quantity,
                'after_quantity' => $newAvailableQuantity,
                'type' => 'out',
                'created_by' => auth()->id()
            ]);

            // Handle bank transaction reversal
            $bankTransaction = BankTransaction::where([
                ['description', 'like', "%Stock purchase for product ID: {$stock->product_id}%"],
                ['amount', $stock->total_cost],
                ['transaction_type', 'out']
            ])->first();

            if ($bankTransaction) {
                $bankAccount = BankAccount::findOrFail($bankTransaction->bank_account_id);
                $bankAccount->update([
                    'current_balance' => bcadd($bankAccount->current_balance, $stock->total_cost, 4)
                ]);
                $bankTransaction->delete();
            }

            // Delete the original stock entry
            $stock->delete();

            // Update product's average cost
            $this->updateProductWeightedAverageCost($stock->product_id);

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
