<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Expense;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => [
                [
                    'name' => 'Total Sales',
                    'value' => '৳' . number_format(Sale::sum('total')),
                    'icon' => 'CurrencyDollarIcon',
                    'change' => $this->calculateGrowth(
                        Sale::where('created_at', '>=', $currentMonth)->sum('total'),
                        Sale::whereBetween('created_at', [$lastMonth, $currentMonth])->sum('total')
                    ),
                    'changeType' => 'increase'
                ],
                [
                    'name' => 'Bank Balance',
                    'value' => '৳' . number_format(BankAccount::sum('current_balance')),
                    'icon' => 'BanknotesIcon',
                    'change' => '0%',
                    'changeType' => 'increase'
                ],
                [
                    'name' => 'Monthly Expense',
                    'value' => '৳' . number_format(Expense::where('date', '>=', $currentMonth)->sum('amount')),
                    'icon' => 'CalculatorIcon',
                    'change' => $this->calculateGrowth(
                        Expense::where('date', '>=', $currentMonth)->sum('amount'),
                        Expense::whereBetween('date', [$lastMonth, $currentMonth])->sum('amount')
                    ),
                    'changeType' => 'decrease'
                ],
                [
                    'name' => 'Active Customers',
                    'value' => number_format(Customer::where('status', true)->count()),
                    'icon' => 'UsersIcon',
                    'change' => $this->calculateGrowth(
                        Customer::where('created_at', '>=', $currentMonth)->count(),
                        Customer::whereBetween('created_at', [$lastMonth, $currentMonth])->count()
                    ),
                    'changeType' => 'increase'
                ]
            ],
            'recentSales' => Sale::with('customer')
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($sale) => [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'customer_name' => $sale->customer?->name ?? 'Walk-in Customer',
                    'total' => $sale->total,
                    'status' => $sale->payment_status,
                ]),
            'lowStockProducts' => Product::whereRaw('alert_quantity >= (
                    SELECT COALESCE(SUM(quantity), 0)
                    FROM product_stocks
                    WHERE product_stocks.product_id = products.id
                )')
                ->take(5)
                ->get()
                ->map(fn($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'image' => $product->primary_image?->image ?? '/placeholder.png',
                    'quantity' => $product->current_stock,
                    'alert_quantity' => $product->alert_quantity,
                ]),
            'bankAccounts' => BankAccount::select([
                'id',
                'account_name',
                'account_number',
                'bank_name',
                'current_balance'
            ])
                ->where('status', true)
                ->orderByDesc('current_balance')
                ->get(),
            'recentExpenses' => Expense::with('expenseCategory')
                ->select(['id', 'expense_category_id', 'amount', 'description', 'date'])
                ->latest('date')
                ->take(5)
                ->get()
                ->map(fn($expense) => [
                    'id' => $expense->id,
                    'category_name' => $expense->expenseCategory->name,
                    'amount' => $expense->amount,
                    'description' => $expense->description,
                    'date' => $expense->date->format('M d, Y'),
                ]),
            'salesData' => [
                'weekly' => $this->getSalesData('week'),
                'monthly' => $this->getSalesData('month'),
                'yearly' => $this->getSalesData('year'),
            ],
            'expenseData' => [
                'weekly' => $this->getExpenseData('week'),
                'monthly' => $this->getExpenseData('month'),
                'yearly' => $this->getExpenseData('year'),
            ]
        ]);
    }

    private function getSalesData($period)
    {
        $days = $period === 'week' ? 7 : ($period === 'month' ? 30 : 365);

        $query = Sale::selectRaw(
            $period === 'year'
                ? "DATE_FORMAT(created_at, '%Y-%m') as date, SUM(total) as amount"
                : "DATE(created_at) as date, SUM(total) as amount"
        )
            ->where('created_at', '>=', now()->subDays($days));

        if ($period === 'year') {
            $query->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')");
        } else {
            $query->groupBy('date');
        }

        return $query->orderBy('date')
            ->get()
            ->map(fn($sale) => [
                'date' => $period === 'year'
                    ? Carbon::parse($sale->date)->format('M Y')
                    : Carbon::parse($sale->date)->format('M d'),
                'amount' => (float)$sale->amount
            ])
            ->toArray();
    }

    private function getExpenseData($period)
    {
        $days = $period === 'week' ? 7 : ($period === 'month' ? 30 : 365);

        $query = Expense::selectRaw(
            $period === 'year'
                ? "DATE_FORMAT(date, '%Y-%m') as date, SUM(amount) as amount"
                : "DATE(date) as date, SUM(amount) as amount"
        )
            ->where('date', '>=', now()->subDays($days));

        if ($period === 'year') {
            $query->groupByRaw("DATE_FORMAT(date, '%Y-%m')");
        } else {
            $query->groupBy('date');
        }

        return $query->orderBy('date')
            ->get()
            ->map(fn($expense) => [
                'date' => $period === 'year'
                    ? Carbon::parse($expense->date)->format('M Y')
                    : Carbon::parse($expense->date)->format('M d'),
                'amount' => (float)$expense->amount
            ])
            ->toArray();
    }

    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) return '0%';
        $growth = (($current - $previous) / abs($previous)) * 100;
        return number_format($growth, 1) . '%';
    }
}
