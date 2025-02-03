<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center border-b border-gray-200 pb-4">
                <h2 class="text-2xl font-bold text-gray-900">Income & Expenditure Statement</h2>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <input type="date" v-model="filters.start_date" @change="filter"
                            class="form-input rounded-md border-gray-300 shadow-sm" />
                        <span class="text-gray-500">to</span>
                        <input type="date" v-model="filters.end_date" @change="filter"
                            class="form-input rounded-md border-gray-300 shadow-sm" />
                    </div>
                    <button @click="printReport"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Print
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Expenses</h3>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="text-left px-4 py-2">Description</th>
                                <th class="text-right px-4 py-2">Period</th>
                                <th class="text-right px-4 py-2">Cumulative</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 font-medium">Expenses</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(expenditure.total.period) }}
                                </td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(expenditure.total.cumulative) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-2">Total Expenses</td>
                                <td class="px-4 py-2 text-right text-red-600">
                                    {{ formatCurrency(expenditure.total.period) }}
                                </td>
                                <td class="px-4 py-2 text-right text-red-600">
                                    {{ formatCurrency(expenditure.total.cumulative) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>


                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Sales Profit</h3>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="text-left px-4 py-2">Description</th>
                                <th class="text-right px-4 py-2">Period</th>
                                <th class="text-right px-4 py-2">Cumulative</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 font-medium">Sales Profit</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(income.sales_profit.profit.period) }}
                                </td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(income.sales_profit.profit.cumulative) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-medium">Extra Income</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(income.extra_income.period) }}
                                </td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(income.extra_income.cumulative) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-2">Total Profit</td>
                                <td class="px-4 py-2 text-right text-green-600">
                                    {{ formatCurrency(income.total.period) }}
                                </td>
                                <td class="px-4 py-2 text-right text-green-600">
                                    {{ formatCurrency(income.total.cumulative) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="mt-6 bg-white shadow-md rounded-lg p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="border-r pr-4">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-700">Net Income Period</span>
                            <span
                                :class="{
                                    'font-bold': true,
                                    'text-green-600': netPeriodResult > 0,
                                    'text-red-600': netPeriodResult < 0
                                }"
                            >
                                {{ formatCurrency(netPeriodResult) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-700">Net Income Cumulative</span>
                            <span
                                :class="{
                                    'font-bold': true,
                                    'text-green-600': netCumulativeResult > 0,
                                    'text-red-600': netCumulativeResult < 0
                                }"
                            >
                                {{ formatCurrency(netCumulativeResult) }}
                            </span>
                        </div>
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
        expenditure: { type: Object, required: true },
        income: { type: Object, required: true },
    },
    computed: {
        netPeriodResult() {
            return this.income.total.period - this.expenditure.total.period;
        },
        netCumulativeResult() {
            return this.income.total.cumulative - this.expenditure.total.cumulative;
        }
    },
    methods: {
        formatCurrency,
        formatDate,
        filter() {
            router.get(route('admin.reports.income-expenditure'), {
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
    body * {
        visibility: hidden;
    }

    #app {
        visibility: visible;
        position: absolute;
        left: 0;
        top: 0;
    }
}
</style>
