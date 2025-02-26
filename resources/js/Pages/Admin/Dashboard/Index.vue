<template>
    <AdminLayout>

        <Head title="Dashboard" />

        <!-- Header Section -->
        <div
            class="sm:flex sm:items-center sm:justify-between py-4 px-4 sm:px-6 lg:px-8 border-b border-gray-200 dark:border-gray-700">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Dashboard
                </h1>
                <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                    {{ currentPeriod }}
                </p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-4">
                <div class="flex space-x-3">
                    <!-- Date Range Selector -->
                    <div class="relative inline-block">
                        <select v-model="selectedRange"
                            class="block w-40 rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <!-- Refresh Button -->
                    <button @click="refreshData"
                        class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <ArrowPathIcon class="h-5 w-5 text-gray-400" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Stats Grid -->
                <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Bank Balance Card -->
                    <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
                        <dt>
                            <div class="absolute rounded-md bg-blue-500 p-3">
                                <BanknotesIcon class="h-6 w-6 text-white" aria-hidden="true" />
                            </div>
                            <p class="ml-16 truncate text-sm font-medium text-gray-500">Bank Balance</p>
                        </dt>
                        <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                            <p class="text-2xl font-semibold text-gray-900">{{
                                formatCurrency(summary.banking.total_balance) }}</p>
                            <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900">{{ summary.banking.accounts.length }}</span>
                                    <span class="text-gray-600"> active accounts</span>
                                </div>
                            </div>
                        </dd>
                    </div>

                    <!-- Sales Card -->
                    <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
                        <dt>
                            <div class="absolute rounded-md bg-green-500 p-3">
                                <CurrencyDollarIcon class="h-6 w-6 text-white" aria-hidden="true" />
                            </div>
                            <p class="ml-16 truncate text-sm font-medium text-gray-500">Total Sales</p>
                        </dt>
                        <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                            <p class="text-2xl font-semibold text-gray-900">{{
                                formatCurrency(summary.sales.total_amount) }}</p>
                            <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900">{{ summary.sales.total_transactions
                                        }}</span>
                                    <span class="text-gray-600"> transactions</span>
                                </div>
                            </div>
                        </dd>
                    </div>

                    <!-- Expenses Card -->
                    <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
                        <dt>
                            <div class="absolute rounded-md bg-red-500 p-3">
                                <BanknotesIcon class="h-6 w-6 text-white" aria-hidden="true" />
                            </div>
                            <p class="ml-16 truncate text-sm font-medium text-gray-500">Total Expenses</p>
                        </dt>
                        <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                            <p class="text-2xl font-semibold text-gray-900">
                                {{ formatCurrency(summary.profit.operating_expenses) }}
                            </p>
                            <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                                <div class="text-sm">
                                    <span class="font-medium text-gray-900">{{ expensesData.length }}</span>
                                    <span class="text-gray-600"> transactions</span>
                                </div>
                            </div>
                        </dd>
                    </div>

                    <!-- Net Profit Card -->
                    <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
                        <dt>
                            <div class="absolute rounded-md bg-indigo-500 p-3">
                                <ChartBarIcon class="h-6 w-6 text-white" aria-hidden="true" />
                            </div>
                            <p class="ml-16 truncate text-sm font-medium text-gray-500">Net Profit</p>
                        </dt>
                        <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                            <p class="text-2xl font-semibold text-gray-900">{{ formatCurrency(summary.profit.net_profit)
                                }}</p>
                            <p class="ml-2 flex items-baseline text-sm font-semibold"
                                :class="profitTrend >= 0 ? 'text-green-600' : 'text-red-600'">
                                <component :is="profitTrend >= 0 ? ArrowUpIcon : ArrowDownIcon"
                                    class="h-5 w-5 flex-shrink-0 self-center" aria-hidden="true" />
                                <span class="sr-only">{{ profitTrend >= 0 ? 'Increased' : 'Decreased' }} by</span>
                                {{ Math.abs(profitTrend) }}%
                            </p>
                        </dd>
                    </div>





                    <!-- Stock Value Card -->
                    <!-- <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
                        <dt>
                            <div class="absolute rounded-md bg-purple-500 p-3">
                                <ArchiveBoxIcon class="h-6 w-6 text-white" aria-hidden="true" />
                            </div>
                            <p class="ml-16 truncate text-sm font-medium text-gray-500">Stock Value</p>
                        </dt>
                        <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                            <p class="text-2xl font-semibold text-gray-900">{{
                                formatCurrency(summary.stock.current_value) }}</p>
                            <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                                <div class="text-sm">
                                    <span class="font-medium text-red-600">{{ summary.stock.low_stock_count }}</span>
                                    <span class="text-gray-600"> low stock items</span>
                                </div>
                            </div>
                        </dd>
                    </div> -->


                    <!-- Stock Wrowth Card -->
                    <!-- <div class="relative overflow-hidden rounded-lg bg-white px-4 pb-12 pt-5 shadow sm:px-6 sm:pt-6">
                        <dt>
                            <div class="absolute rounded-md bg-amber-500 p-3">
                                <ArchiveBoxIcon class="h-6 w-6 text-white" aria-hidden="true" />
                            </div>
                            <p class="ml-16 truncate text-sm font-medium text-gray-500">Stock Worth</p>
                        </dt>
                        <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
                            <p class="text-2xl font-semibold text-gray-900">
                                {{ formatCurrency(summary.stock.potential_value) }}
                            </p>
                            <div class="absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6">
                                <div class="text-sm flex justify-between">
                                    <div>
                                        <span class="text-gray-600">ROI: </span>
                                        <span class="font-medium text-green-600">{{ calculateProfitPercentage }}%</span>
                                    </div>
                                </div>
                            </div>
                        </dd>
                    </div> -->
                </dl>





                <!-- Recent Activity -->
                <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Recent Sales -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Recent Sales</h3>
                            <div class="mt-4 flow-root">
                                <div class="-my-5 divide-y divide-gray-200">
                                    <div v-for="sale in salesData.slice(0, 5)" :key="sale.id" class="py-5">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">Invoice #{{ sale.invoice_no
                                                    }}</p>
                                                <p class="text-sm text-gray-500">{{ formatDate(sale.created_at) }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-gray-900">{{
                                                    formatCurrency(sale.total) }}</p>
                                                <span :class="[
                                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                    sale.payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                                ]">
                                                    {{ sale.payment_status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6">
                                <Link href="/admin/sales"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                View all sales
                                <span aria-hidden="true"> &rarr;</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Bank Transactions -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Recent Transactions</h3>
                            <div class="mt-4 flow-root">
                                <div class="-my-5 divide-y divide-gray-200">
                                    <div v-for="transaction in bankTransactions.slice(0, 5)" :key="transaction.id"
                                        class="py-5">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ transaction.bankAccount?.account_name }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    {{ transaction.description || 'No description' }}
                                                </p>
                                                <p class="text-xs text-gray-400">
                                                    {{ formatDate(transaction.date) }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p :class="[
                                                    'text-sm font-medium',
                                                    transaction.transaction_type === 'in' ? 'text-green-600' : 'text-red-600'
                                                ]">
                                                    {{ transaction.transaction_type === 'in' ? '+' : '-' }}
                                                    {{ formatCurrency(transaction.amount) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6">
                                <Link href="/admin/bank-transactions"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                View all transactions
                                <span aria-hidden="true"> &rarr;</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white shadow rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900">Recent Expenses</h3>
                            <div class="mt-4 flow-root">
                                <div class="-my-5 divide-y divide-gray-200">
                                    <div v-for="expense in expensesData.slice(0, 5)" :key="expense.id" class="py-5">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ expense.expenseCategory?.name }}
                                                </p>
                                                <p class="text-sm text-gray-500">
                                                    {{ expense.description || 'No description' }}
                                                </p>
                                                <p class="text-xs text-gray-400">
                                                    {{ formatDate(expense.date) }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-red-600">
                                                    -{{ formatCurrency(expense.amount) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6">
                                <Link href="/admin/expenses"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                View all expenses
                                <span aria-hidden="true"> &rarr;</span>
                                </Link>
                            </div>
                        </div>
                    </div>



                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
    ArrowPathIcon,
    ChartBarIcon,
    CurrencyDollarIcon,
    BanknotesIcon,
    ArchiveBoxIcon,
    ArrowUpIcon,
    ArrowDownIcon,
    ArrowTrendingUpIcon
} from '@heroicons/vue/24/outline'
import {
    format,
    startOfMonth,
    endOfMonth,
    startOfWeek,
    differenceInWeeks,
    differenceInDays,
    sub,
    parseISO,
    isValid
} from 'date-fns'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { formatCurrency } from '@/utils'

const props = defineProps({
    salesData: Array,
    expensesData: Array,
    bankTransactions: Array,
    extraIncomeData: Array,
    summary: Object,
    dailySummary: Array,
    bankingSummary: Array,
    filters: Object
})

const selectedRange = ref(props.filters?.startDate ? 'custom' : 'month')

// Calculate profit trend
const profitTrend = computed(() => {
    try {
        const currentProfit = props.summary.profit.net_profit || 0
        const previousProfit = props.summary.profit.previous_period_profit || 0

        if (previousProfit === 0) return 0

        let percentageChange = ((currentProfit - previousProfit) / Math.abs(previousProfit)) * 100
        return Number(percentageChange.toFixed(1))
    } catch (error) {
        console.error('Error calculating profit trend:', error)
        return 0
    }
})

const calculateProfitPercentage = computed(() => {
    const costValue = props.summary.stock.current_value || 0
    const potentialProfit = props.summary.stock.potential_profit || 0

    if (costValue === 0) return 0

    return Number(((potentialProfit / costValue) * 100).toFixed(1))
})




// Current period display
const currentPeriod = computed(() => {
    try {
        const start = parseISO(props.filters.startDate)
        const end = parseISO(props.filters.endDate)

        if (!isValid(start) || !isValid(end)) {
            return 'Select Date Range'
        }

        if (format(start, 'yyyy-MM-dd') === format(end, 'yyyy-MM-dd')) {
            return format(start, 'MMMM d, yyyy')
        }
        return `${format(start, 'MMM d')} - ${format(end, 'MMM d, yyyy')}`
    } catch (error) {
        console.error('Error formatting period:', error)
        return 'Invalid Date Range'
    }
})

function formatDate(date) {
    try {
        const parsedDate = typeof date === 'string' ? parseISO(date) : date
        return isValid(parsedDate) ? format(parsedDate, 'MMM d, yyyy') : 'Invalid Date'
    } catch {
        return 'Invalid Date'
    }
}

function getWeekOfMonth(date) {
    try {
        const parsedDate = typeof date === 'string' ? parseISO(date) : date
        if (!isValid(parsedDate)) return 1

        const start = startOfMonth(parsedDate)
        const firstWeek = startOfWeek(start)
        const targetWeek = startOfWeek(parsedDate)

        return differenceInWeeks(targetWeek, firstWeek) + 1
    } catch {
        return 1
    }
}

function updateDateRange(range) {
    let startDate = new Date()
    let endDate = new Date()

    switch (range) {
        case 'today':
            break
        case 'week':
            startDate = sub(endDate, { weeks: 1 })
            break
        case 'month':
            startDate = sub(endDate, { months: 1 })
            break
        case 'year':
            startDate = sub(endDate, { years: 1 })
            break
        default:
            return
    }

    router.get(
        route('admin.dashboard'),
        {
            start_date: format(startDate, 'yyyy-MM-dd'),
            end_date: format(endDate, 'yyyy-MM-dd')
        },
        {
            preserveState: true,
            preserveScroll: true
        }
    )
}

function refreshData() {
    router.reload()
}

// Watch for changes
watch(selectedRange, (newValue) => {
    updateDateRange(newValue)
})

// Lifecycle
onMounted(() => {
    if (!props.filters.startDate) {
        updateDateRange('month')
    }
})
</script>

<style scoped>
.chart-container {
    position: relative;
    height: 300px;
}

.stats-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

@media (min-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.stat-card {
    @apply relative overflow-hidden rounded-lg bg-white px-4 pt-5 pb-12 shadow sm:px-6 sm:pt-6;
}

.stat-icon {
    @apply absolute rounded-md p-3;
}

.stat-value {
    @apply ml-16 text-2xl font-semibold text-gray-900;
}

.stat-label {
    @apply ml-16 truncate text-sm font-medium text-gray-500;
}

.stat-footer {
    @apply absolute inset-x-0 bottom-0 bg-gray-50 px-4 py-4 sm:px-6;
}
</style>
