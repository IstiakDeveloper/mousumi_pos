<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Balance Sheet</h2>
                <div class="flex space-x-4">
                    <div class="flex items-center space-x-2">
                        <input
                            type="date"
                            v-model="filters.start_date"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        />
                        <span class="text-gray-500">to</span>
                        <input
                            type="date"
                            v-model="filters.end_date"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        />
                        <button
                            @click="filter"
                            class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Filter
                        </button>
                    </div>
                    <button
                        @click="printReport"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                        Print
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-8" ref="printArea">
                        <!-- Report Header -->
                        <div class="text-center mb-10">
                            <h2 class="text-2xl font-bold mb-2">Balance Sheet</h2>
                            <p class="text-gray-600">
                                Period: {{ formatDate(filters.start_date) }} to {{ formatDate(filters.end_date) }}
                            </p>
                        </div>

                        <!-- Main Balance Sheet Table -->
                        <div class="border rounded-lg shadow">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-100 border-b">
                                        <th class="w-1/2 px-6 py-3 text-center border-r font-semibold">Fund & Liabilities</th>
                                        <th class="w-1/2 px-6 py-3 text-center font-semibold">Property & Assets</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <!-- Period Section -->
                                    <tr>
                                        <td class="border-r align-top">
                                            <div class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-700">Period</div>
                                            <table class="w-full">
                                                <tbody class="divide-y divide-gray-100">
                                                    <tr>
                                                        <td class="px-6 py-3">Fund</td>
                                                        <td class="px-6 py-3 text-right">{{ formatCurrency(fund_and_liabilities.fund.period) }}</td>
                                                    </tr>
                                                    <tr class="bg-gray-50">
                                                        <td colspan="2" class="px-6 py-3 font-medium text-gray-700">Net Profit:</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-6 py-2 pl-10">Sales</td>
                                                        <td class="px-6 py-2 text-right">{{ formatCurrency(fund_and_liabilities.net_profit.details.sales.period) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-6 py-2 pl-10">Extra Income</td>
                                                        <td class="px-6 py-2 text-right">{{ formatCurrency(fund_and_liabilities.net_profit.details.extra_income.period) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-6 py-2 pl-10">Less: Expenses</td>
                                                        <td class="px-6 py-2 text-right text-red-600">{{ formatCurrency(-fund_and_liabilities.net_profit.details.expenses.period) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-6 py-2 pl-10">Less: Cost of Goods Sold</td>
                                                        <td class="px-6 py-2 text-right text-red-600">{{ formatCurrency(-fund_and_liabilities.net_profit.details.cost_of_goods_sold.period) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-6 py-3 font-medium border-t">Net Profit for Period</td>
                                                        <td class="px-6 py-3 text-right border-t" :class="{'text-green-600': fund_and_liabilities.net_profit.period > 0, 'text-red-600': fund_and_liabilities.net_profit.period < 0}">
                                                            {{ formatCurrency(fund_and_liabilities.net_profit.period) }}
                                                        </td>
                                                    </tr>
                                                    <tr class="bg-gray-100 font-bold">
                                                        <td class="px-6 py-4">Total for Period</td>
                                                        <td class="px-6 py-4 text-right">{{ formatCurrency(fund_and_liabilities.fund.period + fund_and_liabilities.net_profit.period) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td class="align-top">
                                            <div class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-700">Period</div>
                                            <table class="w-full">
                                                <tbody class="divide-y divide-gray-100">
                                                    <tr>
                                                        <td class="px-6 py-3">Bank Balance</td>
                                                        <td class="px-6 py-3 text-right">{{ formatCurrency(property_and_assets.bank_balance.period) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-6 py-3">Customer Due</td>
                                                        <td class="px-6 py-3 text-right">{{ formatCurrency(property_and_assets.customer_due.period) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="px-6 py-3">Stock Value</td>
                                                        <td class="px-6 py-3 text-right">{{ formatCurrency(property_and_assets.stock_value.period - property_and_assets.stock_value.opening) }}</td>
                                                    </tr>
                                                    <tr class="bg-gray-100 font-bold">
                                                        <td class="px-6 py-4">Total for Period</td>
                                                        <td class="px-6 py-4 text-right">{{ calculatePeriodTotal }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                    <!-- Cumulative Section -->
                                    <tr class="border-t pt-4">
                                        <td class="border-r align-top">
                                            <div class="px-4 py-2 bg-gray-50 border-b font-medium">Cumulative</div>
                                            <table class="w-full">
                                                <tr>
                                                    <td class="px-4 py-2">Fund</td>
                                                    <td class="px-4 py-2 text-right">{{ formatCurrency(fund_and_liabilities.fund.total) }}</td>
                                                </tr>
                                                <tr class="bg-gray-50">
                                                    <td colspan="2" class="px-4 py-1 font-medium">Net Profit:</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-4 py-1 pl-8">Sales</td>
                                                    <td class="px-4 py-1 text-right">{{ formatCurrency(fund_and_liabilities.net_profit.details.sales.opening + fund_and_liabilities.net_profit.details.sales.period) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-4 py-1 pl-8">Extra Income</td>
                                                    <td class="px-4 py-1 text-right">{{ formatCurrency(fund_and_liabilities.net_profit.details.extra_income.opening + fund_and_liabilities.net_profit.details.extra_income.period) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-4 py-1 pl-8">Less: Expenses</td>
                                                    <td class="px-4 py-1 text-right text-red-600">-{{ formatCurrency(fund_and_liabilities.net_profit.details.expenses.opening + fund_and_liabilities.net_profit.details.expenses.period) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-4 py-1 pl-8">Less: Cost of Goods Sold</td>
                                                    <td class="px-4 py-1 text-right text-red-600">-{{ formatCurrency(fund_and_liabilities.net_profit.details.cost_of_goods_sold.opening + fund_and_liabilities.net_profit.details.cost_of_goods_sold.period) }}</td>
                                                </tr>
                                                <tr class="border-t">
                                                    <td class="px-4 py-2 font-medium">Total Net Profit</td>
                                                    <td class="px-4 py-2 text-right" :class="{'text-green-600': fund_and_liabilities.net_profit.total > 0, 'text-red-600': fund_and_liabilities.net_profit.total < 0}">
                                                        {{ formatCurrency(fund_and_liabilities.net_profit.total) }}
                                                    </td>
                                                </tr>
                                                <tr class="bg-gray-100 font-bold border-t">
                                                    <td class="px-4 py-2">Total Cumulative</td>
                                                    <td class="px-4 py-2 text-right">{{ formatCurrency(fund_and_liabilities.total) }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td class="align-top">
                                            <div class="px-4 py-2 bg-gray-50 border-b font-medium">Cumulative</div>
                                            <table class="w-full">
                                                <tr>
                                                    <td class="px-4 py-2">Bank Balance</td>
                                                    <td class="px-4 py-2 text-right">{{ formatCurrency(property_and_assets.bank_balance.total) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-4 py-2">Fixed Assets</td>
                                                    <td class="px-4 py-2 text-right">{{ formatCurrency(property_and_assets.fixed_assets) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-4 py-2">Customer Due</td>
                                                    <td class="px-4 py-2 text-right">{{ formatCurrency(property_and_assets.customer_due.total) }}</td>
                                                </tr>
                                                <tr>
                                                    <td class="px-4 py-2">Stock Value</td>
                                                    <td class="px-4 py-2 text-right">{{ formatCurrency(property_and_assets.stock_value.period) }}</td>
                                                </tr>
                                                <tr class="bg-gray-100 font-bold border-t">
                                                    <td class="px-4 py-2">Total Cumulative</td>
                                                    <td class="px-4 py-2 text-right">{{ formatCurrency(property_and_assets.total) }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { defineComponent } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { formatDate, formatCurrency } from '@/Utils'

export default defineComponent({
    components: {
        AdminLayout,
    },

    props: {
        filters: {
            type: Object,
            required: true,
        },
        fund_and_liabilities: {
            type: Object,
            required: true,
        },
        property_and_assets: {
            type: Object,
            required: true,
        },
    },

    computed: {
        calculatePeriodTotal() {
            const total = this.property_and_assets.bank_balance.period +
                this.property_and_assets.customer_due.period +
                (this.property_and_assets.stock_value.period - this.property_and_assets.stock_value.opening);
            return formatCurrency(total);
        }
    },

    methods: {
        formatCurrency,
        formatDate,

        filter() {
            router.get(route('admin.reports.balance-sheet'), {
                start_date: this.filters.start_date,
                end_date: this.filters.end_date,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },

        printReport() {
            window.print();
        },
    },
})
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #app {
        visibility: visible;
        position: absolute;
        left: 0;
        top: 0;
    }
    .no-print {
        display: none !important;
    }
    .shadow-xl, .shadow {
        box-shadow: none !important;
    }
    @page {
        size: auto;
        margin: 15mm;
    }
}

/* Add these classes for better spacing in print */
@media print {
    .py-8 {
        padding-top: 2rem !important;
        padding-bottom: 2rem !important;
    }
    .p-8 {
        padding: 2rem !important;
    }
    .px-6 {
        padding-left: 1.5rem !important;
        padding-right: 1.5rem !important;
    }
}
</style>
