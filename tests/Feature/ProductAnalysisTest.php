<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAnalysisTest extends TestCase
{
    use RefreshDatabase;

    protected Role $role;

    protected User $user;

    protected Category $category;

    protected Brand $brand;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a role for foreign key constraint
        $this->role = Role::create(['name' => 'Admin', 'slug' => 'admin']);

        // Create a user for foreign key constraint
        $this->user = User::factory()->create(['role_id' => $this->role->id]);

        // Create necessary related records
        $this->category = Category::factory()->create(['name' => 'Test Category']);
        $this->brand = Brand::factory()->create(['name' => 'Test Brand']);
        $this->unit = Unit::factory()->create(['name' => 'PCS', 'short_name' => 'pc']);
    }

    public function test_monthly_stock_value_calculation_is_accurate()
    {
        // Create a product
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-001',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'unit_id' => $this->unit->id,
            'cost_price' => 100,
            'selling_price' => 150,
            'status' => true,
        ]);

        // October: Purchase 100 units at 100 each = 10,000
        $octoberStock = ProductStock::create([
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 100,
            'unit_cost' => 100,
            'total_cost' => 10000,
            'available_quantity' => 100,
            'note' => 'October Purchase',
            'date' => Carbon::parse('2025-10-15'),
            'created_by' => $this->user->id,
        ]);
        $octoberStock->created_at = Carbon::parse('2025-10-15');
        $octoberStock->save();

        // November 1: Before month stock = 100 units @ 100 = 10,000
        // November: Purchase 50 units at 120 each = 6,000
        $novemberStock = ProductStock::create([
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 50,
            'unit_cost' => 120,
            'total_cost' => 6000,
            'available_quantity' => 150,
            'note' => 'November Purchase',
            'date' => Carbon::parse('2025-11-10'),
            'created_by' => $this->user->id,
        ]);
        $novemberStock->created_at = Carbon::parse('2025-11-10');
        $novemberStock->save();

        // Create sale in November: 80 units
        $sale = Sale::create([
            'invoice_no' => 'INV-001',
            'customer_id' => null,
            'subtotal' => 12000, // 80 * 150
            'discount' => 0,
            'total' => 12000,
            'paid' => 12000,
            'due' => 0,
            'payment_status' => 'paid',
            'created_by' => $this->user->id,
        ]);
        $sale->created_at = Carbon::parse('2025-11-20');
        $sale->save();

        SaleItem::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 80,
            'unit_price' => 150,
            'subtotal' => 12000,
        ]);

        // Run analysis for November
        $startDate = Carbon::parse('2025-11-01')->startOfDay();
        $endDate = Carbon::parse('2025-11-30')->endOfDay();

        $analysis = Product::getProductAnalysis($startDate, $endDate);

        // Extract the product data
        $productData = $analysis['products']->first();

        // Assertions
        $this->assertNotNull($productData, 'Product data should not be null');

        // Before values (October 31)
        $this->assertEquals(100, $productData['before_quantity'], 'Before quantity should be 100');
        $this->assertEquals(100, $productData['before_price'], 'Before price should be 100');
        $this->assertEquals(10000, $productData['before_value'], 'Before value should be 10,000');

        // November purchases
        $this->assertEquals(50, $productData['buy_quantity'], 'Buy quantity should be 50');
        $this->assertEquals(120, $productData['buy_price'], 'Buy price should be 120');
        $this->assertEquals(6000, $productData['total_buy_price'], 'Total buy price should be 6,000');

        // November sales
        $this->assertEquals(80, $productData['sale_quantity'], 'Sale quantity should be 80');
        $this->assertEquals(12000, $productData['total_sale_price'], 'Total sale should be 12,000');

        // Weighted average cost calculation:
        // Total cost up to Nov 30 = 10,000 (Oct) + 6,000 (Nov) = 16,000
        // Total quantity = 100 + 50 = 150
        // Weighted avg = 16,000 / 150 = 106.67 (approx)
        $expectedWeightedAvg = 16000 / 150;

        // Sales cost = 80 * 106.67 = 8,533.33
        $expectedSalesCost = 80 * $expectedWeightedAvg;

        // Expected stock value = Before (10,000) + Purchases (6,000) - Sales Cost (8,533.33) = 7,466.67
        $expectedStockValue = 10000 + 6000 - $expectedSalesCost;

        $this->assertEqualsWithDelta(
            $expectedStockValue,
            $productData['available_stock_value'],
            1, // Allow 1 unit tolerance for rounding
            'Stock value should be calculated as: Before Value + Purchases - Sales Cost'
        );

        // Expected profit = Sale Amount (12,000) - Sales Cost (8,533.33) = 3,466.67
        $expectedProfit = 12000 - $expectedSalesCost;

        $this->assertEqualsWithDelta(
            $expectedProfit,
            $productData['total_profit'],
            1,
            'Profit should be calculated as: Sale Amount - Sales Cost'
        );

        // Available quantity should be 70 (100 + 50 - 80)
        $this->assertEquals(70, $productData['available_quantity'], 'Available quantity should be 70');
    }

    public function test_cumulative_stock_value_remains_accurate()
    {
        // Create a product
        $product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-001',
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'unit_id' => $this->unit->id,
            'cost_price' => 100,
            'selling_price' => 150,
            'status' => true,
        ]);

        // Purchase: 100 units at 100 each
        $stock = ProductStock::create([
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 100,
            'unit_cost' => 100,
            'total_cost' => 10000,
            'available_quantity' => 100,
            'date' => Carbon::now()->subDays(10),
            'created_by' => $this->user->id,
        ]);
        $stock->created_at = Carbon::now()->subDays(10);
        $stock->save();

        // Run cumulative analysis (all time)
        $startDate = Carbon::now()->startOfCentury();
        $endDate = Carbon::now()->endOfDay();

        $analysis = Product::getProductAnalysis($startDate, $endDate);
        $productData = $analysis['products']->first();

        // Cumulative: No before stock
        $this->assertEquals(0, $productData['before_value'], 'Cumulative should have no before value');

        // Total purchase = 10,000
        $this->assertEquals(10000, $productData['total_buy_price'], 'Total purchase should be 10,000');

        // No sales, so stock value should equal total purchases
        $this->assertEquals(10000, $productData['available_stock_value'], 'Stock value should equal total purchases when no sales');
    }
}
