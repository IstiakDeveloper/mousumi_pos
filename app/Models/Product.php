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
        'status'
    ];

    protected $casts = [
        'specifications' => 'array',
        'status' => 'boolean',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2'
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

        $products = static::with(['category', 'unit'])
            ->select('products.*')
            ->addSelect([
                'all_time_buy_price' => static::getAllTimeBuyPriceQuery(),
                'buy_quantity' => static::getBuyQuantityQuery($startDate, $endDate),
                'total_buy_cost' => static::getTotalBuyCostQuery($startDate, $endDate),
                'sale_quantity' => static::getSaleQuantityQuery($startDate, $endDate),
                'total_sale_amount' => static::getTotalSaleAmountQuery($startDate, $endDate)
            ])
            ->get()
            ->map(function ($product, $index) use ($stockPositions, $beforeStockPositions) {
                return static::calculateProductMetrics($product, $index, $stockPositions, $beforeStockPositions);
            });

        return [
            'products' => $products,
            'totals' => static::calculateTotals($products)
        ];
    }

    protected static function getBeforeStockPositions($startDate)
    {
        return DB::table(function ($query) use ($startDate) {
            $query->from('product_stocks')
                ->select('product_id')
                ->selectRaw('
                    FIRST_VALUE(available_quantity) OVER (
                        PARTITION BY product_id
                        ORDER BY created_at DESC, id DESC
                    ) as before_quantity
                ')
                ->selectRaw('
                    FIRST_VALUE(unit_cost) OVER (
                        PARTITION BY product_id
                        ORDER BY created_at DESC, id DESC
                    ) as before_avg_cost
                ')
                ->where('created_at', '<', $startDate);
        }, 'before_stocks')
            ->select('product_id', 'before_quantity', 'before_avg_cost')
            ->distinct()
            ->get()
            ->keyBy('product_id');
    }

    protected static function getCurrentStockPositions($endDate)
    {
        return DB::table('product_stocks as ps')
            ->select('ps.product_id')
            ->selectRaw('
                CASE
                    WHEN ps.id IN (
                        SELECT MAX(id)
                        FROM product_stocks
                        WHERE product_id = ps.product_id
                        AND created_at <= ?
                        GROUP BY product_id
                    )
                    THEN ps.available_quantity
                    ELSE 0
                END as available_quantity
            ', [$endDate])
            ->selectRaw(static::getWeightedAvgCostQuery())
            ->join('product_stocks as ps2', function ($join) use ($endDate) {
                $join->on('ps2.product_id', '=', 'ps.product_id')
                    ->where('ps2.created_at', '<=', $endDate);
            })
            ->groupBy('ps.product_id', 'ps.id', 'ps.available_quantity')
            ->having('available_quantity', '>', 0)
            ->get()
            ->keyBy('product_id');
    }

    protected static function getWeightedAvgCostQuery()
    {
        return '
            CASE
                WHEN SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END) > 0
                THEN CAST(
                    SUM(CASE WHEN ps2.quantity > 0 THEN (ps2.quantity * ps2.unit_cost) ELSE 0 END) /
                    SUM(CASE WHEN ps2.quantity > 0 THEN ps2.quantity ELSE 0 END)
                    AS DECIMAL(15,6)
                )
                ELSE 0
            END as weighted_avg_cost
        ';
    }

    protected static function getAllTimeBuyPriceQuery()
    {
        return ProductStock::selectRaw('
            CASE
                WHEN SUM(CASE WHEN type = "purchase" THEN quantity ELSE 0 END) > 0
                THEN CAST(
                    SUM(CASE WHEN type = "purchase" THEN total_cost ELSE 0 END) /
                    SUM(CASE WHEN type = "purchase" THEN quantity ELSE 0 END)
                    AS DECIMAL(15,6)
                )
                ELSE 0
            END')
            ->whereColumn('product_id', 'products.id')
            ->where('type', 'purchase');
    }

    protected static function getBuyQuantityQuery($startDate, $endDate)
    {
        return ProductStock::selectRaw('CAST(COALESCE(SUM(quantity), 0) AS DECIMAL(15,6))')
            ->whereColumn('product_id', 'products.id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'purchase');
    }

    protected static function getTotalBuyCostQuery($startDate, $endDate)
    {
        return ProductStock::selectRaw('CAST(COALESCE(SUM(total_cost), 0) AS DECIMAL(15,6))')
            ->whereColumn('product_id', 'products.id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('type', 'purchase');
    }

    protected static function getSaleQuantityQuery($startDate, $endDate)
    {
        return SaleItem::selectRaw('CAST(COALESCE(SUM(quantity), 0) AS DECIMAL(15,6))')
            ->whereColumn('product_id', 'products.id')
            ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            });
    }

    protected static function getTotalSaleAmountQuery($startDate, $endDate)
    {
        // Define cutoff date for when to start applying discount calculations
        $discountCutoffDate = '2025-03-01';

        // For date ranges that include March 2025 or later, use the discount-aware calculation
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereColumn('sale_items.product_id', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNull('sales.deleted_at')
            ->selectRaw('
            CAST(
                COALESCE(
                    SUM(
                        CASE
                            -- Only apply discount calculation for sales on/after March 1, 2025
                            WHEN sales.created_at >= ? AND sales.total > 0
                            THEN ROUND(
                                (sale_items.subtotal / sales.subtotal) * sales.paid,
                                2
                            )
                            -- Use regular subtotal for earlier dates
                            ELSE sale_items.subtotal
                        END
                    ), 0
                ) AS DECIMAL(15,2)
            )
        ', [$discountCutoffDate]);
    }

    protected static function calculateProductMetrics($product, $index, $stockPositions, $beforeStockPositions)
    {
        $stockPosition = $stockPositions[$product->id] ?? null;
        $beforeStockPosition = $beforeStockPositions[$product->id] ?? null;

        $beforeQuantity = $beforeStockPosition ? (float) $beforeStockPosition->before_quantity : 0;
        $beforeAvgCost = $beforeStockPosition ? (float) $beforeStockPosition->before_avg_cost : 0;
        $beforeStockValue = $beforeQuantity * $beforeAvgCost;

        $allTimeBuyPrice = (float) $product->all_time_buy_price;
        $effectiveCost = $allTimeBuyPrice > 0 ? $allTimeBuyPrice : $beforeAvgCost;

        $salePrice = $product->sale_quantity > 0
            ? (float) ($product->total_sale_amount / $product->sale_quantity)
            : 0;

        $availableQuantity = $stockPosition ? (float) $stockPosition->available_quantity : 0;

        $totalStockValue = $effectiveCost * ($beforeQuantity + $product->buy_quantity);
        $totalSoldValue = $effectiveCost * $product->sale_quantity;
        $availableStockValue = $totalStockValue - $totalSoldValue;

        $profitPerUnit = $salePrice - $effectiveCost;
        $totalProfit = $product->sale_quantity * $profitPerUnit;

        return [
            'serial' => $index + 1,
            'product_name' => $product->name,
            'product_model' => $product->sku,
            'category' => $product->category->name,
            'unit' => $product->unit->name,
            'before_quantity' => $beforeQuantity,
            'before_price' => $beforeAvgCost,
            'before_value' => $beforeStockValue,
            'buy_quantity' => (float) $product->buy_quantity,
            'buy_price' => $allTimeBuyPrice,
            'total_buy_price' => (float) $product->total_buy_cost,
            'sale_quantity' => (float) $product->sale_quantity,
            'sale_price' => $salePrice,
            'total_sale_price' => (float) $product->total_sale_amount,
            'profit_per_unit' => $profitPerUnit,
            'total_profit' => $totalProfit,
            'available_quantity' => $availableQuantity,
            'available_stock_value' => $availableStockValue
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
                    'cumulative' => (float) ($productAnalysis['totals']['total_profit'] ?? 0)
                ]
            ];
        } catch (\Exception $e) {
            return [
                'total_profit' => [
                    'period' => 0.0,
                    'cumulative' => 0.0
                ]
            ];
        }
    }

}
