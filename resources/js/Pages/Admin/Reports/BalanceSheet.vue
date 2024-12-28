<template>
    <AdminLayout>
        <div class="bg-white dark:bg-gray-800 shadow">
            <div class="px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Balance Sheet
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Financial position and net worth analysis
                        </p>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-3 bg-white dark:bg-gray-700 rounded-lg shadow p-2">
                            <select v-model="selectedMonth"
                                class="rounded-md border-0 bg-transparent py-1.5 pl-3 pr-8 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <option v-for="(month, index) in months" :key="index" :value="index + 1">
                                    {{ month }}
                                </option>
                            </select>
                            <select v-model="selectedYear"
                                class="rounded-md border-0 bg-transparent py-1.5 pl-3 pr-8 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <option v-for="year in years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                        </div>

                        <button @click="downloadPdf"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow-sm transition-colors duration-200">
                            <ArrowDownTrayIcon class="w-5 h-5 mr-2" />
                            Download Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Funds & Liabilities -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 bg-indigo-50 dark:bg-indigo-900">
                        <h3 class="text-lg font-semibold text-indigo-700 dark:text-indigo-200">
                            Funds & Liabilities
                        </h3>
                    </div>
                    <div class="p-6">
                        <!-- Current Month -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                                Current Month
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Funds In</span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(fundsAndLiabilities.current_month.funds_in) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Net Profit</span>
                                    <span :class="[
                                        'font-medium',
                                        fundsAndLiabilities.current_month.net_profit >= 0
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-red-600 dark:text-red-400'
                                    ]">
                                        {{ formatCurrency(fundsAndLiabilities.current_month.net_profit) }}
                                    </span>
                                </div>
                                <div class="flex justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <span class="font-medium text-gray-900 dark:text-white">Total</span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ formatCurrency(fundsAndLiabilities.current_month.total) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Cumulative -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                                Cumulative
                            </h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Total Investment</span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(fundsAndLiabilities.cumulative.total_investment) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">Retained Earnings</span>
                                    <span :class="[
                                        'font-medium',
                                        fundsAndLiabilities.cumulative.retained_earnings >= 0
                                            ? 'text-green-600 dark:text-green-400'
                                            : 'text-red-600 dark:text-red-400'
                                    ]">
                                        {{ formatCurrency(fundsAndLiabilities.cumulative.retained_earnings) }}
                                    </span>
                                </div>
                                <div class="flex justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <span class="font-medium text-gray-900 dark:text-white">Total</span>
                                    <span class="font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ formatCurrency(fundsAndLiabilities.cumulative.total) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Property & Assets -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 bg-emerald-50 dark:bg-emerald-900">
                        <h3 class="text-lg font-semibold text-emerald-700 dark:text-emerald-200">
                            Property & Assets
                        </h3>
                    </div>
                    <div class="p-6">
                        <!-- Bank Accounts -->
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">
                                Bank Accounts
                            </h4>
                            <div class="space-y-3">
                                <div v-for="account in propertyAndAssets.bank_accounts" :key="account.account_name"
                                    class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-300">{{ account.account_name }}</span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(account.balance) }}
                                    </span>
                                </div>
                                <div class="flex justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <span class="font-medium text-gray-900 dark:text-white">Total Bank Balance</span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(propertyAndAssets.total_bank_balance) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Other Assets -->
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Customer Due</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(propertyAndAssets.customer_due) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-300">Stock Value</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(propertyAndAssets.stock_value) }}
                                </span>
                            </div>
                            <div class="flex justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                <span class="font-medium text-gray-900 dark:text-white">Total</span>
                                <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formatCurrency(propertyAndAssets.total) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Check -->
            <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Balance Check</h3>
                        <div :class="[
                            'px-4 py-2 rounded-full text-sm font-medium',
                            isBalanced ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                        ]">
                            {{ isBalanced ? 'Balanced' : 'Not Balanced' }}
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div class="text-right text-gray-600 dark:text-gray-300">
                            Funds & Liabilities:
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ formatCurrency(fundsAndLiabilities.cumulative.total) }}
                            </span>
                        </div>
                        <div class="text-right text-gray-600 dark:text-gray-300">
                            Property & Assets:
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ formatCurrency(propertyAndAssets.total) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    fundsAndLiabilities: Object,
    propertyAndAssets: Object,
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

// Balance Check
const isBalanced = computed(() => {
    return Math.abs(props.fundsAndLiabilities.cumulative.total - props.propertyAndAssets.total) < 0.01;
});

// Watchers for month and year changes
watch([selectedMonth, selectedYear], ([newMonth, newYear]) => {
    router.get(route('admin.reports.balance-sheet'), {
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

const downloadPdf = () => {
    window.open(
        route('admin.reports.balance-sheet.pdf', {
            month: selectedMonth.value,
            year: selectedYear.value
        }),
        '_blank'
    );
};
</script>
