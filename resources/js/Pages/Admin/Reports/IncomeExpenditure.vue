    <template>
        <AdminLayout>
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 shadow">
                <div class="px-4 sm:px-6 lg:px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                                Income & Expenditure Report
                            </h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Detailed financial analysis and reporting
                            </p>
                        </div>

                        <div class="flex items-center space-x-4">
                            <!-- Period Selector -->
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

                            <!-- Download Button -->
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
                <!-- Summary Overview -->
                <div class="mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Monthly Summary -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <div class="p-6">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                    {{ filters.monthName }} {{ filters.year }}
                                </h4>
                                <dl class="space-y-4">
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-600">Sales Profit</dt>
                                        <dd class="text-sm font-semibold text-gray-900">
                                            {{ formatCurrency(summary.monthly.sales_profit) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-600">Extra Income</dt>
                                        <dd class="text-sm font-semibold text-gray-900">
                                            {{ formatCurrency(summary.monthly.extra_income) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between pt-4 border-t">
                                        <dt class="text-sm font-medium text-gray-600">Total Income</dt>
                                        <dd class="text-sm font-semibold text-emerald-600">
                                            {{ formatCurrency(summary.monthly.total_income) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-600">Total Expenses</dt>
                                        <dd class="text-sm font-semibold text-red-600">
                                            {{ formatCurrency(summary.monthly.expenses) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between pt-4 border-t">
                                        <dt class="text-base font-medium text-gray-900">Net Profit</dt>
                                        <dd class="text-base font-bold"
                                            :class="summary.monthly.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                            {{ formatCurrency(summary.monthly.net_profit) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Year to Date Summary -->
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                            <div class="p-6">
                                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                    Year to Date (Jan - {{ filters.monthName }} {{ filters.year }})
                                </h4>
                                <dl class="space-y-4">
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-600">Sales Profit</dt>
                                        <dd class="text-sm font-semibold text-gray-900">
                                            {{ formatCurrency(summary.cumulative.sales_profit) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-600">Extra Income</dt>
                                        <dd class="text-sm font-semibold text-gray-900">
                                            {{ formatCurrency(summary.cumulative.extra_income) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between pt-4 border-t">
                                        <dt class="text-sm font-medium text-gray-600">Total Income</dt>
                                        <dd class="text-sm font-semibold text-emerald-600">
                                            {{ formatCurrency(summary.cumulative.total_income) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-sm font-medium text-gray-600">Total Expenses</dt>
                                        <dd class="text-sm font-semibold text-red-600">
                                            {{ formatCurrency(summary.cumulative.expenses) }}
                                        </dd>
                                    </div>
                                    <div class="flex justify-between pt-4 border-t">
                                        <dt class="text-base font-medium text-gray-900">Net Profit</dt>
                                        <dd class="text-base font-bold"
                                            :class="summary.cumulative.net_profit >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                            {{ formatCurrency(summary.cumulative.net_profit) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Tables -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Income Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="p-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Income Details</h4>

                            <!-- Extra Income Table -->
                            <div class="mt-6">
                                <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Extra Income</h5>
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Date
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Title
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Account
                                                </th>
                                                <th
                                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Amount
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            <tr v-for="income in extraIncome" :key="income.id">
                                                <td
                                                    class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ formatDate(income.date) }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {{ income.title }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                    {{ income.bank_account.account_name }}
                                                </td>
                                                <td
                                                    class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                                                    {{ formatCurrency(income.amount) }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Details -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="p-6">
                            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Expense Details</h4>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Date
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Category
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Description
                                            </th>
                                            <th
                                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                Amount
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="expense in expenses" :key="expense.id">
                                            <td
                                                class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                {{ formatDate(expense.date) }}
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                {{ expense.expense_category.name }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                                {{ expense.description }}
                                            </td>
                                            <td
                                                class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-white">
                                                {{ formatCurrency(expense.amount) }}
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

const downloadPdf = () => {
    window.open(
        route('admin.reports.income-expenditure.pdf', {
            month: selectedMonth.value,
            year: selectedYear.value
        }),
        '_blank'
    );
};
</script>
