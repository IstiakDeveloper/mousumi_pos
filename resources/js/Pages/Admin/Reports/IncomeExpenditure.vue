<!-- resources/js/Pages/Admin/Reports/IncomeExpenditure.vue -->
<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                    Income & Expenditure Report
                </h2>
                <div class="flex items-center space-x-4">
                    <!-- Month Selector -->
                    <select v-model="selectedMonth"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option v-for="(month, index) in months" :key="index" :value="index + 1">
                            {{ month }}
                        </option>
                    </select>

                    <!-- Year Selector -->
                    <select v-model="selectedYear"
                        class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>
                </div>
            </div>
        </template>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5 mb-6">
            <SummaryCard
                title="Sales Profit"
                :value="summary.sales_profit"
                icon="trending-up"
                type="currency"
            />
            <SummaryCard
                title="Extra Income"
                :value="summary.extra_income"
                icon="plus"
                type="currency"
            />
            <SummaryCard
                title="Total Profit"
                :value="summary.total_profit"
                icon="currency-dollar"
                type="currency"
                class="bg-green-50"
            />
            <SummaryCard
                title="Total Expense"
                :value="summary.total_expense"
                icon="minus"
                type="currency"
                class="bg-red-50"
            />
            <SummaryCard
                title="Net Profit"
                :value="summary.net_profit"
                icon="chart-bar"
                type="currency"
                :class="summary.net_profit >= 0 ? 'bg-green-50' : 'bg-red-50'"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Income Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Income Details</h3>

                    <!-- Sales Profit -->
                    <div class="mt-6">
                        <h4 class="font-medium text-gray-700 dark:text-gray-300">Sales Profit</h4>
                        <div class="mt-2 border-t border-gray-200 dark:border-gray-700">
                            <dl class="divide-y divide-gray-200 dark:divide-gray-700">
                                <div class="flex justify-between py-3">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Total Sales Profit</dt>
                                    <dd class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(summary.sales_profit) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Extra Income table remains the same -->

                    <!-- Total Profit Summary -->
                    <div class="mt-6 pt-4 border-t-2 border-gray-200">
                        <div class="flex justify-between items-center font-medium">
                            <span class="text-gray-700 dark:text-gray-300">Total Profit</span>
                            <span class="text-lg text-green-600">
                                {{ formatCurrency(summary.total_profit) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expenditure Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Expenditure Details</h3>

                    <!-- Expenses table remains the same -->

                    <!-- Total Expense Summary -->
                    <div class="mt-6 pt-4 border-t-2 border-gray-200">
                        <div class="flex justify-between items-center font-medium">
                            <span class="text-gray-700 dark:text-gray-300">Total Expense</span>
                            <span class="text-lg text-red-600">
                                {{ formatCurrency(summary.total_expense) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Profit Display -->
        <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <h3 class="text-xl font-medium text-gray-900 dark:text-white">Net Profit</h3>
                <span :class="[
                    'text-2xl font-bold',
                    summary.net_profit >= 0 ? 'text-green-600' : 'text-red-600'
                ]">
                    {{ formatCurrency(summary.net_profit) }}
                </span>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SummaryCard from '@/Components/SummaryCard.vue';

const props = defineProps({
    salesIncome: Object,
    extraIncome: Array,
    expenses: Array,
    summary: Object,
    filters: Object
});

// Month and Year Selection
const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - 2 + i);

const selectedMonth = ref(props.filters.month);
const selectedYear = ref(props.filters.year);

// Watchers for month and year changes
watch([selectedMonth, selectedYear], ([newMonth, newYear]) => {
    router.get(route('admin.reports.income-expenditure'), {
        month: newMonth,
        year: newYear
    }, {
        preserveState: true,
        preserveScroll: true
    });
});

// Utility functions
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
    }).format(amount || 0);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-BD');
};
</script>
