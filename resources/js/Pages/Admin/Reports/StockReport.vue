<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Stock Movement Report
            </h2>
        </template>

        <!-- Filters -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <!-- Product Select -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Product
                    </label>
                    <select
                        v-model="filters.product_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters"
                    >
                        <option value="">All Products</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.name }} ({{ product.sku }})
                        </option>
                    </select>
                </div>

                <!-- Category Select -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Category
                    </label>
                    <select
                        v-model="filters.category_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters"
                    >
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        From Date
                    </label>
                    <input
                        type="date"
                        v-model="filters.from_date"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        To Date
                    </label>
                    <input
                        type="date"
                        v-model="filters.to_date"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters"
                    >
                </div>

                <!-- Download Button -->
                <div class="flex items-end">
                    <button
                        @click="downloadReport"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        <DocumentArrowDownIcon class="h-5 w-5 mr-2" />
                        Download Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Reports -->
        <div class="space-y-6">
            <div v-for="report in reports" :key="report.product.id"
                class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <!-- Product Header -->
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ report.product.name }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                SKU: {{ report.product.sku }} | Category: {{ report.product.category }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500 dark:text-gray-400">Opening Stock</div>
                            <div class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ report.opening_stock }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Data -->
                <div v-for="monthData in report.monthly_data" :key="monthData.month"
                    class="border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700">
                        <h4 class="font-medium text-gray-900 dark:text-white">{{ monthData.month }}</h4>
                    </div>

                    <!-- Stock In Transactions -->
                    <div v-if="monthData.stock_ins.length" class="px-4 py-3">
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Stock In</h5>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400">
                                    <th class="text-left py-2">Date</th>
                                    <th class="text-right py-2">Quantity</th>
                                    <th class="text-right py-2">Unit Cost</th>
                                    <th class="text-right py-2">Total Cost</th>
                                    <th class="text-left py-2">Note</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="stockIn in monthData.stock_ins" :key="stockIn.date"
                                    class="text-sm">
                                    <td class="py-2">{{ formatDate(stockIn.date) }}</td>
                                    <td class="text-right py-2">{{ stockIn.quantity }}</td>
                                    <td class="text-right py-2">{{ formatPrice(stockIn.unit_cost) }}</td>
                                    <td class="text-right py-2">{{ formatPrice(stockIn.total_cost) }}</td>
                                    <td class="py-2">{{ stockIn.note }}</td>
                                </tr>
                                <tr class="font-medium bg-gray-50 dark:bg-gray-700">
                                    <td class="py-2">Total</td>
                                    <td class="text-right py-2">{{ monthData.total_in }}</td>
                                    <td></td>
                                    <td class="text-right py-2">{{ formatPrice(monthData.in_value) }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Stock Out Transactions -->
                    <div v-if="monthData.stock_outs.length" class="px-4 py-3">
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Stock Out (Sales)</h5>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr class="text-xs text-gray-500 dark:text-gray-400">
                                    <th class="text-left py-2">Date</th>
                                    <th class="text-left py-2">Invoice</th>
                                    <th class="text-right py-2">Quantity</th>
                                    <th class="text-right py-2">Unit Price</th>
                                    <th class="text-right py-2">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="stockOut in monthData.stock_outs" :key="stockOut.date"
                                    class="text-sm">
                                    <td class="py-2">{{ formatDate(stockOut.date) }}</td>
                                    <td class="py-2">{{ stockOut.invoice_no }}</td>
                                    <td class="text-right py-2">{{ stockOut.quantity }}</td>
                                    <td class="text-right py-2">{{ formatPrice(stockOut.unit_price) }}</td>
                                    <td class="text-right py-2">{{ formatPrice(stockOut.total) }}</td>
                                </tr>
                                <tr class="font-medium bg-gray-50 dark:bg-gray-700">
                                    <td colspan="2" class="py-2">Total</td>
                                    <td class="text-right py-2">{{ monthData.total_out }}</td>
                                    <td></td>
                                    <td class="text-right py-2">{{ formatPrice(monthData.out_value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Monthly Summary -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700">
                        <div class="flex justify-between text-sm">
                            <div>Closing Stock: <span class="font-medium">{{ monthData.closing_stock }}</span></div>
                            <div>Net Movement: <span class="font-medium">{{ monthData.total_in - monthData.total_out }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Product Summary -->
                <div class="px-4 py-3 bg-gray-100 dark:bg-gray-700">
                    <div class="flex justify-between">
                        <div class="font-medium">Current Stock: {{ report.current_stock }}</div>
                        <div class="font-medium">
                            Stock Value: {{ formatPrice(report.current_stock * report.product.cost_price) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
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
    product_id: props.filters.product_id,
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
</script>
