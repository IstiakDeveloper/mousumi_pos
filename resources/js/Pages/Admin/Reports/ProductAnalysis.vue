<template>
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-800">Product Analysis Report</h2>
                <div class="flex items-center space-x-2">
                    <!-- Date Range Selector -->
                    <div class="flex items-center space-x-1">
                        <input type="date" v-model="filters.start_date" @change="handleDateChange"
                            :max="filters.end_date"
                            class="text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                        <span class="text-xs text-gray-500">to</span>
                        <input type="date" v-model="filters.end_date" @change="handleDateChange"
                            :min="filters.start_date" :max="today"
                            class="text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                    </div>

                    <!-- Search Box -->
                    <input type="text" v-model="searchQuery" @input="handleSearch" placeholder="Search products..."
                        class="w-48 px-3 py-1 text-xs border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />

                    <!-- Export Buttons -->
                    <button @click="exportToExcel" :disabled="isExporting"
                        class="flex items-center px-3 py-1 space-x-1 text-xs text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>{{ isExporting ? 'Exporting...' : 'Excel' }}</span>
                    </button>

                    <button @click="downloadPDF" :disabled="isDownloading"
                        class="flex items-center px-3 py-1 space-x-1 text-xs text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span>{{ isDownloading ? 'Generating...' : 'PDF' }}</span>
                    </button>

                    <!-- Refresh Button -->
                    <button @click="refreshData" :disabled="isRefreshing"
                        class="flex items-center px-3 py-1 text-xs text-white bg-gray-600 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50">
                        <svg :class="['w-4 h-4', { 'animate-spin': isRefreshing }]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <div class="py-3">
            <div class="max-w-full mx-auto">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="p-6 text-center">
                        <div class="inline-flex items-center">
                            <svg class="w-5 h-5 mr-3 text-gray-600 animate-spin" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                                    fill="none" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            Loading product analysis...
                        </div>
                    </div>

                    <!-- Error State -->
                    <div v-else-if="error" class="p-6">
                        <div class="p-4 border border-red-200 rounded-md bg-red-50">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Error loading data</h3>
                                    <div class="mt-2 text-sm text-red-700">{{ error }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Table -->
                    <div v-else class="p-2">
                        <!-- Summary Cards -->
                        <div class="grid grid-cols-5 gap-4 mb-4">
                            <div class="p-3 rounded-lg bg-blue-50">
                                <h3 class="text-xs font-medium text-blue-700">Total Buy</h3>
                                <p class="text-lg font-bold text-blue-900">{{ formatCurrency(totalBuyPrice) }}</p>
                            </div>
                            <div class="p-3 rounded-lg bg-green-50">
                                <h3 class="text-xs font-medium text-green-700">Total Sale</h3>
                                <p class="text-lg font-bold text-green-900">{{ formatCurrency(totalSaleAfterDiscount) }}
                                </p>
                            </div>
                            <div class="p-3 rounded-lg bg-orange-50">
                                <h3 class="text-xs font-medium text-orange-700">Total Profit</h3>
                                <p class="text-lg font-bold text-orange-900">{{ formatCurrency(totalProfit) }}</p>
                            </div>
                            <div class="p-3 rounded-lg bg-purple-50">
                                <h3 class="text-xs font-medium text-purple-700">Profit Margin</h3>
                                <p class="text-lg font-bold text-purple-900">{{ profitMargin }}%</p>
                            </div>
                            <div class="p-3 rounded-lg bg-yellow-50">
                                <h3 class="text-xs font-medium text-yellow-700">Stock Value</h3>
                                <p class="text-lg font-bold text-yellow-900">{{ formatCurrency(totalAvailableValue) }}
                                </p>
                            </div>
                        </div>

                        <!-- Table Container -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs divide-y divide-gray-200">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <!-- Product Info Section -->
                                        <th colspan="2"
                                            class="px-2 py-1 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-r">
                                            Product Information
                                        </th>

                                        <!-- Before Stock Section -->
                                        <th colspan="3"
                                            class="px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-yellow-50">
                                            Before Stock Information
                                        </th>

                                        <!-- Buy Info Section -->
                                        <th colspan="3"
                                            class="px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-blue-50">
                                            Buy Information
                                        </th>

                                        <!-- Sale Info Section -->
                                        <th colspan="5"
                                            class="px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            Sale Information
                                        </th>

                                        <!-- Profit Info Section -->
                                        <th colspan="2"
                                            class="px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-orange-50">
                                            Profit Information
                                        </th>

                                        <!-- Available Info Section -->
                                        <th colspan="2"
                                            class="px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase bg-purple-50">
                                            Available Information
                                        </th>
                                    </tr>
                                    <tr class="text-xs bg-gray-50">
                                        <!-- Product Info Headers -->
                                        <th
                                            class="sticky left-0 px-2 py-1 font-medium tracking-wider text-left text-gray-500 uppercase border-r bg-gray-50">
                                            SL</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-left text-gray-500 uppercase border-r">
                                            Name</th>
                                        <!-- Before Stock Headers -->
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-yellow-50">
                                            Qty</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-yellow-50">
                                            Price</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-yellow-50">
                                            Value</th>

                                        <!-- Buy Info Headers -->
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-blue-50">
                                            Qty</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-blue-50">
                                            Price</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-blue-50">
                                            Total</th>

                                        <!-- Sale Info Headers -->
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            Qty</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            Price</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            Subtotal</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            Discount</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-green-50">
                                            Total</th>

                                        <!-- Profit Info Headers -->
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-orange-50">
                                            Per Unit</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-orange-50">
                                            Total</th>

                                        <!-- Available Info Headers -->
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase border-r bg-purple-50">
                                            Stock</th>
                                        <th
                                            class="px-2 py-1 font-medium tracking-wider text-center text-gray-500 uppercase bg-purple-50">
                                            Value</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="product in filteredProducts" :key="product.serial"
                                        class="text-xs hover:bg-gray-50">
                                        <!-- Product Info -->
                                        <td
                                            class="sticky left-0 px-2 py-1 text-gray-500 bg-white border-r whitespace-nowrap">
                                            {{
                                                product.serial }}</td>
                                        <td class="px-2 py-1 font-medium text-gray-900 border-r whitespace-nowrap">{{
                                            product.product_name }}</td>


                                        <!-- Before Stock Info -->
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-yellow-50/30">{{
                                            formatNumber(product.before_quantity) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-yellow-50/30">{{
                                            formatCurrency(product.before_price) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-yellow-50/30">{{
                                            formatCurrency(product.before_value) }}</td>

                                        <!-- Buy Info -->
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-blue-50/30">{{
                                            formatNumber(product.buy_quantity) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-blue-50/30">{{
                                            formatCurrency(product.buy_price) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-blue-50/30">{{
                                            formatCurrency(product.total_buy_price) }}</td>

                                        <!-- Sale Info -->
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-green-50/30">{{
                                            formatNumber(product.sale_quantity) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-green-50/30">{{
                                            formatCurrency(product.sale_price) }}</td>
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-green-50/30">{{
                                            formatCurrency(product.total_sale_price) }}</td>
                                        <td
                                            class="px-2 py-1 text-center text-red-600 border-r whitespace-nowrap bg-green-50/30">
                                            {{ formatCurrency(product.sale_discount) }}</td>
                                        <td
                                            class="px-2 py-1 font-semibold text-center border-r whitespace-nowrap bg-green-50/30">
                                            {{
                                                formatCurrency(product.sale_after_discount) }}</td>

                                        <!-- Profit Info -->
                                        <td :class="[
                                            'px-2 py-1 whitespace-nowrap text-center border-r bg-orange-50/30',
                                            product.profit_per_unit < 0 ? 'text-red-600' : 'text-green-600'
                                        ]">
                                            {{ formatCurrency(product.profit_per_unit) }}
                                        </td>
                                        <td :class="[
                                            'px-2 py-1 whitespace-nowrap text-center border-r bg-orange-50/30 font-semibold',
                                            product.total_profit < 0 ? 'text-red-600' : 'text-green-600'
                                        ]">
                                            {{ formatCurrency(product.total_profit) }}
                                        </td>

                                        <!-- Available Info -->
                                        <td class="px-2 py-1 text-center border-r whitespace-nowrap bg-purple-50/30">{{
                                            formatNumber(product.available_quantity) }}</td>
                                        <td class="px-2 py-1 text-center whitespace-nowrap bg-purple-50/30">{{
                                            formatCurrency(product.available_stock_value) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="text-xs font-semibold bg-gray-50">
                                        <td colspan="2" class="px-2 py-1 text-right text-gray-900 border-r">Totals</td>
                                        <!-- Before Stock Totals -->
                                        <td class="px-2 py-1 text-center bg-yellow-100 border-r">{{
                                            formatNumber(totalBeforeQuantity) }}</td>
                                        <td class="px-2 py-1 text-center bg-yellow-100 border-r">-</td>
                                        <td class="px-2 py-1 text-center bg-yellow-100 border-r">{{
                                            formatCurrency(totalBeforeValue) }}</td>
                                        <!-- Buy Info Totals -->
                                        <td class="px-2 py-1 text-center bg-blue-100 border-r">{{
                                            formatNumber(totalBuyQuantity)
                                            }}</td>
                                        <td class="px-2 py-1 text-center bg-blue-100 border-r">-</td>
                                        <td class="px-2 py-1 text-center bg-blue-100 border-r">{{
                                            formatCurrency(totalBuyPrice)
                                            }}</td>
                                        <!-- Sale Info Totals -->
                                        <td class="px-2 py-1 text-center bg-green-100 border-r">{{
                                            formatNumber(totalSaleQuantity) }}</td>
                                        <td class="px-2 py-1 text-center bg-green-100 border-r">-</td>
                                        <td class="px-2 py-1 text-center bg-green-100 border-r">{{
                                            formatCurrency(totalSalePrice) }}</td>
                                        <td class="px-2 py-1 text-center text-red-600 bg-green-100 border-r">{{
                                            formatCurrency(totalSaleDiscount) }}</td>
                                        <td class="px-2 py-1 font-semibold text-center bg-green-100 border-r">{{
                                            formatCurrency(totalSaleAfterDiscount) }}</td>
                                        <!-- Profit Info Totals -->
                                        <td class="px-2 py-1 text-center bg-orange-100 border-r">-</td>
                                        <td :class="[
                                            'px-2 py-1 text-center border-r bg-orange-100',
                                            totalProfit < 0 ? 'text-red-600' : 'text-green-600'
                                        ]">
                                            {{ formatCurrency(totalProfit) }}
                                        </td>
                                        <!-- Available Info Totals -->
                                        <td class="px-2 py-1 text-center bg-purple-100 border-r">{{
                                            formatNumber(totalAvailableQuantity) }}</td>
                                        <td class="px-2 py-1 text-center bg-purple-100">{{
                                            formatCurrency(totalAvailableValue)
                                            }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredProducts.length === 0 && !isLoading" class="p-6 text-center">
                            <div class="text-gray-500">
                                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <p class="mt-2">No products found matching your criteria</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { defineComponent, computed, ref } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { router } from '@inertiajs/vue3'
import { format, parseISO } from 'date-fns'
import axios from 'axios'

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

    setup(props) {
        const searchQuery = ref('')
        const isLoading = ref(false)
        const isDownloading = ref(false)
        const isExporting = ref(false)
        const isRefreshing = ref(false)
        const error = ref(null)

        // Computed properties
        const today = computed(() => format(new Date(), 'yyyy-MM-dd'))

        const filteredProducts = computed(() => {
            if (!searchQuery.value) return props.products

            const query = searchQuery.value.toLowerCase()
            return props.products.filter(product => {
                return product.product_name.toLowerCase().includes(query) ||
                    product.product_model.toLowerCase().includes(query) ||
                    product.category.toLowerCase().includes(query)
            })
        })

        const profitMargin = computed(() => {
            const afterDiscount = props.totals.sale_after_discount || props.totals.total_sale_price
            if (afterDiscount === 0) return 0
            return ((props.totals.total_profit / afterDiscount) * 100).toFixed(2)
        })

        // Totals from props
        const totalBeforeQuantity = computed(() => props.totals.before_quantity)
        const totalBeforeValue = computed(() => props.totals.before_value)
        const totalBuyQuantity = computed(() => props.totals.buy_quantity)
        const totalBuyPrice = computed(() => props.totals.total_buy_price)
        const totalSaleQuantity = computed(() => props.totals.sale_quantity)
        const totalSalePrice = computed(() => props.totals.total_sale_price)
        const totalSaleDiscount = computed(() => props.totals.sale_discount || 0)
        const totalSaleAfterDiscount = computed(() => props.totals.sale_after_discount || props.totals.total_sale_price)
        const totalProfit = computed(() => props.totals.total_profit)
        const totalAvailableQuantity = computed(() => props.totals.available_quantity)
        const totalAvailableValue = computed(() => props.totals.available_stock_value)

        // Methods
        const handleDateChange = () => {
            router.get(route('admin.reports.product-analysis'), {
                start_date: props.filters.start_date,
                end_date: props.filters.end_date,
            }, {
                preserveState: true,
                preserveScroll: true,
                onStart: () => isLoading.value = true,
                onFinish: () => isLoading.value = false,
                onError: (errors) => error.value = Object.values(errors).join('\n')
            })
        }

        const handleSearch = () => {
            // Debounce search functionality could be added here
        }

        const refreshData = () => {
            router.reload({
                onStart: () => isRefreshing.value = true,
                onFinish: () => isRefreshing.value = false,
                onError: (errors) => error.value = Object.values(errors).join('\n')
            })
        }

        const formatNumber = (number) => {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number || 0)
        }

        const formatCurrency = (amount) => {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount || 0)
        }

        const downloadPDF = async () => {
            isDownloading.value = true
            error.value = null

            try {
                const response = await axios.get(route('admin.reports.product-analysis.pdf'), {
                    params: {
                        start_date: props.filters.start_date,
                        end_date: props.filters.end_date,
                    },
                    responseType: 'blob'
                })

                const url = window.URL.createObjectURL(new Blob([response.data]))
                const link = document.createElement('a')
                link.href = url
                link.setAttribute('download', `product-analysis-${props.filters.start_date}-to-${props.filters.end_date}.pdf`)
                document.body.appendChild(link)
                link.click()
                document.body.removeChild(link)
                window.URL.revokeObjectURL(url)
            } catch (err) {
                console.error('Error downloading PDF:', err)
                error.value = 'Failed to download PDF. Please try again.'
            } finally {
                isDownloading.value = false
            }
        }

        const exportToExcel = async () => {
            isExporting.value = true
            error.value = null

            try {
                // Check if we have an excel export route
                const excelRoute = route('admin.reports.product-analysis.excel')
                if (excelRoute) {
                    const response = await axios.get(excelRoute, {
                        params: {
                            start_date: props.filters.start_date,
                            end_date: props.filters.end_date,
                        },
                        responseType: 'blob'
                    })

                    const url = window.URL.createObjectURL(new Blob([response.data]))
                    const link = document.createElement('a')
                    link.href = url
                    link.setAttribute('download', `product-analysis-${props.filters.start_date}-to-${props.filters.end_date}.xlsx`)
                    document.body.appendChild(link)
                    link.click()
                    document.body.removeChild(link)
                    window.URL.revokeObjectURL(url)
                } else {
                    // Fallback to CSV if excel route not available
                    exportToCSV()
                }
            } catch (err) {
                // Fallback to CSV on error
                exportToCSV()
            } finally {
                isExporting.value = false
            }
        }

        const exportToCSV = () => {
            const data = props.products.map(product => ({
                'SL': product.serial,
                'Product Name': product.product_name,
                'Model': product.product_model,
                'Category': product.category,
                'Unit': product.unit,
                'Before Quantity': product.before_quantity,
                'Before Price': product.before_price,
                'Before Value': product.before_value,
                'Buy Quantity': product.buy_quantity,
                'Buy Price': product.buy_price,
                'Buy Total': product.total_buy_price,
                'Sale Quantity': product.sale_quantity,
                'Sale Price': product.sale_price,
                'Sale Subtotal': product.total_sale_price,
                'Sale Discount': product.sale_discount,
                'Sale Total': product.sale_after_discount,
                'Profit Per Unit': product.profit_per_unit,
                'Total Profit': product.total_profit,
                'Available Stock': product.available_quantity,
                'Stock Value': product.available_stock_value,
            }))

            // Add totals row
            data.push({
                'SL': '',
                'Product Name': 'TOTALS',
                'Model': '',
                'Category': '',
                'Unit': '',
                'Before Quantity': totalBeforeQuantity.value,
                'Before Price': '',
                'Before Value': totalBeforeValue.value,
                'Buy Quantity': totalBuyQuantity.value,
                'Buy Price': '',
                'Buy Total': totalBuyPrice.value,
                'Sale Quantity': totalSaleQuantity.value,
                'Sale Price': '',
                'Sale Subtotal': totalSalePrice.value,
                'Sale Discount': totalSaleDiscount.value,
                'Sale Total': totalSaleAfterDiscount.value,
                'Profit Per Unit': '',
                'Total Profit': totalProfit.value,
                'Available Stock': totalAvailableQuantity.value,
                'Stock Value': totalAvailableValue.value,
            })

            // Convert data to CSV format
            const headers = Object.keys(data[0]).join(',')
            const rows = data.map(item => Object.values(item).join(','))
            const csvContent = '\ufeff' + [headers, ...rows].join('\n') // Add BOM for Excel compatibility

            // Create blob and download link
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
            const link = document.createElement('a')

            // Create download URL
            const url = window.URL.createObjectURL(blob)
            link.setAttribute('href', url)
            link.setAttribute('download', `product-analysis-${props.filters.start_date}-to-${props.filters.end_date}.csv`)

            // Append link, trigger download, and cleanup
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)
        }

        return {
            searchQuery,
            isLoading,
            isDownloading,
            isExporting,
            isRefreshing,
            error,
            today,
            filteredProducts,
            profitMargin,
            totalBeforeQuantity,
            totalBeforeValue,
            totalBuyQuantity,
            totalBuyPrice,
            totalSaleQuantity,
            totalSalePrice,
            totalSaleDiscount,
            totalSaleAfterDiscount,
            totalProfit,
            totalAvailableQuantity,
            totalAvailableValue,
            handleDateChange,
            handleSearch,
            refreshData,
            formatNumber,
            formatCurrency,
            downloadPDF,
            exportToExcel,
        }
    },
})
</script>

<style scoped>
/* Add any custom styles here if needed */
.sticky {
    position: sticky;
    z-index: 10;
}

/* Ensure proper scrolling on mobile devices */
@media (max-width: 640px) {
    .overflow-x-auto {
        -webkit-overflow-scrolling: touch;
    }
}

/* Animation for loading spinner */
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>
