<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Product Analysis Report</h2>
                <div class="flex space-x-4">
                    <div class="flex items-center space-x-2">
                        <input type="date" v-model="filters.start_date" @change="handleDateChange"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                        <span class="text-gray-500">to</span>
                        <input type="date" v-model="filters.end_date" @change="handleDateChange"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                    </div>
                    <button @click="exportToExcel"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Export to Excel
                    </button>
                    <button @click="downloadPDF" :disabled="isDownloadDisabled"
                        class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 flex items-center">
                        <svg v-if="!isDownloading" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
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
        </template>

        <div class="py-6">
            <div class="max-w-full mx-auto">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <!-- Product Info Section -->
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                                            SL</th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                                            Product Name</th>

                                        <!-- Buy Info Section -->
                                        <th colspan="3"
                                            class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-blue-50">
                                            Buy Information
                                        </th>

                                        <!-- Sale Info Section -->
                                        <th colspan="3"
                                            class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-green-50">
                                            Sale Information
                                        </th>

                                        <!-- Available Info Section -->
                                        <th colspan="2"
                                            class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">
                                            Available Information
                                        </th>
                                    </tr>
                                    <tr class="bg-gray-50">
                                        <!-- Product Info Section -->
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                                        </th>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                                        </th>

                                        <!-- Buy Info Section -->
                                        <th
                                            class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-blue-50">
                                            Quantity</th>
                                        <th
                                            class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-blue-50">
                                            Price</th>
                                        <th
                                            class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-blue-50">
                                            Total</th>

                                        <!-- Sale Info Section -->
                                        <th
                                            class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-green-50">
                                            Quantity</th>
                                        <th
                                            class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-green-50">
                                            Price</th>
                                        <th
                                            class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-green-50">
                                            Total</th>

                                        <!-- Available Info Section -->
                                        <th
                                            class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-yellow-50">
                                            Stock</th>
                                        <th
                                            class="px-4 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider text-center bg-yellow-50">
                                            Value</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="product in products" :key="product.serial" class="hover:bg-gray-50">
                                        <!-- Product Info -->
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 border-r">{{
                                            product.serial
                                            }}</td>
                                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 border-r">{{
                                            product.product_name }}</td>

                                        <!-- Buy Info -->
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-blue-50/30">
                                            {{
                                                formatNumber(product.buy_quantity) }}</td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-blue-50/30">
                                            {{
                                                formatCurrency(product.buy_price) }}</td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-sm text-center text-gray-900 border-r bg-blue-50/30">
                                            {{
                                                formatCurrency(product.total_buy_price) }}</td>

                                        <!-- Sale Info -->
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-green-50/30">
                                            {{
                                                formatNumber(product.sale_quantity) }}</td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-green-50/30">
                                            {{
                                                formatCurrency(product.sale_price) }}</td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-green-50/30">
                                            {{
                                                formatCurrency(product.total_sale_price) }}</td>

                                        <!-- Available Info -->
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-yellow-50/30">
                                            {{ formatNumber(product.available_quantity) }}</td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 bg-yellow-50/30">
                                            {{
                                                formatCurrency(product.available_stock_value) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 font-semibold">
                                        <td colspan="2" class="px-4 py-4 text-sm text-gray-900 border-r">Totals</td>
                                        <!-- Buy Info Totals -->
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-blue-100">
                                            {{
                                                formatNumber(totalBuyQuantity) }}</td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-blue-100">
                                            -
                                        </td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-blue-100">
                                            {{
                                                formatCurrency(totalBuyPrice) }}</td>
                                        <!-- Sale Info Totals -->
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-green-100">
                                            {{
                                                formatNumber(totalSaleQuantity) }}</td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-green-100">
                                            -
                                        </td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-green-100">
                                            {{
                                                formatCurrency(totalSalePrice) }}</td>
                                        <!-- Available Info Totals -->
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 border-r bg-yellow-100">
                                            {{
                                                formatNumber(totalAvailableQuantity) }}</td>
                                        <td
                                            class="px-4 py-4 whitespace-nowrap text-center text-sm text-gray-900 bg-yellow-100">
                                            {{
                                                formatCurrency(totalAvailableValue) }}</td>
                                    </tr>
                                </tfoot>
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
import { startOfMonth, format } from 'date-fns'

export default defineComponent({
    components: {
        AdminLayout,
    },

    props: {
        products: {
            type: Array,
            required: true,
        },
        filters: {
            type: Object,
            required: true,
        },
        totals: {     // Add this prop
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            filters: {
                start_date: this.filters.start_date || format(startOfMonth(new Date()), 'yyyy-MM-dd'),
                end_date: this.filters.end_date || format(new Date(), 'yyyy-MM-dd'),
            },
            isDownloading: false,
            isDownloadDisabled: false,
        }
    },

    computed: {
        totalBuyQuantity() {
            return this.totals.buy_quantity;
        },
        totalBuyPrice() {
            return this.totals.total_buy_price;
        },
        totalSaleQuantity() {
            return this.totals.sale_quantity;
        },
        totalSalePrice() {
            return this.totals.total_sale_price;
        },
        totalAvailableQuantity() {
            return this.totals.available_quantity;
        },
        totalAvailableValue() {
            return this.totals.available_stock_value;
        },
    },

    methods: {
        handleDateChange() {
            router.get(route('admin.reports.product-analysis'), {
                start_date: this.filters.start_date,
                end_date: this.filters.end_date,
            }, {
                preserveState: true,
                preserveScroll: true,
            })
        },

        formatNumber(number) {
            return new Intl.NumberFormat('en-US').format(number || 0)
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount || 0);
        },
        async downloadPDF() {
            this.isDownloading = true;
            this.isDownloadDisabled = true;

            try {
                const response = await axios.get(route('admin.reports.product-analysis.pdf'), {
                    params: {
                        start_date: this.filters.start_date,
                        end_date: this.filters.end_date,
                    },
                    responseType: 'blob'
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `product-analysis-${this.filters.start_date}-to-${this.filters.end_date}.pdf`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (error) {
                console.error('Error downloading PDF:', error);
            } finally {
                this.isDownloading = false;
                this.isDownloadDisabled = false;
            }
        },

        exportToExcel() {
            const data = this.products.map(product => ({
                'SL': product.serial,
                'Product Name': product.product_name,
                'Buy Quantity': product.buy_quantity,
                'Buy Price': product.buy_price,
                'Buy Total': product.total_buy_price,
                'Sale Quantity': product.sale_quantity,
                'Sale Price': product.sale_price,
                'Sale Total': product.total_sale_price,
                'Available Stock': product.available_quantity,
                'Stock Value': product.available_stock_value,
            }))

            // Convert data to CSV format
            const headers = Object.keys(data[0]).join(',')
            const rows = data.map(item => Object.values(item).join(','))
            const csvContent = [headers, ...rows].join('\n')

            // Create blob and download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
            const link = document.createElement('a')

            // Create download URL
            const url = window.URL.createObjectURL(blob)
            link.setAttribute('href', url)
            link.setAttribute('download', 'products_export.csv')

            // Append link, trigger download, and cleanup
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)
        }
    },
})
</script>
