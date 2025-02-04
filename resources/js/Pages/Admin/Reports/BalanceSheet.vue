<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Balance Sheet</h2>
                <div class="flex items-center space-x-2">
                    <input
                        type="date"
                        v-model="filters.start_date"
                        @change="filter"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    />
                    <span class="text-gray-500">to</span>
                    <input
                        type="date"
                        v-model="filters.end_date"
                        @change="filter"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                    />
                    <button
                        @click="printReport"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 ml-4"
                    >
                        Print
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Fund & Liabilities -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="py-3 px-4 bg-gray-50 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Fund & Liabilities</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="text-left py-2 px-4">Description</th>
                                    <th class="text-right py-2 px-4">Period</th>
                                    <th class="text-right py-2 px-4">Cumulative</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b">
                                    <td class="py-3 px-4">Fund</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(fund_and_liabilities.fund.period) }}</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(fund_and_liabilities.fund.total) }}</td>
                                </tr>
                                <tr class="border-b bg-gray-50">
                                    <td class="py-2 px-4 font-medium" colspan="3">Net Profit</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-2 px-4 pl-8">Sales</td>
                                    <td class="py-2 px-4 text-right">{{ formatCurrency(fund_and_liabilities.net_profit.details.sales.period) }}</td>
                                    <td class="py-2 px-4 text-right">{{ formatCurrency(fund_and_liabilities.net_profit.details.sales.opening + fund_and_liabilities.net_profit.details.sales.period) }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-2 px-4 pl-8">Extra Income</td>
                                    <td class="py-2 px-4 text-right">{{ formatCurrency(fund_and_liabilities.net_profit.details.extra_income.period) }}</td>
                                    <td class="py-2 px-4 text-right">{{ formatCurrency(fund_and_liabilities.net_profit.details.extra_income.opening + fund_and_liabilities.net_profit.details.extra_income.period) }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-2 px-4 pl-8">Less: Expenses</td>
                                    <td class="py-2 px-4 text-right text-red-600">-{{ formatCurrency(fund_and_liabilities.net_profit.details.expenses.period) }}</td>
                                    <td class="py-2 px-4 text-right text-red-600">-{{ formatCurrency(fund_and_liabilities.net_profit.details.expenses.opening + fund_and_liabilities.net_profit.details.expenses.period) }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-2 px-4 pl-8">Less: Cost of Goods</td>
                                    <td class="py-2 px-4 text-right text-red-600">-{{ formatCurrency(fund_and_liabilities.net_profit.details.cost_of_goods_sold.period) }}</td>
                                    <td class="py-2 px-4 text-right text-red-600">-{{ formatCurrency(fund_and_liabilities.net_profit.details.cost_of_goods_sold.opening + fund_and_liabilities.net_profit.details.cost_of_goods_sold.period) }}</td>
                                </tr>
                                <tr class="font-semibold border-b">
                                    <td class="py-3 px-4">Net Profit</td>
                                    <td class="py-3 px-4 text-right" :class="{'text-green-600': fund_and_liabilities.net_profit.period > 0, 'text-red-600': fund_and_liabilities.net_profit.period < 0}">
                                        {{ formatCurrency(fund_and_liabilities.net_profit.period) }}
                                    </td>
                                    <td class="py-3 px-4 text-right" :class="{'text-green-600': fund_and_liabilities.net_profit.total > 0, 'text-red-600': fund_and_liabilities.net_profit.total < 0}">
                                        {{ formatCurrency(fund_and_liabilities.net_profit.total) }}
                                    </td>
                                </tr>
                                <tr class="font-semibold bg-gray-50">
                                    <td class="py-3 px-4">Total</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(fund_and_liabilities.fund.period + fund_and_liabilities.net_profit.period) }}</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(fund_and_liabilities.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Property & Assets -->
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                        <div class="py-3 px-4 bg-gray-50 border-b">
                            <h3 class="text-lg font-semibold text-gray-800">Property & Assets</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="text-left py-2 px-4">Description</th>
                                    <th class="text-right py-2 px-4">Period</th>
                                    <th class="text-right py-2 px-4">Cumulative</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b">
                                    <td class="py-3 px-4">Bank Balance</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(property_and_assets.bank_balance.period) }}</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(property_and_assets.bank_balance.total) }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-3 px-4">Fixed Assets</td>
                                    <td class="py-3 px-4 text-right">-</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(property_and_assets.fixed_assets) }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-3 px-4">Customer Due</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(property_and_assets.customer_due.period) }}</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(property_and_assets.customer_due.total) }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-3 px-4">Stock Value</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(property_and_assets.stock_value.period - property_and_assets.stock_value.opening) }}</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(property_and_assets.stock_value.period) }}</td>
                                </tr>
                                <tr class="font-semibold bg-gray-50">
                                    <td class="py-3 px-4">Total</td>
                                    <td class="py-3 px-4 text-right">{{ calculatePeriodTotal }}</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(property_and_assets.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { defineComponent } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { formatDate, formatCurrency } from '@/Utils';

export default defineComponent({
    components: { AdminLayout },
    props: {
        filters: { type: Object, required: true },
        fund_and_liabilities: { type: Object, required: true },
        property_and_assets: { type: Object, required: true },
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
    watch: {
        'filters.start_date'() { this.filter(); },
        'filters.end_date'() { this.filter(); }
    }
})
</script>

<style>
@media print {
    body * { visibility: hidden; }
    #app {
        visibility: visible;
        position: absolute;
        left: 0;
        top: 0;
    }
    .shadow-sm { box-shadow: none !important; }
}
</style>
