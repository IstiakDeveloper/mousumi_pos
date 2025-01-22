<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Stock Movement Report
                </h2>
                <button @click="downloadReport"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <DocumentArrowDownIcon class="h-5 w-5 mr-2" />
                    Export Report
                </button>
            </div>
        </template>

        <!-- Filters -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Product
                    </label>
                    <select v-model="filters.product_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">All Products</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.name }} ({{ product.sku }})
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Category
                    </label>
                    <select v-model="filters.category_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        From Date
                    </label>
                    <input type="date" v-model="filters.from_date"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        To Date
                    </label>
                    <input type="date" v-model="filters.to_date"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                </div>
            </div>
        </div>

        <!-- Reports -->
        <div v-for="report in reports" :key="report.product.id"
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- Product Header -->
            <div class="px-4 py-4 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white flex items-center gap-2">
                            {{ report.product.name }}
                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                {{ report.product.sku }}
                            </span>
                        </h3>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400 flex items-center gap-4">
                            <span>Category: {{ report.product.category }}</span>
                            <span>Unit: {{ report.product.unit }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="space-y-1">
                            <div class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Opening:</span>
                                <span class="ml-2 font-medium">{{ report.opening_stock }}</span>
                            </div>
                            <div class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Current:</span>
                                <span class="ml-2 font-medium" :class="getStockClass(report.current_stock)">
                                    {{ report.current_stock }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Data -->
            <div v-for="monthData in report.monthly_data" :key="monthData.month"
                class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                <!-- Month Header -->
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 flex justify-between items-center">
                    <h4 class="font-medium text-gray-900 dark:text-white">{{ monthData.month }}</h4>
                    <div class="flex items-center gap-4 text-sm">
                        <span class="text-gray-600 dark:text-gray-300">
                            Opening: {{ monthData.opening_stock }}
                        </span>
                        <span :class="getMovementClass(monthData.total_in - monthData.total_out)">
                            Net: {{ monthData.total_in - monthData.total_out }}
                        </span>
                        <span class="font-medium">
                            Closing: {{ monthData.closing_stock }}
                        </span>
                    </div>
                </div>

                <!-- Stock Movements -->
                <div class="p-4 space-y-4">
                    <!-- Stock Ins -->
                    <div v-if="monthData.stock_ins.length" class="overflow-x-auto">
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Stock In</h5>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-2 text-left">Date</th>
                                    <th class="px-4 py-2 text-right">Quantity</th>
                                    <th class="px-4 py-2 text-right">Unit Cost</th>
                                    <th class="px-4 py-2 text-right">Total Cost</th>
                                    <th class="px-4 py-2 text-left">Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="stockIn in monthData.stock_ins" :key="stockIn.date"
                                    class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-2">{{ formatDate(stockIn.date) }}</td>
                                    <td class="px-4 py-2 text-right">{{ stockIn.quantity }}</td>
                                    <td class="px-4 py-2 text-right">{{ formatPrice(stockIn.unit_cost) }}</td>
                                    <td class="px-4 py-2 text-right">{{ formatPrice(stockIn.total_cost) }}</td>
                                    <td class="px-4 py-2">{{ stockIn.note }}</td>
                                </tr>
                                <tr class="text-sm font-medium bg-gray-50 dark:bg-gray-700">
                                    <td class="px-4 py-2">Total</td>
                                    <td class="px-4 py-2 text-right">{{ monthData.total_in }}</td>
                                    <td></td>
                                    <td class="px-4 py-2 text-right">{{ formatPrice(monthData.in_value) }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Stock Outs -->
                    <div v-if="monthData.stock_outs.length" class="overflow-x-auto">
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Stock Out</h5>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400">
                                    <th class="px-4 py-2 text-left">Date</th>
                                    <th class="px-4 py-2 text-left">Invoice</th>
                                    <th class="px-4 py-2 text-right">Quantity</th>
                                    <th class="px-4 py-2 text-right">Unit Price</th>
                                    <th class="px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="stockOut in monthData.stock_outs" :key="stockOut.date"
                                    class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-2">{{ formatDate(stockOut.date) }}</td>
                                    <td class="px-4 py-2">{{ stockOut.invoice_no }}</td>
                                    <td class="px-4 py-2 text-right">{{ stockOut.quantity }}</td>
                                    <td class="px-4 py-2 text-right">{{ formatPrice(stockOut.unit_price) }}</td>
                                    <td class="px-4 py-2 text-right">{{ formatPrice(stockOut.total) }}</td>
                                </tr>
                                <tr class="text-sm font-medium bg-gray-50 dark:bg-gray-700">
                                    <td colspan="2" class="px-4 py-2">Total</td>
                                    <td class="px-4 py-2 text-right">{{ monthData.total_out }}</td>
                                    <td></td>
                                    <td class="px-4 py-2 text-right">{{ formatPrice(monthData.out_value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Product Summary -->
            <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-6">
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Total In:</span>
                            <span class="ml-2 text-green-600 dark:text-green-400 font-medium">
                                {{ report.total_stock_in }}
                            </span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 dark:text-gray-400">Total Out:</span>
                            <span class="ml-2 text-red-600 dark:text-red-400 font-medium">
                                {{ report.total_stock_out }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Stock Value:</span>
                        <span class="ml-2 font-medium text-gray-900 dark:text-white">
                            {{ formatPrice(report.total_stock_value) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { DocumentArrowDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    products: {
        type: Array,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    filters: {
        type: Object,
        required: true
    },
    reports: {
        type: Array,
        required: true
    }
})

const filters = ref({
    product_id: props.filters.product_id || (props.products[0]?.id || ''),
    category_id: props.filters.category_id,
    from_date: props.filters.from_date,
    to_date: props.filters.to_date
})

const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
    }).format(amount || 0)
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getStockClass = (stock) => {
    if (stock <= 0) return 'text-red-600 dark:text-red-400'
    if (stock < 10) return 'text-yellow-600 dark:text-yellow-400'
    return 'text-green-600 dark:text-green-400'
}

const getMovementClass = (movement) => {
    if (movement > 0) return 'text-green-600 dark:text-green-400'
    if (movement < 0) return 'text-red-600 dark:text-red-400'
    return 'text-gray-600 dark:text-gray-400'
}

const applyFilters = () => {
    router.get(route('admin.reports.stock'), {
        product_id: filters.value.product_id,
        category_id: filters.value.category_id,
        from_date: filters.value.from_date,
        to_date: filters.value.to_date
    }, {
        preserveState: true,
        preserveScroll: true
    })
}

const downloadReport = () => {
    const params = new URLSearchParams({
        product_id: filters.value.product_id || '',
        category_id: filters.value.category_id || '',
        from_date: filters.value.from_date || '',
        to_date: filters.value.to_date || ''
    }).toString()

    window.location.href = `${route('admin.reports.stock.download')}?${params}`
}

// Initialize with first product selected
onMounted(() => {
    if (!filters.value.product_id && props.products.length > 0) {
        filters.value.product_id = props.products[0].id
        applyFilters()
    }
})
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.table-hover tr:hover {
    @apply bg-gray-50 dark:bg-gray-700;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
