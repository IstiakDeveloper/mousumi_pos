<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'barcode',
        'category_id',
        'brand_id',
        'unit_id',
        'cost_price',
        'selling_price',
        'alert_quantity',
        'description',
        'specifications',
        'status',
    ];

    protected $casts = [
        'specifications' => 'array',
        'status' => 'boolean',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    // Existing Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function productStocks()
    {
        return $this->hasMany(ProductStock::class, 'product_id', 'id');
    }

    // Stock Analysis Methods
    public static function getProductAnalysis($startDate, $endDate)
    {
        $beforeStockPositions = static::getBeforeStockPositions($startDate);
        $stockPositions = static::getCurrentStockPositions($endDate);
        $weightedAvgCosts = static::getWeightedAvgCosts($endDate);

        $buyStats = DB::table('product_stocks')
            ->select('product_id')
            ->selectRaw('CAST(COALESCE(SUM(quantity), 0) AS DECIMAL(15,6)) as buy_quantity')
            ->selectRaw('CAST(COALESCE(SUM(total_cost), 0) AS DECIMAL(15,6)) as total_buy_cost')
            ->whereNull('deleted_at')
            ->where('type', 'purchase')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('product_id');

        $saleStats = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNull('sales.deleted_at')
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id as product_id')
            ->selectRaw('CAST(COALESCE(SUM(sale_items.quantity), 0) AS DECIMAL(15,6)) as sale_quantity')
            ->selectRaw('CAST(COALESCE(SUM(sale_items.subtotal), 0) AS DECIMAL(15,2)) as total_sale_amount')
            ->selectRaw('CAST(COALESCE(SUM(CASE WHEN sales.subtotal > 0 THEN (sale_items.subtotal / sales.subtotal) * sales.discount ELSE 0 END), 0) AS DECIMAL(15,6)) as sale_discount')
            ->selectRaw('CAST(COALESCE(SUM(sale_items.subtotal - CASE WHEN sales.subtotal > 0 THEN (sale_items.subtotal / sales.subtotal) * sales.discount ELSE 0 END), 0) AS DECIMAL(15,6)) as sale_amount_after_discount');

        $products = static::with(['category', 'unit'])
            ->where('products.status', true)
            ->leftJoinSub($buyStats, 'buy_stats', function ($join) {
                $join->on('buy_stats.product_id', '=', 'products.id');
            })
            ->leftJoinSub($saleStats, 'sale_stats', function ($join) {
                $join->on('sale_stats.product_id', '=', 'products.id');
            })
            ->select('products.*')
            ->selectRaw('COALESCE(buy_stats.buy_quantity, 0) as buy_quantity')
            ->selectRaw('COALESCE(buy_stats.total_buy_cost, 0) as total_buy_cost')
            ->selectRaw('COALESCE(sale_stats.sale_quantity, 0) as sale_quantity')
            ->selectRaw('COALESCE(sale_stats.total_sale_amount, 0) as total_sale_amount')
            ->selectRaw('COALESCE(sale_stats.sale_discount, 0) as sale_discount')
            ->selectRaw('COALESCE(sale_stats.sale_amount_after_discount, 0) as sale_amount_after_discount')
            ->orderBy('products.name')
            ->get()
            ->map(function ($product, $index) use ($stockPositions, $beforeStockPositions, $weightedAvgCosts) {
                return static::calculateProductMetrics(
                    $product,
                    $index,
                    $stockPositions,
                    $beforeStockPositions,
                    (float) ($weightedAvgCosts[$product->id] ?? 0)
                );
            })
            ->filter(function ($product) {
                // Include products with any activity: before stock, purchases, sales, or current stock
                // Even if sold out (available = 0), include if there were purchases or sales
                return $product['before_quantity'] > 0 ||
                       $product['buy_quantity'] > 0 ||
                       $product['sale_quantity'] > 0 ||
                       $product['available_quantity'] > 0;
            });

        return [
            'products' => $products->values(), // Reset keys after filtering
            'totals' => static::calculateTotals($products),
        ];
    }

    protected static function getBeforeStockPositions($startDate)
    {
        $ranked = DB::table('product_stocks')
            ->select('product_id', 'available_quantity', 'unit_cost')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY created_at DESC, id DESC) as rn')
            ->where('created_at', '<', $startDate);

        return DB::query()
            ->fromSub($ranked, 'ranked_stocks')
            ->where('rn', 1)
            ->select('product_id', 'available_quantity as before_quantity', 'unit_cost as before_avg_cost')
            ->get()
            ->keyBy('product_id');
    }

    protected static function getCurrentStockPositions($endDate)
    {
        // ALWAYS calculate from purchases - sales for accuracy
        // Don't rely on product_stocks.available_quantity which may have discrepancies

        $productIds = static::where('status', true)->pluck('id');

        $purchases = DB::table('product_stocks')
            ->where('type', 'purchase')
            ->where('created_at', '<=', $endDate)
            ->whereNull('deleted_at')
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as total_quantity')
            ->get()
            ->pluck('total_quantity', 'product_id');

        $sales = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.created_at', '<=', $endDate)
            ->whereNull('sales.deleted_at')
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id as product_id, SUM(sale_items.quantity) as total_quantity')
            ->get()
            ->pluck('total_quantity', 'product_id');

        $stockPositions = collect();

        foreach ($productIds as $productId) {
            $availableQty = (float) ($purchases[$productId] ?? 0) - (float) ($sales[$productId] ?? 0);

            $stockPositions[$productId] = (object) [
                'product_id' => $productId,
                'available_quantity' => max(0, $availableQty),
            ];
        }

        return $stockPositions;
    }

    protected static function getWeightedAvgCosts($endDate)
    {
        return DB::table('product_stocks')
            ->where('type', 'purchase')
            ->where('created_at', '<=', $endDate)
            ->whereNull('deleted_at')
            ->groupBy('product_id')
            ->selectRaw('
                product_id,
                CASE
                    WHEN SUM(quantity) > 0
                    THEN CAST(SUM(total_cost) AS DOUBLE) / SUM(quantity)
                    ELSE 0
                END as weighted_avg_cost
            ')
            ->get()
            ->pluck('weighted_avg_cost', 'product_id');
    }

    protected static function calculateProductMetrics($product, $index, $stockPositions, $beforeStockPositions, float $weightedAvgCost)
    {
        $stockPosition = $stockPositions[$product->id] ?? null;
        $beforeStockPosition = $beforeStockPositions[$product->id] ?? null;

        $beforeQuantity = $beforeStockPosition ? (float) $beforeStockPosition->before_quantity : 0;
        $beforeAvgCost = $beforeStockPosition ? (float) $beforeStockPosition->before_avg_cost : 0;
        $beforeStockValue = $beforeQuantity * $beforeAvgCost;

        $buyQuantity = (float) $product->buy_quantity;
        $totalBuyCost = (float) $product->total_buy_cost;
        $buyPrice = $buyQuantity > 0 ? $totalBuyCost / $buyQuantity : 0;

        $saleQuantity = (float) $product->sale_quantity;
        $totalSaleAmount = (float) $product->total_sale_amount;
        $totalSaleDiscount = (float) ($product->sale_discount ?? 0);
        $totalSaleAfterDiscount = (float) ($product->sale_amount_after_discount ?? $totalSaleAmount);
        $salePrice = $saleQuantity > 0 ? $totalSaleAmount / $saleQuantity : 0;
        $salePriceAfterDiscount = $saleQuantity > 0 ? $totalSaleAfterDiscount / $saleQuantity : 0;

        // Get available quantity from stock position (either from stock_movements or product_stocks)
        $availableQuantity = $stockPosition ? (float) $stockPosition->available_quantity : 0;

        // Weighted average cost of all purchases up to the end date.
        $effectiveCost = $weightedAvgCost > 0 ? $weightedAvgCost : ($beforeAvgCost > 0 ? $beforeAvgCost : $buyPrice);

        // Calculate monthly stock value flow:
        // Available Stock Value = Before Stock Value + Monthly Purchases - Monthly Sales Cost
        // Sales Cost = Sale Quantity × Effective Cost (weighted average)
        $monthlySalesCost = $saleQuantity * $effectiveCost;
        $availableStockValue = $beforeStockValue + $totalBuyCost - $monthlySalesCost;

        // Ensure non-negative stock value (shouldn't be negative but just in case)
        $availableStockValue = max(0, $availableStockValue);

        // Profit calculation: Sale Amount (after discount) - Sales Cost
        $totalProfit = $totalSaleAfterDiscount - $monthlySalesCost;
        $profitPerUnit = $saleQuantity > 0 ? $totalProfit / $saleQuantity : 0;

        // Calculate profit percentage: (Profit / Sale After Discount) * 100
        $profitPercentage = $totalSaleAfterDiscount > 0 ? ($totalProfit / $totalSaleAfterDiscount) * 100 : 0;

        return [
            'serial' => $index + 1,
            'product_name' => $product->name,
            'product_model' => $product->sku,
            'category' => $product->category->name,
            'unit' => $product->unit->name,
            'before_quantity' => $beforeQuantity,
            'before_price' => $beforeAvgCost,
            'before_value' => $beforeStockValue,
            'buy_quantity' => $buyQuantity,
            'buy_price' => $buyPrice,
            'total_buy_price' => $totalBuyCost,
            'sale_quantity' => $saleQuantity,
            'sale_price' => $salePrice,
            'total_sale_price' => $totalSaleAmount,
            'sale_discount' => $totalSaleDiscount,
            'sale_after_discount' => $totalSaleAfterDiscount,
            'profit_per_unit' => $profitPerUnit,
            'total_profit' => $totalProfit,
            'profit_percentage' => $profitPercentage,
            'available_quantity' => $availableQuantity,
            'available_stock_value' => $availableStockValue,
        ];
    }

    public static function calculateTotals($products)
    {
        return [
            'before_quantity' => (float) $products->sum('before_quantity'),
            'before_value' => (float) $products->sum('before_value'),
            'buy_quantity' => (float) $products->sum('buy_quantity'),
            'total_buy_price' => (float) $products->sum('total_buy_price'),
            'sale_quantity' => (float) $products->sum('sale_quantity'),
            'total_sale_price' => (float) $products->sum('total_sale_price'),
            'sale_discount' => (float) $products->sum('sale_discount'),
            'sale_after_discount' => (float) $products->sum('sale_after_discount'),
            'total_profit' => (float) $products->sum('total_profit'),
            'available_quantity' => (float) $products->sum('available_quantity'),
            'available_stock_value' => (float) $products->sum('available_stock_value'),
        ];
    }

    // Accessor for current stock
    public function getCurrentStockAttribute()
    {
        return $this->stocks()->sum('quantity');
    }

    public static function getCumulativeTotals()
    {
        try {
            $productAnalysis = static::getProductAnalysis(
                Carbon::now()->startOfCentury(),
                Carbon::now()->endOfDay()
            );

            return [
                'total_profit' => [
                    'period' => 0.0,
                    'cumulative' => (float) ($productAnalysis['totals']['total_profit'] ?? 0),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'total_profit' => [
                    'period' => 0.0,
                    'cumulative' => 0.0,
                ],
            ];
        }
    }
}
