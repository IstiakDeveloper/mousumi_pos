<!-- resources/js/Pages/Admin/Reports/BankTransactionReport.vue -->
<script setup>
import { ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    bankAccounts: Array,
    selectedAccount: Object,
    selectedMonth: Number,
    selectedYear: Number,
    previousMonthBalance: Number,
    dailyTransactions: Array,
    monthTotals: Object,
    filters: Object
});

const currentMonth = ref(props.selectedMonth);
const currentYear = ref(props.selectedYear);
const selectedBankAccount = ref(props.selectedAccount?.id);

const months = [
    { id: 1, name: 'January' },
    { id: 2, name: 'February' },
    { id: 3, name: 'March' },
    { id: 4, name: 'April' },
    { id: 5, name: 'May' },
    { id: 6, name: 'June' },
    { id: 7, name: 'July' },
    { id: 8, name: 'August' },
    { id: 9, name: 'September' },
    { id: 10, name: 'October' },
    { id: 11, name: 'November' },
    { id: 12, name: 'December' }
];

const years = Array.from({ length: 5 }, (_, i) => new Date().getFullYear() - i);

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-GB');
};

const formatAmount = (amount) => {
    const num = Number(amount) || 0;
    return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

watch([currentMonth, currentYear, selectedBankAccount], () => {
    router.get(route('admin.bank-transaction-report'), {
        month: currentMonth.value,
        year: currentYear.value,
        bank_account_id: selectedBankAccount.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
});
</script>

<template>
    <AdminLayout>
        <Head title="Bank Transaction Report" />

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Filter Section -->
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex flex-wrap gap-4 items-center">
                            <div class="flex-1 min-w-[200px]">
                                <label class="block text-sm font-medium text-gray-700">Bank Account</label>
                                <select v-model="selectedBankAccount"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                        {{ account.account_name }} - {{ account.bank_name }}
                                    </option>
                                </select>
                            </div>

                            <div class="w-[150px]">
                                <label class="block text-sm font-medium text-gray-700">Month</label>
                                <select v-model="currentMonth"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option v-for="month in months" :key="month.id" :value="month.id">
                                        {{ month.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="w-[120px]">
                                <label class="block text-sm font-medium text-gray-700">Year</label>
                                <select v-model="currentYear"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                                    <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Table -->
                    <div class="overflow-x-auto p-6">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="border border-gray-300 bg-gray-50 px-4 py-2 text-left text-sm font-semibold text-gray-900">
                                        Date
                                    </th>
                                    <th colspan="5" class="border border-gray-300 bg-gray-50 px-4 py-2 text-center text-sm font-semibold text-gray-900">
                                        IN
                                    </th>
                                    <th colspan="4" class="border border-gray-300 bg-gray-50 px-4 py-2 text-center text-sm font-semibold text-gray-900">
                                        OUT
                                    </th>
                                    <th rowspan="2" class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-gray-900">
                                        Available Balance
                                    </th>
                                </tr>
                                <tr>
                                    <!-- IN subcategories -->
                                    <th class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-gray-900">Fund</th>
                                    <th class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-gray-900">Payment</th>
                                    <th class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-gray-900">Extra</th>
                                    <th class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-gray-900">Refund</th>
                                    <th class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-green-600">Total</th>
                                    <!-- OUT subcategories -->
                                    <th class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-gray-900">Fund</th>
                                    <th class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-gray-900">Purchase</th>
                                    <th class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-gray-900">Expense</th>
                                    <th class="border border-gray-300 bg-gray-50 px-4 py-2 text-right text-sm font-semibold text-red-600">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Previous Month Balance Row -->
                                <tr class="bg-gray-50">
                                    <td class="border border-gray-300 px-4 py-2 text-sm font-medium text-gray-900">
                                        Previous Month Balance
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-500">0.00</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-500">0.00</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-500">0.00</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-500">0.00</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-500">0.00</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-500">0.00</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-500">0.00</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-500">0.00</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-gray-500">0.00</td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-medium text-gray-900">
                                        {{ formatAmount(previousMonthBalance) }}
                                    </td>
                                </tr>

                                <!-- Daily Transaction Rows -->
                                <tr v-for="transaction in dailyTransactions" :key="transaction.date">
                                    <td class="border border-gray-300 px-4 py-2 text-sm text-gray-900">
                                        {{ formatDate(transaction.date) }}
                                    </td>
                                    <!-- IN columns -->
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-green-600">
                                        {{ formatAmount(transaction.in.fund) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-green-600">
                                        {{ formatAmount(transaction.in.payment) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-green-600">
                                        {{ formatAmount(transaction.in.extra) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-green-600">
                                        {{ formatAmount(transaction.in.refund) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-medium text-green-600">
                                        {{ formatAmount(transaction.in.total) }}
                                    </td>
                                    <!-- OUT columns -->
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-red-600">
                                        {{ formatAmount(transaction.out.fund) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-red-600">
                                        {{ formatAmount(transaction.out.purchase) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm text-red-600">
                                        {{ formatAmount(transaction.out.expense) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-medium text-red-600">
                                        {{ formatAmount(transaction.out.total) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-medium text-gray-900">
                                        {{ formatAmount(transaction.balance) }}
                                    </td>
                                </tr>

                                <!-- Total Row -->
                                <tr class="bg-gray-100 font-medium">
                                    <td class="border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-900">
                                        Month Total
                                    </td>
                                    <!-- IN totals -->
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-green-600">
                                        {{ formatAmount(monthTotals.in.fund) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-green-600">
                                        {{ formatAmount(monthTotals.in.payment) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-green-600">
                                        {{ formatAmount(monthTotals.in.extra) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-green-600">
                                        {{ formatAmount(monthTotals.in.refund) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-green-600">
                                        {{ formatAmount(monthTotals.in.total) }}
                                    </td>
                                    <!-- OUT totals -->
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-red-600">
                                        {{ formatAmount(monthTotals.out.fund) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-red-600">
                                        {{ formatAmount(monthTotals.out.purchase) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-red-600">
                                        {{ formatAmount(monthTotals.out.expense) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-red-600">
                                        {{ formatAmount(monthTotals.out.total) }}
                                    </td>
                                    <td class="border border-gray-300 px-4 py-2 text-right text-sm font-semibold text-gray-900">
                                        {{ formatAmount(dailyTransactions[dailyTransactions.length - 1]?.balance) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
