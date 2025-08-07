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
            ->where('status', true) // Only active products
            ->select('products.*')
            ->addSelect([
                'all_time_buy_price' => static::getAllTimeBuyPriceQuery(),
                'buy_quantity' => static::getBuyQuantityQuery($startDate, $endDate),
                'total_buy_cost' => static::getTotalBuyCostQuery($startDate, $endDate),
                'sale_quantity' => static::getSaleQuantityQuery($startDate, $endDate),
                'total_sale_amount' => static::getTotalSaleAmountQuery($startDate, $endDate),
                'sale_discount' => static::getTotalSaleDiscountQuery($startDate, $endDate),
                'sale_amount_after_discount' => static::getSaleAmountAfterDiscountQuery($startDate, $endDate)
            ])
            ->orderBy('products.name')
            ->get()
            ->map(function ($product, $index) use ($stockPositions, $beforeStockPositions) {
                return static::calculateProductMetrics($product, $index, $stockPositions, $beforeStockPositions);
            })
            ->filter(function($product) {
                // Filter out products with no activity (no before stock, buy, sale, or available)
                return $product['before_quantity'] > 0 ||
                       $product['buy_quantity'] > 0 ||
                       $product['sale_quantity'] > 0 ||
                       $product['available_quantity'] > 0;
            });

        return [
            'products' => $products->values(), // Reset keys after filtering
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
        // Get the latest stock position for each product up to the end date
        $stockPositions = DB::table('product_stocks as ps')
            ->select('ps.product_id', 'ps.available_quantity')
            ->where('ps.created_at', '<=', $endDate)
            ->whereIn('ps.id', function ($query) use ($endDate) {
                $query->selectRaw('MAX(id)')
                    ->from('product_stocks')
                    ->where('created_at', '<=', $endDate)
                    ->groupBy('product_id');
            })
            ->get()
            ->keyBy('product_id');

        // If no stock entries found, calculate from purchases and sales
        $productsWithNoStock = Product::whereNotIn('id', $stockPositions->pluck('product_id')->toArray())
            ->pluck('id');

        foreach ($productsWithNoStock as $productId) {
            // Calculate total purchases up to end date
            $totalPurchases = ProductStock::where('product_id', $productId)
                ->where('type', 'purchase')
                ->where('created_at', '<=', $endDate)
                ->sum('quantity');

            // Calculate total sales up to end date
            $totalSales = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $productId)
                ->where('sales.created_at', '<=', $endDate)
                ->whereNull('sales.deleted_at')
                ->sum('sale_items.quantity');

            $availableQty = $totalPurchases - $totalSales;

            $stockPositions[$productId] = (object) [
                'product_id' => $productId,
                'available_quantity' => $availableQty,
                'weighted_avg_cost' => 0
            ];
        }

        return $stockPositions;
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
        return DB::table('sale_items')
            ->selectRaw('CAST(COALESCE(SUM(sale_items.quantity), 0) AS DECIMAL(15,6))')
            ->whereColumn('sale_items.product_id', 'products.id')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNull('sales.deleted_at');
    }

    protected static function getTotalSaleAmountQuery($startDate, $endDate)
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereColumn('sale_items.product_id', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNull('sales.deleted_at')
            ->selectRaw('
                CAST(
                    COALESCE(
                        SUM(sale_items.subtotal), 0
                    ) AS DECIMAL(15,2)
                )
            ');
    }

    protected static function getTotalSaleDiscountQuery($startDate, $endDate)
    {
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
                                WHEN sales.subtotal > 0
                                THEN (sale_items.subtotal / sales.subtotal) * sales.discount
                                ELSE 0
                            END
                        ), 0
                    ) AS DECIMAL(15,2)
                )
            ');
    }

    protected static function getSaleAmountAfterDiscountQuery($startDate, $endDate)
    {
        return DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereColumn('sale_items.product_id', 'products.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereNull('sales.deleted_at')
            ->selectRaw('
                CAST(
                    COALESCE(
                        SUM(
                            sale_items.subtotal -
                            CASE
                                WHEN sales.subtotal > 0
                                THEN (sale_items.subtotal / sales.subtotal) * sales.discount
                                ELSE 0
                            END
                        ), 0
                    ) AS DECIMAL(15,2)
                )
            ');
    }

    protected static function calculateProductMetrics($product, $index, $stockPositions, $beforeStockPositions)
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

        // Calculate effective cost for available stock value
        // If we have history, use weighted average; otherwise use buy price
        $totalQuantity = $beforeQuantity + $buyQuantity;
        $totalCost = $beforeStockValue + $totalBuyCost;
        $effectiveCost = $totalQuantity > 0 ? $totalCost / $totalQuantity : $buyPrice;

        // Use effective cost for available stock value
        $availableStockValue = $availableQuantity * $effectiveCost;

        $profitPerUnit = $salePriceAfterDiscount - $effectiveCost;
        $totalProfit = $saleQuantity * $profitPerUnit;

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
