<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FixedAsset;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExpenseAndAssetDateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_super_admin_can_set_custom_expense_date()
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $superAdmin = User::firstOrCreate(
            ['email' => 'test_superadmin@mail.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'role_id' => $superAdminRole->id, 'status' => 1]
        );

        $category = ExpenseCategory::firstOrCreate(['name' => 'Test Office Supplies'], ['status' => true]);
        $bank = BankAccount::firstOrCreate(['account_number' => 'ACC-TEST-001'], ['bank_name' => 'City Bank', 'account_name' => 'Test Account', 'status' => true, 'current_balance' => 50000]);

        $customDate = '2024-05-15';

        $response = $this->actingAs($superAdmin)->post(route('admin.expenses.store'), [
            'expense_category_id' => $category->id,
            'bank_account_id' => $bank->id,
            'amount' => 500,
            'description' => 'Test Expense by Super Admin',
            'date' => $customDate,
        ]);

        $response->assertSessionHas('success');

        $expense = Expense::where('description', 'Test Expense by Super Admin')->latest()->first();
        $this->assertNotNull($expense);
        $this->assertEquals($customDate, $expense->date->toDateString());
    }

    public function test_non_super_admin_cannot_change_expense_date_and_it_is_locked_to_today()
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $managerUser = User::firstOrCreate(
            ['email' => 'test_regular_manager@mail.com'],
            ['name' => 'Regular Manager', 'password' => Hash::make('password'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $category = ExpenseCategory::firstOrCreate(['name' => 'Test Office Supplies'], ['status' => true]);
        $bank = BankAccount::firstOrCreate(['account_number' => 'ACC-TEST-001'], ['bank_name' => 'City Bank', 'account_name' => 'Test Account', 'status' => true, 'current_balance' => 50000]);

        $tamperedDate = '2020-01-01';

        $response = $this->actingAs($managerUser)->post(route('admin.expenses.store'), [
            'expense_category_id' => $category->id,
            'bank_account_id' => $bank->id,
            'amount' => 300,
            'description' => 'Test Expense by Regular Manager',
            'date' => $tamperedDate,
        ]);

        $response->assertSessionHas('success');

        $expense = Expense::where('description', 'Test Expense by Regular Manager')->latest()->first();
        $this->assertNotNull($expense);
        $this->assertEquals(now()->toDateString(), $expense->date->toDateString());
    }

    public function test_super_admin_can_set_custom_fixed_asset_date()
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $superAdmin = User::firstOrCreate(
            ['email' => 'test_superadmin@mail.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'role_id' => $superAdminRole->id, 'status' => 1]
        );

        $bank = BankAccount::firstOrCreate(['account_number' => 'ACC-TEST-001'], ['bank_name' => 'City Bank', 'account_name' => 'Test Account', 'status' => true, 'current_balance' => 50000]);

        $customDate = '2024-03-20';

        $response = $this->actingAs($superAdmin)->post(route('admin.fixed-assets.store'), [
            'name' => 'Office AC Unit',
            'purchase_price' => 35000,
            'bank_account_id' => $bank->id,
            'purchase_date' => $customDate,
            'description' => 'Installed in Manager room',
        ]);

        $response->assertSessionHas('success');

        $asset = FixedAsset::with('items')->where('name', 'Office AC Unit')->latest()->first();
        $this->assertNotNull($asset);
        $this->assertNotEmpty($asset->items);
        $this->assertEquals($customDate, $asset->items->first()->purchase_date->toDateString());
    }

    public function test_non_super_admin_fixed_asset_date_is_locked_to_today()
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $managerUser = User::firstOrCreate(
            ['email' => 'test_regular_manager@mail.com'],
            ['name' => 'Regular Manager', 'password' => Hash::make('password'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $bank = BankAccount::firstOrCreate(['account_number' => 'ACC-TEST-001'], ['bank_name' => 'City Bank', 'account_name' => 'Test Account', 'status' => true, 'current_balance' => 50000]);

        $tamperedDate = '2019-12-25';

        $response = $this->actingAs($managerUser)->post(route('admin.fixed-assets.store'), [
            'name' => 'Office Desk',
            'purchase_price' => 8000,
            'bank_account_id' => $bank->id,
            'purchase_date' => $tamperedDate,
            'description' => 'Bought by manager',
        ]);

        $response->assertSessionHas('success');

        $asset = FixedAsset::with('items')->where('name', 'Office Desk')->latest()->first();
        $this->assertNotNull($asset);
        $this->assertNotEmpty($asset->items);
        $this->assertEquals(now()->toDateString(), $asset->items->first()->purchase_date->toDateString());
    }

    public function test_super_admin_can_update_expense_date()
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $superAdmin = User::firstOrCreate(
            ['email' => 'test_superadmin@mail.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'role_id' => $superAdminRole->id, 'status' => 1]
        );

        $category = ExpenseCategory::firstOrCreate(['name' => 'Test Office Supplies'], ['status' => true]);
        $bank = BankAccount::firstOrCreate(['account_number' => 'ACC-TEST-001'], ['bank_name' => 'City Bank', 'account_name' => 'Test Account', 'status' => true, 'current_balance' => 50000]);

        $expense = Expense::create([
            'expense_category_id' => $category->id,
            'bank_account_id' => $bank->id,
            'amount' => 1000,
            'description' => 'Original Expense',
            'date' => '2024-01-10',
            'created_by' => $superAdmin->id,
        ]);

        $updatedDate = '2024-02-15';

        $response = $this->actingAs($superAdmin)->put(route('admin.expenses.update', $expense->id), [
            'expense_category_id' => $category->id,
            'bank_account_id' => $bank->id,
            'amount' => 1200,
            'description' => 'Updated Expense by Super Admin',
            'date' => $updatedDate,
        ]);

        $response->assertSessionHas('success');
        $expense->refresh();
        $this->assertEquals($updatedDate, $expense->date->toDateString());
        $this->assertEquals('1200.00', $expense->amount);
    }

    public function test_non_super_admin_cannot_update_expense_date()
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $managerUser = User::firstOrCreate(
            ['email' => 'test_regular_manager@mail.com'],
            ['name' => 'Regular Manager', 'password' => Hash::make('password'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $category = ExpenseCategory::firstOrCreate(['name' => 'Test Office Supplies'], ['status' => true]);
        $bank = BankAccount::firstOrCreate(['account_number' => 'ACC-TEST-001'], ['bank_name' => 'City Bank', 'account_name' => 'Test Account', 'status' => true, 'current_balance' => 50000]);

        $originalDate = '2024-01-10';
        $expense = Expense::create([
            'expense_category_id' => $category->id,
            'bank_account_id' => $bank->id,
            'amount' => 1000,
            'description' => 'Original Manager Expense',
            'date' => $originalDate,
            'created_by' => $managerUser->id,
        ]);

        $tamperedDate = '2021-08-08';

        $response = $this->actingAs($managerUser)->put(route('admin.expenses.update', $expense->id), [
            'expense_category_id' => $category->id,
            'bank_account_id' => $bank->id,
            'amount' => 1100,
            'description' => 'Updated Expense by Manager',
            'date' => $tamperedDate,
        ]);

        $response->assertSessionHas('success');
        $expense->refresh();
        $this->assertEquals($originalDate, $expense->date->toDateString());
        $this->assertEquals('1100.00', $expense->amount);
    }

    public function test_expense_category_crud()
    {
        $uniqueSuffix = uniqid();
        $catName = "Special Test Cat {$uniqueSuffix}";
        $updatedCatName = "Updated Test Cat {$uniqueSuffix}";

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $admin = User::firstOrCreate(
            ['email' => 'test_admin_cat@mail.com'],
            ['name' => 'Admin Cat', 'password' => Hash::make('password'), 'role_id' => $adminRole->id, 'status' => 1]
        );

        $response = $this->actingAs($admin)->post(route('admin.expense-categories.store'), [
            'name' => $catName,
            'description' => 'For testing purposes',
            'status' => true,
        ]);

        $response->assertSessionHas('success');

        $category = ExpenseCategory::where('name', $catName)->first();
        $this->assertNotNull($category);

        $updateResponse = $this->actingAs($admin)->put(route('admin.expense-categories.update', $category->id), [
            'name' => $updatedCatName,
            'description' => 'Updated description',
            'status' => false,
        ]);

        $updateResponse->assertSessionHas('success');
        $category->refresh();
        $this->assertEquals($updatedCatName, $category->name);
        $this->assertFalse((bool) $category->status);
    }
}
