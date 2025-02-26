<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center border-b border-gray-200 pb-4">
                <h2 class="text-2xl font-bold text-gray-900">Receipt & Payment Statement</h2>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <!-- Year Dropdown -->
                        <select v-model="selectedYear" @change="handleDateChange"
                            class="form-select rounded-md border-gray-300 shadow-sm w-32">
                            <option v-for="year in years" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>

                        <!-- Month Dropdown -->
                        <select v-model="selectedMonth" @change="handleDateChange"
                            class="form-select rounded-md border-gray-300 shadow-sm w-40">
                            <option v-for="month in months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </option>
                        </select>

                        <button @click="downloadPDF" :disabled="isDownloadDisabled"
                            class="inline-flex items-center px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50">
                            <svg v-if="!isDownloading" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 animate-spin" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ isDownloading ? 'Downloading...' : 'Download PDF' }}
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6 px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Receipt</h3>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="text-left px-4 py-2">Description</th>
                                <th class="text-right px-4 py-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 font-medium">Opening Cash On the Bank</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.opening_cash_on_bank || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">Sale Collection</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.sale_collection || 0) }}
                                </td>
                            </tr>
                            <!-- Extra Income Header Row -->
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-4 py-2 font-medium text-gray-900">Others Income</td>
                            </tr>
                            <!-- Individual Extra Income Category Rows -->
                            <template
                                v-if="receipt?.extra_income?.categories && receipt.extra_income.categories.length > 0">
                                <tr v-for="category in receipt.extra_income.categories" :key="category.category">
                                    <td class="px-4 py-2 pl-8 text-sm text-gray-700">{{ category.category }}</td>
                                    <td class="px-4 py-2 text-right text-green-600">
                                        {{ formatCurrency(category.amount) }}
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td class="px-4 py-2 pl-8 text-sm text-gray-500 italic">No others income this period
                                    </td>
                                    <td class="px-4 py-2 text-right text-gray-500">
                                        {{ formatCurrency(0) }}
                                    </td>
                                </tr>
                            </template>
                            <!-- Extra Income Total Row -->
                            <tr class="bg-gray-100 font-semibold">
                                <td class="px-4 py-2 text-gray-800">Total Others Income</td>
                                <td class="px-4 py-2 text-right text-green-600 font-bold">
                                    {{ formatCurrency(receipt?.extra_income?.total || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">Fund Receive</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.fund_receive || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-2">Total Receipt</td>
                                <td class="px-4 py-2 text-right text-green-600">
                                    {{ formatCurrency(receipt?.total || 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Payment</h3>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="text-left px-4 py-2">Description</th>
                                <th class="text-right px-4 py-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 font-medium">Purchase</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.purchase || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">Fund Refund</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.fund_refund || 0) }}
                                </td>
                            </tr>
                            <!-- Expenses Header Row -->
                            <tr class="bg-gray-50">
                                <td colspan="2" class="px-4 py-2 font-medium text-gray-900">Expenses</td>
                            </tr>
                            <!-- Individual Expense Category Rows -->
                            <template v-if="payment?.expenses?.categories && payment.expenses.categories.length > 0">
                                <tr v-for="category in payment.expenses.categories" :key="category.category">
                                    <td class="px-4 py-2 pl-8 text-sm text-gray-700">{{ category.category }}</td>
                                    <td class="px-4 py-2 text-right text-red-600">
                                        {{ formatCurrency(category.amount) }}
                                    </td>
                                </tr>
                            </template>
                            <template v-else>
                                <tr>
                                    <td class="px-4 py-2 pl-8 text-sm text-gray-500 italic">No expenses this period</td>
                                    <td class="px-4 py-2 text-right text-gray-500">
                                        {{ formatCurrency(0) }}
                                    </td>
                                </tr>
                            </template>
                            <!-- Expenses Total Row -->
                            <tr class="bg-gray-100 font-semibold">
                                <td class="px-4 py-2 text-gray-800">Total Expenses</td>
                                <td class="px-4 py-2 text-right text-red-600 font-bold">
                                    {{ formatCurrency(payment?.expenses?.total || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">Closing Cash at Bank</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.closing_cash_at_bank || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-2">Total Payment</td>
                                <td class="px-4 py-2 text-right text-red-600">
                                    {{ formatCurrency(payment?.total || 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { defineComponent } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { formatCurrency } from '@/Utils';

export default defineComponent({
    components: { AdminLayout },
    props: {
        filters: {
            type: Object,
            default: () => ({
                year: null,
                month: null,
                start_date: null,
                end_date: null
            })
        },
        receipt: {
            type: Object,
            default: () => ({
                opening_cash_on_bank: 0,
                sale_collection: 0,
                extra_income: 0,
                fund_receive: 0,
                total: 0
            })
        },
        payment: {
            type: Object,
            default: () => ({
                purchase: 0,
                fund_refund: 0,
                expenses: 0,
                closing_cash_at_bank: 0,
                total: 0
            })
        },
    },
    data() {
        const currentYear = new Date().getFullYear();

        return {
            years: [currentYear - 1, currentYear, currentYear + 1],
            months: [
                { value: 1, label: 'January' },
                { value: 2, label: 'February' },
                { value: 3, label: 'March' },
                { value: 4, label: 'April' },
                { value: 5, label: 'May' },
                { value: 6, label: 'June' },
                { value: 7, label: 'July' },
                { value: 8, label: 'August' },
                { value: 9, label: 'September' },
                { value: 10, label: 'October' },
                { value: 11, label: 'November' },
                { value: 12, label: 'December' }
            ],
            selectedYear: this.filters.year || currentYear,
            selectedMonth: this.filters.month || (new Date().getMonth() + 1),
            isDownloading: false,
            isDownloadDisabled: false,
        }
    },
    methods: {
        formatCurrency,
        handleDateChange() {
            router.get(route('admin.reports.receipt-payment'), {
                year: this.selectedYear,
                month: this.selectedMonth
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        async downloadPDF() {
            this.isDownloading = true;
            this.isDownloadDisabled = true;

            try {
                // Validate inputs
                if (!this.selectedYear || !this.selectedMonth) {
                    throw new Error('Year and month must be selected');
                }

                // Create URL parameters
                const params = new URLSearchParams({
                    year: this.selectedYear,
                    month: this.selectedMonth
                });

                // Trigger download
                window.location.href = `${route('admin.reports.receipt-payment.pdf')}?${params}`;

                // Reset states after a brief delay to show loading state
                setTimeout(() => {
                    this.isDownloading = false;
                    this.isDownloadDisabled = false;
                }, 1000);

            } catch (error) {
                console.error('Error downloading PDF:', error);
                this.$notify({
                    type: 'error',
                    message: error.message || 'Failed to download PDF'
                });
                this.isDownloading = false;
                this.isDownloadDisabled = false;
            }
        }
    }
})
</script>
