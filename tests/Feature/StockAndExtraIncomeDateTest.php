<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ExtraIncome;
use App\Models\ExtraIncomeCategory;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StockAndExtraIncomeDateTest extends TestCase
{
    protected User $superAdmin;
    protected User $regularManager;
    protected BankAccount $bank;
    protected Product $product;
    protected ExtraIncomeCategory $incomeCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->superAdmin = User::firstOrCreate(
            ['email' => 'test_stock_superadmin@mail.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'role_id' => $superAdminRole->id, 'status' => 1]
        );

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $this->regularManager = User::firstOrCreate(
            ['email' => 'test_stock_regular_manager@mail.com'],
            ['name' => 'Regular Manager', 'password' => Hash::make('password'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $this->bank = BankAccount::firstOrCreate(
            ['account_number' => 'ACC-STOCK-TEST-001'],
            ['bank_name' => 'BRAC Bank', 'account_name' => 'Stock Test Account', 'status' => true, 'current_balance' => 100000]
        );

        $category = Category::firstOrCreate(['slug' => 'test-category'], ['name' => 'Test Category', 'slug' => 'test-category', 'status' => true]);
        $brand = Brand::firstOrCreate(['name' => 'Test Brand'], ['name' => 'Test Brand', 'slug' => 'test-brand', 'status' => true]);
        $unit = Unit::firstOrCreate(['name' => 'Pcs'], ['name' => 'Pcs', 'short_name' => 'pcs', 'status' => true]);

        $this->product = Product::firstOrCreate(
            ['sku' => 'SKU-TEST-STOCK-001'],
            [
                'name' => 'Test Book',
                'slug' => 'test-book',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'unit_id' => $unit->id,
                'cost_price' => 100,
                'selling_price' => 150,
                'alert_quantity' => 5,
                'status' => true,
            ]
        );

        $this->incomeCategory = ExtraIncomeCategory::firstOrCreate(
            ['name' => 'Test Commission Income'],
            ['status' => true, 'created_by' => $this->superAdmin->id]
        );
    }

    public function test_super_admin_can_set_custom_stock_date()
    {
        $customDate = '2024-03-10';

        $response = $this->actingAs($this->superAdmin)->post(route('admin.product-stocks.store'), [
            'product_id' => $this->product->id,
            'quantity' => 10,
            'total_cost' => 1000,
            'date' => $customDate,
            'bank_account_id' => $this->bank->id,
            'note' => 'Stock added by Super Admin',
        ]);

        $response->assertSessionHas('success');

        $stock = ProductStock::where('product_id', $this->product->id)
            ->where('note', 'Stock added by Super Admin')
            ->latest('id')
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals($customDate, $stock->date);
    }

    public function test_non_super_admin_cannot_change_stock_date_and_it_is_locked_to_today()
    {
        $tamperedDate = '2022-01-01';

        $response = $this->actingAs($this->regularManager)->post(route('admin.product-stocks.store'), [
            'product_id' => $this->product->id,
            'quantity' => 5,
            'total_cost' => 500,
            'date' => $tamperedDate,
            'bank_account_id' => $this->bank->id,
            'note' => 'Stock added by Regular Manager',
        ]);

        $response->assertSessionHas('success');

        $stock = ProductStock::where('product_id', $this->product->id)
            ->where('note', 'Stock added by Regular Manager')
            ->latest('id')
            ->first();

        $this->assertNotNull($stock);
        $this->assertEquals(now()->toDateString(), $stock->date);
    }

    public function test_super_admin_can_set_custom_extra_income_date()
    {
        $customDate = '2024-02-14';

        $response = $this->actingAs($this->superAdmin)->post(route('admin.extra-incomes.store'), [
            'bank_account_id' => $this->bank->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 1500,
            'title' => 'Extra Income Super Admin Test',
            'description' => 'Test extra income',
            'date' => $customDate,
        ]);

        $response->assertSessionHas('success');

        $income = ExtraIncome::where('title', 'Extra Income Super Admin Test')->latest('id')->first();
        $this->assertNotNull($income);
        $this->assertEquals($customDate, $income->date->toDateString());
    }

    public function test_non_super_admin_cannot_change_extra_income_date_on_store()
    {
        $tamperedDate = '2021-06-15';

        $response = $this->actingAs($this->regularManager)->post(route('admin.extra-incomes.store'), [
            'bank_account_id' => $this->bank->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 800,
            'title' => 'Extra Income Manager Store Test',
            'description' => 'Test extra income',
            'date' => $tamperedDate,
        ]);

        $response->assertSessionHas('success');

        $income = ExtraIncome::where('title', 'Extra Income Manager Store Test')->latest('id')->first();
        $this->assertNotNull($income);
        $this->assertEquals(now()->toDateString(), $income->date->toDateString());
    }

    public function test_non_super_admin_cannot_change_extra_income_date_on_update()
    {
        $income = ExtraIncome::create([
            'bank_account_id' => $this->bank->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 600,
            'title' => 'Extra Income To Update',
            'description' => 'Original description',
            'date' => '2024-01-01',
            'created_by' => $this->regularManager->id,
        ]);

        $tamperedDate = '2020-05-20';

        $response = $this->actingAs($this->regularManager)->put(route('admin.extra-incomes.update', $income->id), [
            'bank_account_id' => $this->bank->id,
            'category_id' => $this->incomeCategory->id,
            'amount' => 700,
            'title' => 'Extra Income Updated By Manager',
            'description' => 'Updated description',
            'date' => $tamperedDate,
        ]);

        $response->assertSessionHas('success');

        $income->refresh();
        $this->assertEquals(now()->toDateString(), $income->date->toDateString());
    }
}
