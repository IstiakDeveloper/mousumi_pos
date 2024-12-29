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
        ->select(
            'product_stocks.*',
            DB::raw('SUM(quantity) as total_quantity'),
            DB::raw('SUM(total_cost) as total_stock_value'),
            DB::raw('(SUM(total_cost) / SUM(quantity)) as avg_unit_cost')
        )
        ->groupBy(
            'product_stocks.id',
            'product_stocks.product_id',
            'product_stocks.product_variant_id',
            'product_stocks.quantity',
            'product_stocks.total_cost',
            'product_stocks.unit_cost',
            'product_stocks.note',
            'product_stocks.created_by',
            'product_stocks.created_at',
            'product_stocks.updated_at'
        )
        ->orderBy('created_at', 'desc');

        $stocks = $query->paginate(10)->through(function ($stock) {
            return [
                'id' => $stock->id,  // Now we'll have a proper id
                'product' => [
                    'id' => $stock->product->id,
                    'name' => $stock->product->name,
                    'sku' => $stock->product->sku,
                    'alert_quantity' => $stock->product->alert_quantity
                ],
                'quantity' => $stock->total_quantity,
                'total_cost' => $stock->total_stock_value,
                'unit_cost' => $stock->avg_unit_cost,
                'created_by' => $stock->createdBy?->name ?? 'N/A',
                'created_at' => $stock->created_at,
                'stock_status' => $stock->total_quantity <= 0 ? 'out' :
                    ($stock->total_quantity <= $stock->product->alert_quantity ? 'low' : 'in')
            ];
        });

        return Inertia::render('Admin/ProductStocks/Index', [
            'stocks' => $stocks,
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
