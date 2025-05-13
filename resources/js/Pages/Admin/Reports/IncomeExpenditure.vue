<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Income & Expenditure Statement</h2>
                <div class="flex items-center space-x-4">
                    <!-- Year Selection -->
                    <select v-model="selectedYear" @change="handleDateChange"
                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option v-for="year in years" :key="year" :value="year">
                            {{ year }}
                        </option>
                    </select>

                    <!-- Month Selection -->
                    <select v-model="selectedMonth" @change="handleDateChange"
                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option v-for="month in months" :key="month.value" :value="month.value">
                            {{ month.label }}
                        </option>
                    </select>

                    <button @click="downloadPDF" :disabled="isDownloadDisabled"
                        class="inline-flex items-center px-3 py-2 text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50">
                        <svg v-if="!isDownloading" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2 animate-spin" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ isDownloading ? 'Downloading...' : 'Download PDF' }}
                    </button>

                </div>
            </div>
        </template>

        <div class="py-6">
            <div id="income-expenditure-content" class="mx-auto ">


                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Income Section -->
                    <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-800">Income</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="px-4 py-2 text-left border-r">Description</th>
                                    <th class="px-4 py-2 text-right border-r">Month</th>
                                    <th class="px-4 py-2 text-right">Cumulative</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sales Profit Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 font-semibold border-r">Sales Profit</td>
                                    <td class="px-4 py-2 text-right border-r" :class="{
                                        'text-green-600': income.sales_profit.period > 0,
                                        'text-red-600': income.sales_profit.period < 0
                                    }">
                                        {{ formatCurrency(income.sales_profit.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right" :class="{
                                        'text-green-600': income.sales_profit.cumulative > 0,
                                        'text-red-600': income.sales_profit.cumulative < 0
                                    }">
                                        {{ formatCurrency(income.sales_profit.cumulative) }}
                                    </td>
                                </tr>

                                <!-- Extra Income Categories -->
                                <template v-for="category in income.extra_income.categories" :key="category.name">
                                    <tr class="border-b">
                                        <td class="px-4 py-2 border-r">
                                            {{ category.name }} <span class="text-gray-500">(Others Income)</span>
                                        </td>
                                        <td class="px-4 py-2 text-right text-green-600 border-r">
                                            {{ formatCurrency(category.period) }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-green-600">
                                            {{ formatCurrency(category.cumulative) }}
                                        </td>
                                    </tr>
                                </template>

                                <!-- Total Income Row -->
                                <tr class="font-bold border-b bg-gray-50">
                                    <td class="px-4 py-2 border-r">Total Income</td>
                                    <td class="px-4 py-2 text-right text-green-600 border-r">
                                        {{ formatCurrency(income.total.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-green-600">
                                        {{ formatCurrency(income.total.cumulative) }}
                                    </td>
                                </tr>

                                <!-- Surplus Row -->
                                <tr class="font-bold bg-gray-50">
                                    <td class="px-4 py-2 border-r">Surplus</td>
                                    <td class="px-4 py-2 text-right border-r">
                                        <span class="font-bold" :class="{
                                            'text-green-600': netResultPeriod > 0,
                                            'text-red-600': netResultPeriod < 0
                                        }">
                                            {{ formatCurrency(netResultPeriod) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-right">
                                        <span class="font-bold" :class="{
                                            'text-green-600': netResultCumulative > 0,
                                            'text-red-600': netResultCumulative < 0
                                        }">
                                            {{ formatCurrency(netResultCumulative) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr class="font-bold border-b bg-gray-50">
                                    <td class="px-4 py-2 border-r">Grand Total</td>
                                    <td class="px-4 py-2 text-right text-red-600 border-r">
                                        {{ formatCurrency(expenditure.total.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-red-600">
                                        {{ formatCurrency(expenditure.total.cumulative) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Expenditure Section -->
                    <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-800">Expenditure</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="px-4 py-2 text-left border-r">Description</th>
                                    <th class="px-4 py-2 text-right border-r">Month</th>
                                    <th class="px-4 py-2 text-right">Cumulative</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="category in expenditure.categories" :key="category.name" class="border-b">
                                    <td class="px-4 py-2 border-r">{{ category.name }}</td>
                                    <td class="px-4 py-2 text-right text-red-600 border-r">
                                        {{ formatCurrency(category.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-red-600">
                                        {{ formatCurrency(category.cumulative) }}
                                    </td>
                                </tr>

                                <!-- Total Expenses Row -->
                                <tr class="font-bold border-b bg-gray-50">
                                    <td class="px-4 py-2 border-r">Total Expenditure</td>
                                    <td class="px-4 py-2 text-right text-red-600 border-r">
                                        {{ formatCurrency(expenditure.total.period) }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-red-600">
                                        {{ formatCurrency(expenditure.total.cumulative) }}
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

<script>
import { defineComponent } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { formatCurrency } from '@/Utils';
import html2pdf from 'html2pdf.js';

export default defineComponent({
    components: { AdminLayout },
    props: {
        filters: { type: Object, required: true },
        income: { type: Object, required: true },
        expenditure: { type: Object, required: true },
    },
    data() {
        const currentYear = new Date().getFullYear();
        const currentMonth = new Date().getMonth() + 1;

        return {
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
            years: [currentYear - 1, currentYear, currentYear + 1],
            selectedYear: this.extractYear(this.filters.start_date) || currentYear,
            selectedMonth: this.extractMonth(this.filters.start_date) || currentMonth,
            isDownloading: false,
            isDownloadDisabled: false,
        }
    },
    computed: {
        netResultPeriod() {
            return this.income.total.period - this.expenditure.total.period;
        },
        netResultCumulative() {
            return this.income.total.cumulative - this.expenditure.total.cumulative;
        }
    },
    methods: {
        formatCurrency,
        extractYear(dateString) {
            if (!dateString) return null;
            return new Date(dateString).getFullYear();
        },
        extractMonth(dateString) {
            if (!dateString) return null;
            return new Date(dateString).getMonth() + 1;
        },
        getMonthName(monthNumber) {
            return this.months.find(m => m.value === monthNumber)?.label || '';
        },
        formatDateRange(startDate, endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            return `${start.toLocaleDateString()} - ${end.toLocaleDateString()}`;
        },
        handleDateChange() {
            // Get first and last day of selected month
            const startDate = new Date(this.selectedYear, this.selectedMonth - 1, 1);
            const endDate = new Date(this.selectedYear, this.selectedMonth, 0);

            router.get(route('admin.reports.income-expenditure'), {
                start_date: startDate.toISOString().split('T')[0],
                end_date: endDate.toISOString().split('T')[0],
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        async downloadPDF() {
            this.isDownloading = true;
            this.isDownloadDisabled = true;

            try {
                // Get first and last day of selected month with timezone safety
                // Use Date.UTC to ensure consistent dates without timezone issues
                const startDate = new Date(Date.UTC(this.selectedYear, this.selectedMonth - 1, 1));
                const endDate = new Date(Date.UTC(this.selectedYear, this.selectedMonth, 0)); // Last day of month

                // Format dates as YYYY-MM-DD ensuring we get the correct day
                // Use local date methods rather than ISO string to avoid timezone shifts
                const formattedStartDate = `${startDate.getUTCFullYear()}-${String(startDate.getUTCMonth() + 1).padStart(2, '0')}-${String(startDate.getUTCDate()).padStart(2, '0')}`;
                const formattedEndDate = `${endDate.getUTCFullYear()}-${String(endDate.getUTCMonth() + 1).padStart(2, '0')}-${String(endDate.getUTCDate()).padStart(2, '0')}`;

                // Also include the month directly as a parameter to ensure the controller has the correct month
                const monthName = this.getMonthName(this.selectedMonth);

                // Create URL with query parameters
                const url = route('admin.reports.income-expenditure.pdf') +
                    `?start_date=${formattedStartDate}&end_date=${formattedEndDate}&selected_month=${this.selectedMonth}&selected_year=${this.selectedYear}`;

                // Open in new window or redirect current window
                window.location.href = url;

                // Reset states after a brief delay to show loading state
                setTimeout(() => {
                    this.isDownloading = false;
                    this.isDownloadDisabled = false;
                }, 1500);
            } catch (error) {
                console.error('Error downloading PDF:', error);
                this.isDownloading = false;
                this.isDownloadDisabled = false;
            }
        },

    }
})
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    #income-expenditure-content,
    #income-expenditure-content * {
        visibility: visible;
    }

    #income-expenditure-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }


}
</style>
