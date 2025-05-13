<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Balance Sheet</h2>
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
            <div id="balance-sheet-content" class="mx-auto max-w-7xl">

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                    <!-- Fund & Liabilities -->
                    <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-800">Fund & Liabilities</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="px-4 py-2 text-left border-r">Description</th>
                                    <th class="px-4 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Fund Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">Fund</td>
                                    <td class="px-4 py-2 text-right text-green-600">
                                        {{ formatCurrency(fund_and_liabilities.fund.period) }}
                                    </td>
                                </tr>

                                <!-- Net Profit Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 font-semibold border-r">Net Profit</td>
                                    <td class="px-4 py-2 text-right" :class="{
                                        'text-green-600': fund_and_liabilities.net_profit.period > 0,
                                        'text-red-600': fund_and_liabilities.net_profit.period < 0
                                    }">
                                        {{ formatCurrency(fund_and_liabilities.net_profit.period) }}
                                    </td>
                                </tr>

                                <!-- Empty Rows for Spacing -->
                                <tr class="border-b">
                                    <td class="px-4 py-5 border-r"></td>
                                    <td class="px-4 py-5 text-right"></td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-4 py-5 border-r"></td>
                                    <td class="px-4 py-5 text-right"></td>
                                </tr>

                                <!-- Total Row -->
                                <tr class="font-bold bg-gray-50">
                                    <td class="px-4 py-2 border-r">Total</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(fund_and_liabilities.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Property & Assets -->
                    <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="px-4 py-3 border-b bg-gray-50">
                            <h3 class="text-lg font-semibold text-gray-800">Property & Assets</h3>
                        </div>
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-gray-50">
                                    <th class="px-4 py-2 text-left border-r">Description</th>
                                    <th class="px-4 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Bank Balance Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">Bank Balance</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.bank_balance.period) }}
                                    </td>
                                </tr>

                                <!-- Customer Due Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">Customer Due</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.customer_due.period) }}
                                    </td>
                                </tr>

                                <!-- Fixed Assets Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">Fixed Assets</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.fixed_assets) }}
                                    </td>
                                </tr>

                                <!-- Stock Value Row -->
                                <tr class="border-b">
                                    <td class="px-4 py-2 border-r">Stock Value</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.stock_value.period) }}
                                    </td>
                                </tr>


                                <!-- Total Row -->
                                <tr class="font-bold bg-gray-50">
                                    <td class="px-4 py-2 border-r">Total</td>
                                    <td class="px-4 py-2 text-right">
                                        {{ formatCurrency(property_and_assets.total) }}
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
import axios from 'axios'; // Ensure axios is imported

export default defineComponent({
    components: { AdminLayout },
    props: {
        filters: { type: Object, required: true },
        fund_and_liabilities: { type: Object, required: true },
        property_and_assets: { type: Object, required: true },
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
            isDownloading: false // Add a loading state for download
        }
    },
    methods: {
        formatCurrency,
        getMonthName(monthNumber) {
            return this.months.find(m => m.value === monthNumber)?.label || '';
        },
        extractYear(dateString) {
            if (!dateString) return null;
            return new Date(dateString).getFullYear();
        },
        extractMonth(dateString) {
            if (!dateString) return null;
            return new Date(dateString).getMonth() + 1;
        },
        handleDateChange() {
            // Get first and last day of selected month
            const startDate = new Date(this.selectedYear, this.selectedMonth - 1, 1);
            const endDate = new Date(this.selectedYear, this.selectedMonth, 0);

            router.get(route('admin.reports.balance-sheet'), {
                start_date: startDate.toISOString().split('T')[0],
                end_date: endDate.toISOString().split('T')[0],
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        async downloadPDF() {
            try {
                // Set downloading state
                this.isDownloading = true;

                // Prepare the download URL
                const url = route('admin.reports.balance-sheet.download', {
                    start_date: this.filters.start_date,
                    end_date: this.filters.end_date,
                });

                // Fetch the PDF
                const response = await axios({
                    url: url,
                    method: 'GET',
                    responseType: 'blob', // Important
                });

                // Create a link element to trigger download
                const link = document.createElement('a');
                const blob = new Blob([response.data], { type: 'application/pdf' });

                // Generate filename
                const startDate = new Date(this.filters.start_date);
                const endDate = new Date(this.filters.end_date);
                const filename = `balance-sheet-${startDate.toISOString().split('T')[0]}-to-${endDate.toISOString().split('T')[0]}.pdf`;

                // Create download link
                link.href = window.URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();

                // Clean up
                document.body.removeChild(link);
                window.URL.revokeObjectURL(link.href);
            } catch (error) {
                console.error('PDF Download Error:', error);
                // Optionally show an error toast/notification
                this.$toast.error('Failed to download PDF');
            } finally {
                // Reset downloading state
                this.isDownloading = false;
            }
        },
    },
    computed: {
        // Optional: Disable download button while downloading
        isDownloadDisabled() {
            return this.isDownloading;
        }
    }
})
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    #balance-sheet-content,
    #balance-sheet-content * {
        visibility: visible;
    }

    #balance-sheet-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .print\:hidden {
        display: none;
    }

    .print\:block {
        display: block !important;
    }
}
</style>
