<template>

    <Head title="Dashboard" />

    <AdminLayout>
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div v-for="stat in stats" :key="stat.name"
                class="bg-white dark:bg-gray-800 overflow-hidden rounded-lg shadow">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <component :is="stat.icon" class="h-6 w-6 text-gray-400 dark:text-gray-500"
                                aria-hidden="true" />
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                    {{ stat.name }}
                                </dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ stat.value }}
                                    </div>
                                    <div :class="[
                                        stat.changeType === 'increase'
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-red-600 dark:text-red-400',
                                        'ml-2 flex items-baseline text-sm font-semibold'
                                    ]">
                                        <component :is="stat.changeType === 'increase' ? ArrowUpIcon : ArrowDownIcon"
                                            class="h-4 w-4 flex-shrink-0 self-center" aria-hidden="true" />
                                        <span class="sr-only">
                                            {{ stat.changeType === 'increase' ? 'Increased' : 'Decreased' }} by
                                        </span>
                                        {{ stat.change }}
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Bank Accounts Overview -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Bank Accounts</h2>
                        <Link href="/admin/bank-accounts"
                            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                        Manage Accounts
                        </Link>
                    </div>
                    <div class="flow-root">
                        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li v-for="account in bankAccounts" :key="account.id" class="py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ account.account_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ account.bank_name }} - {{ account.account_number }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            ৳{{ formatNumber(account.current_balance) }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recent Expenses -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Expenses</h2>
                        <Link href="/admin/expenses"
                            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                        View all
                        </Link>
                    </div>
                    <div class="flow-root">
                        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li v-for="expense in recentExpenses" :key="expense.id" class="py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ expense.category_name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ expense.date }} - {{ expense.description }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-red-600 dark:text-red-400">
                                            ৳{{ formatNumber(expense.amount) }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Sales -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Sales</h2>
                        <Link href="/admin/sales"
                            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                        View all
                        </Link>
                    </div>
                    <div class="flow-root">
                        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">
                            <li v-for="sale in recentSales" :key="sale.id" class="py-4">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ sale.invoice_no }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                            {{ sale.customer_name }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            ৳{{ formatNumber(sale.total) }}
                                        </p>
                                        <p :class="[
                                            sale.status === 'paid'
                                                ? 'text-green-500 dark:text-green-400'
                                                : sale.status === 'partial'
                                                    ? 'text-yellow-500 dark:text-yellow-400'
                                                    : 'text-red-500 dark:text-red-400',
                                            'text-sm'
                                        ]">
                                            {{ sale.status }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Low Stock Products -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="p-6">
                    <!-- Same as before -->
                </div>
            </div>

            <!-- Charts Section -->
            <div class="col-span-1 lg:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sales Chart -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Sales Overview</h2>
                        <select v-model="chartPeriod"
                            class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
                            <option value="week">Last 7 days</option>
                            <option value="month">Last 30 days</option>
                            <option value="year">Last 12 months</option>
                        </select>
                    </div>
                    <div class="h-80">
                        <line-chart :data="chartData" />
                    </div>
                </div>

                <!-- Expense Chart -->
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-white">Expense Overview</h2>
                        <select v-model="expenseChartPeriod"
                            class="rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm">
                            <option value="week">Last 7 days</option>
                            <option value="month">Last 30 days</option>
                            <option value="year">Last 12 months</option>
                        </select>
                    </div>
                    <div class="h-80">
                        <line-chart :data="expenseChartData" />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import LineChart from '@/Components/Charts/LineChart.vue'
import {
    CurrencyDollarIcon, UsersIcon, ShoppingBagIcon,
    CreditCardIcon, ArrowUpIcon, ArrowDownIcon,
    BanknotesIcon, CalculatorIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    stats: {
        type: Object,
        required: true
    },
    recentSales: {
        type: Array,
        required: true
    },
    lowStockProducts: {
        type: Array,
        required: true
    },
    bankAccounts: {
        type: Array,
        required: true
    },
    recentExpenses: {
        type: Array,
        required: true
    },
    salesData: {
        type: Object,
        required: true
    },
    expenseData: {
        type: Object,
        required: true
    }
})

const chartPeriod = ref('week')
const expenseChartPeriod = ref('week')
const chartData = ref([])
const expenseChartData = ref([])


const formatNumber = (number) => {
    return new Intl.NumberFormat('en-BD').format(number)
}

const updateChartData = () => {
    const data = chartPeriod.value === 'week'
        ? props.salesData.weekly
        : chartPeriod.value === 'month'
            ? props.salesData.monthly
            : props.salesData.yearly

    chartData.value = data?.map(item => ({
        date: item.date,
        amount: parseFloat(item.amount)
    })) || []
}

const updateExpenseChartData = () => {
    const data = expenseChartPeriod.value === 'week'
        ? props.expenseData.weekly
        : expenseChartPeriod.value === 'month'
            ? props.expenseData.monthly
            : props.expenseData.yearly

    expenseChartData.value = data?.map(item => ({
        date: item.date,
        amount: parseFloat(item.amount)
    })) || []
}

watch(chartPeriod, updateChartData)
watch(expenseChartPeriod, updateExpenseChartData)

onMounted(() => {
    updateChartData()
    updateExpenseChartData()
})

</script>
