<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\ExtraIncome;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $salesAgg = DB::table('sales')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total), 0) as total, COALESCE(SUM(due), 0) as due, COUNT(*) as cnt')
            ->first();

        $expensesTotal = (float) DB::table('expenses as e')
            ->join('expense_categories as ec', 'e.expense_category_id', '=', 'ec.id')
            ->whereBetween('e.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('ec.name', '!=', 'Fixed Asset')
            ->whereNull('e.deleted_at')
            ->sum('e.amount');

        $extraIncome = (float) ExtraIncome::query()
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereNull('deleted_at')
            ->sum('amount');

        $openingTotal = (float) BankAccount::query()
            ->where('status', true)
            ->sum('opening_balance');

        $txNet = (float) DB::table('bank_transactions as bt')
            ->join('bank_accounts as ba', 'bt.bank_account_id', '=', 'ba.id')
            ->where('ba.status', true)
            ->whereNull('bt.deleted_at')
            ->where('bt.date', '<=', $endDate->toDateString())
            ->sum(DB::raw("CASE WHEN bt.transaction_type = 'in' THEN bt.amount ELSE -bt.amount END"));

        $bankBalance = $openingTotal + $txNet;

        $salesTotal = (float) ($salesAgg->total ?? 0);
        $netProfit = $salesTotal + $extraIncome - $expensesTotal;

        $productsCount = Product::query()->where('status', true)->count();

        $periodLabel = $startDate->isSameDay($endDate)
            ? $startDate->format('M j, Y')
            : $startDate->format('M j') . ' – ' . $endDate->format('M j, Y');

        return Inertia::render('Admin/Dashboard/Index', [
            'stats' => [
                'sales_total' => $salesTotal,
                'sales_count' => (int) ($salesAgg->cnt ?? 0),
                'sales_due' => (float) ($salesAgg->due ?? 0),
                'expenses_total' => $expensesTotal,
                'extra_income' => $extraIncome,
                'net_profit' => $netProfit,
                'bank_balance' => $bankBalance,
                'products_count' => $productsCount,
            ],
            'filters' => [
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
                'periodLabel' => $periodLabel,
            ],
        ]);
    }
}
