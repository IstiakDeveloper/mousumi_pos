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
        <div v-if="reports.length > 0" class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Overall Summary ({{ filters.from_date }}
                    to {{
                    filters.to_date }})</h3>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-3 gap-6">
                    <!-- Purchase Summary -->
                    <div class="p-4 bg-green-50 dark:bg-green-900/10 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Total Purchases</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600 dark:text-gray-400">Quantity:</span>
                                <span class="font-medium text-green-600">
                                    {{ summary.total_purchase_quantity }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Value:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(summary.total_purchase_value) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div class="p-4 bg-red-50 dark:bg-red-900/10 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Total Sales</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600 dark:text-gray-400">Quantity:</span>
                                <span class="font-medium text-red-600">
                                    {{ summary.total_sales_quantity }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Value:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(summary.total_sales_value) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Summary -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Current Stock</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between border-b pb-2">
                                <span class="text-gray-600 dark:text-gray-400">Quantity:</span>
                                <span class="font-medium">
                                    {{ summary.total_current_stock }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Value:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(summary.total_stock_value) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-for="report in reports" :key="report.product.id"
            class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <!-- Product Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ report.product.name }}
                            <span class="ml-2 text-sm text-gray-500">({{ report.product.sku }})</span>
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ report.product.category }} | {{ report.product.unit }}
                        </p>
                    </div>
                    <div class="text-sm">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-500">Opening Stock:</p>
                                <p class="font-medium">{{ report.summary.opening_stock }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Current Stock:</p>
                                <p class="font-medium" :class="getStockClass(report.summary.current_stock)">
                                    {{ report.summary.current_stock }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid grid-cols-4 gap-4 p-4 bg-gray-50 dark:bg-gray-700">
                <div>
                    <p class="text-sm text-gray-500">Total Purchased</p>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ report.summary.total_purchased }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Sold</p>
                    <p class="text-lg font-medium text-red-600 dark:text-red-400">
                        {{ report.summary.total_sold }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Average Cost</p>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ formatPrice(report.summary.avg_cost) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Stock Value</p>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ formatPrice(report.summary.stock_value) }}
                    </p>
                </div>
            </div>

            <div class="p-4">
                <div class="grid grid-cols-2 gap-6">
                    <!-- Purchase History -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Purchase History</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <th class="px-4 py-2 text-left">Date</th>
                                        <th class="px-4 py-2 text-right">Qty</th>
                                        <th class="px-4 py-2 text-right">Unit Cost</th>
                                        <th class="px-4 py-2 text-right">Total</th>
                                        <th class="px-4 py-2 text-right">Available</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="purchase in report.purchases" :key="purchase.date"
                                        class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-2">{{ formatDate(purchase.date) }}</td>
                                        <td class="px-4 py-2 text-right">{{ purchase.quantity }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(purchase.unit_cost) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(purchase.total_cost) }}</td>
                                        <td class="px-4 py-2 text-right">{{ purchase.available_quantity }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="font-medium bg-gray-50 dark:bg-gray-700">
                                        <td class="px-4 py-2">Total</td>
                                        <td class="px-4 py-2 text-right">{{ report.summary.total_purchased }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(report.summary.avg_cost) }}</td>
                                        <td class="px-4 py-2 text-right" colspan="2">
                                            {{ formatPrice(report.purchases.reduce((sum, p) => sum + p.total_cost, 0))
                                            }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Sales History -->
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">Sales History</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <td class="px-4 py-2 text-left">Date</td>
                                        <td class="px-4 py-2 text-right">Qty</td>
                                        <td class="px-4 py-2 text-right">Cost</td>
                                        <td class="px-4 py-2 text-right">Total</td>
                                        <td class="px-4 py-2 text-right">Available</td>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="sale in report.sales" :key="sale.date"
                                        class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-2">{{ formatDate(sale.date) }}</td>
                                        <td class="px-4 py-2 text-right text-red-600">{{ sale.quantity }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(sale.unit_cost) }}</td>
                                        <td class="px-4 py-2 text-right">{{ formatPrice(sale.total_cost) }}</td>
                                        <td class="px-4 py-2 text-right">{{ sale.available_quantity }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="font-medium bg-gray-50 dark:bg-gray-700">
                                        <td class="px-4 py-2">Total</td>
                                        <td class="px-4 py-2 text-right text-red-600">
                                            {{ report.summary.total_sold }}
                                        </td>
                                        <td class="px-4 py-2"></td>
                                        <td class="px-4 py-2 text-right" colspan="2">
                                            {{ formatPrice(report.sales.reduce((sum, s) => sum + s.total_cost, 0)) }}
                                        </td>
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
    },
    summary: {
        type: Object,
        required: true
    }
});

const filters = ref({
    product_id: props.filters.product_id || '',
    category_id: props.filters.category_id || '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || ''
});


const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const getStockClass = (stock) => {
    if (stock <= 0) return 'text-red-600';
    if (stock < 10) return 'text-yellow-600';
    return 'text-green-600';
};


const getMovementClass = (movement) => {
    if (movement > 0) return 'text-green-600 dark:text-green-400'
    if (movement < 0) return 'text-red-600 dark:text-red-400'
    return 'text-gray-600 dark:text-gray-400'
}


const applyFilters = () => {
    router.get(route('admin.reports.stock'), {
        ...filters.value
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

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
