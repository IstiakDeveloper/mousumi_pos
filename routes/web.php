<?php

use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\BankReportController;
use App\Http\Controllers\Admin\BankTransactionController;
use App\Http\Controllers\Admin\BarcodeController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerSalesReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\ExtraIncomeController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStockController;
use App\Http\Controllers\Admin\ProductStockReportController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\SaleReportController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('categories', CategoryController::class);
    Route::resource('units', UnitController::class);
    Route::resource('brands', BrandController::class);
    Route::resource('products', ProductController::class);
    Route::post('/products/barcode/print', [ProductController::class, 'printBarcodes'])
        ->name('products.barcode.print');
    Route::resource('product-stocks', ProductStockController::class)
        ->only(['index', 'create', 'store']);
    Route::resource('customers', CustomerController::class);
    Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])
        ->name('customers.toggle-status');
    Route::post('customers/{customer}/add-payment', [CustomerController::class, 'addPayment'])
        ->name('customers.add-payment');
    Route::resource('bank-accounts', BankAccountController::class);
    Route::resource('bank-transactions', BankTransactionController::class);
    // Route::resource('sales', SaleController::class);
    Route::resource('extra-incomes', ExtraIncomeController::class);

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/search-products', [PosController::class, 'searchProducts'])->name('pos.search-products');
    Route::post('/pos/store', [PosController::class, 'store'])->name('pos.store');
    Route::get('pos/search-by-barcode', [PosController::class, 'searchByBarcode'])
        ->name('pos.search-by-barcode');

    Route::get('/pos/print-receipt/{id}', [PosController::class, 'printReceipt'])
        ->name('pos.print-receipt');
    Route::get('/pos/products-by-category', [PosController::class, 'productsByCategory'])
        ->name('pos.products.by.category');
    Route::get('/pos/products', [PosController::class, 'products'])
        ->name('pos.products');

    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SaleController::class, 'index'])->name('index');
        Route::get('/{id}', [SaleController::class, 'show'])->name('show');
        Route::delete('/{sale}', [SaleController::class, 'destroy'])->name('destroy');
        Route::get('/print/{id}', [SaleController::class, 'printReceipt'])->name('print-receipt');
    });
    Route::post('/products/{product}/barcode', [BarcodeController::class, 'generate']);
    Route::post('/products/barcode/print', [BarcodeController::class, 'print']);


    Route::get('/reports/bank', [BankReportController::class, 'index'])->name('reports.bank');
    Route::get('/reports/bank/download', [BankReportController::class, 'downloadPdf'])
        ->name('reports.bank.download');

    Route::get('/reports/stock', [ProductStockReportController::class, 'index'])
        ->name('reports.stock');
    Route::get('/reports/stock/download', [ProductStockReportController::class, 'downloadPdf'])
        ->name('reports.stock.download');

    Route::get('/reports/sales', [SaleReportController::class, 'index'])
        ->name('reports.sales');
    Route::get('/reports/sales/download', [SaleReportController::class, 'downloadPdf'])
        ->name('reports.sales.download');

    Route::get('/reports/customer-sales', [CustomerSalesReportController::class, 'index'])
        ->name('reports.customer-sales.index');

    Route::resource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/restore', [ExpenseController::class, 'restore'])->name('expenses.restore');
    Route::resource('expense-categories', ExpenseCategoryController::class);

});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/users', [DashboardController::class, 'users'])->name('admin.users');
});

require __DIR__ . '/auth.php';
