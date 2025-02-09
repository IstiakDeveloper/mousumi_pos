<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-base font-semibold text-gray-800">Product Analysis Report</h2>
                <div class="flex space-x-2">
                    <div class="flex items-center space-x-1">
                        <input type="date" v-model="filters.start_date" @change="handleDateChange"
                            class="text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                        <span class="text-gray-500 text-xs">to</span>
                        <input type="date" v-model="filters.end_date" @change="handleDateChange"
                            class="text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                    </div>
                    <button @click="exportToExcel"
                        class="bg-green-600 text-white px-2 py-1 text-xs rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Export Excel
                    </button>
                    <button @click="downloadPDF" :disabled="isDownloadDisabled"
                        class="bg-red-600 text-white px-2 py-1 text-xs rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 flex items-center">
                        {{ isDownloading ? 'Downloading...' : 'PDF' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="py-3">
            <div class="max-w-full mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-2">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <!-- Product Info Section -->
                                        <th colspan="2"
                                            class="px-2 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r">
                                            Product Information</th>

                                        <!-- Before Stock Section -->
                                        <th colspan="3"
                                            class="px-2 py-1 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-yellow-50">
                                            Before Stock Information
                                        </th>

                                        <!-- Buy Info Section -->
                                        <th colspan="3"
                                            class="px-2 py-1 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-blue-50">
                                            Buy Information
                                        </th>

                                        <!-- Sale Info Section -->
                                        <th colspan="3"
                                            class="px-2 py-1 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-green-50">
                                            Sale Information
                                        </th>

                                        <!-- Profit Info Section -->
                                        <th colspan="2"
                                            class="px-2 py-1 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r bg-orange-50">
                                            Profit Information
                                        </th>

                                        <!-- Available Info Section -->
                                        <th colspan="2"
                                            class="px-2 py-1 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-yellow-50">
                                            Available Information
                                        </th>
                                    </tr>
                                    <tr class="bg-gray-50 text-xs">
                                        <!-- Product Info Headers -->
                                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider border-r">SL</th>
                                        <th class="px-2 py-1 text-left font-medium text-gray-500 uppercase tracking-wider border-r">Name</th>

                                        <!-- Before Stock Headers -->
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-yellow-50">Qty</th>
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-yellow-50">Price</th>
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-yellow-50">Value</th>

                                        <!-- Buy Info Headers -->
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-blue-50">Qty</th>
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-blue-50">Price</th>
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-blue-50">Total Price</th>

                                        <!-- Sale Info Headers -->
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-green-50">Qty</th>
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-green-50">Price</th>
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-green-50">Total Price</th>

                                        <!-- Profit Info Headers -->
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-orange-50">Per Unit</th>
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-orange-50">Total</th>

                                        <!-- Available Info Headers -->
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider border-r text-center bg-yellow-50">Stock</th>
                                        <th class="px-2 py-1 font-medium text-gray-500 uppercase tracking-wider text-center bg-yellow-50">Value</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="product in products" :key="product.serial" class="hover:bg-gray-50 text-xs">
                                        <!-- Product Info -->
                                        <td class="px-2 py-1 whitespace-nowrap text-gray-500 border-r">{{ product.serial }}</td>
                                        <td class="px-2 py-1 whitespace-nowrap text-gray-900 border-r">{{ product.product_name }}</td>

                                        <!-- Before Stock Info -->
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-yellow-50/30">{{ formatNumber(product.before_quantity) }}</td>
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-yellow-50/30">{{ formatCurrency(product.before_price) }}</td>
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-yellow-50/30">{{ formatCurrency(product.before_value) }}</td>

                                        <!-- Buy Info -->
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-blue-50/30">{{ formatNumber(product.buy_quantity) }}</td>
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-blue-50/30">{{ formatCurrency(product.buy_price) }}</td>
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-blue-50/30">{{ formatCurrency(product.total_buy_price) }}</td>

                                        <!-- Sale Info -->
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-green-50/30">{{ formatNumber(product.sale_quantity) }}</td>
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-green-50/30">{{ formatCurrency(product.sale_price) }}</td>
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-green-50/30">{{ formatCurrency(product.total_sale_price) }}</td>

                                        <!-- Profit Info -->
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-orange-50/30">{{ formatCurrency(product.profit_per_unit) }}</td>
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-orange-50/30">{{ formatCurrency(product.total_profit) }}</td>

                                        <!-- Available Info -->
                                        <td class="px-2 py-1 whitespace-nowrap text-center border-r bg-yellow-50/30">{{ formatNumber(product.available_quantity) }}</td>
                                        <td class="px-2 py-1 whitespace-nowrap text-center bg-yellow-50/30">{{ formatCurrency(product.available_stock_value) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-gray-50 font-semibold text-xs">
                                        <td colspan="2" class="px-2 py-1 text-gray-900 border-r">Totals</td>
                                        <!-- Before Stock Totals -->
                                        <td class="px-2 py-1 text-center border-r bg-yellow-100">{{ formatNumber(totalBeforeQuantity) }}</td>
                                        <td class="px-2 py-1 text-center border-r bg-yellow-100">-</td>
                                        <td class="px-2 py-1 text-center border-r bg-yellow-100">{{ formatCurrency(totalBeforeValue) }}</td>
                                        <!-- Buy Info Totals -->
                                        <td class="px-2 py-1 text-center border-r bg-blue-100">{{ formatNumber(totalBuyQuantity) }}</td>
                                        <td class="px-2 py-1 text-center border-r bg-blue-100">-</td>
                                        <td class="px-2 py-1 text-center border-r bg-blue-100">{{ formatCurrency(totalBuyPrice) }}</td>
                                        <!-- Sale Info Totals -->
                                        <td class="px-2 py-1 text-center border-r bg-green-100">{{ formatNumber(totalSaleQuantity) }}</td>
                                        <td class="px-2 py-1 text-center border-r bg-green-100">-</td>
                                        <td class="px-2 py-1 text-center border-r bg-green-100">{{ formatCurrency(totalSalePrice) }}</td>
                                        <!-- Profit Info Totals -->
                                        <td class="px-2 py-1 text-center border-r bg-orange-100">-</td>
                                        <td class="px-2 py-1 text-center border-r bg-orange-100">{{ formatCurrency(totalProfit) }}</td>
                                        <!-- Available Info Totals -->
                                        <td class="px-2 py-1 text-center border-r bg-yellow-100">{{ formatNumber(totalAvailableQuantity) }}</td>
                                        <td class="px-2 py-1 text-center bg-yellow-100">{{ formatCurrency(totalAvailableValue) }}</td>
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
        totals: {
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
        totalBeforeQuantity() {
            return this.totals.before_quantity;
        },
        totalBeforeValue() {
            return this.totals.before_value;
        },
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
        totalProfit() {
            return this.totals.total_profit;
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
                'Before Quantity': product.before_quantity,
                'Before Price': product.before_price,
                'Before Value': product.before_value,
                'Buy Quantity': product.buy_quantity,
                'Buy Price': product.buy_price,
                'Buy Total': product.total_buy_price,
                'Sale Quantity': product.sale_quantity,
                'Sale Price': product.sale_price,
                'Sale Total': product.total_sale_price,
                'Profit Per Unit': product.profit_per_unit,
                'Total Profit': product.total_profit,
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
            link.setAttribute('download', `product-analysis-${this.filters.start_date}-to-${this.filters.end_date}.csv`)

            // Append link, trigger download, and cleanup
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)
        }
    },
})
</script>
