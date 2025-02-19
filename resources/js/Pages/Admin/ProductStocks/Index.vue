<!-- Index.vue -->
<template>

    <Head title="Product Stocks" />
    <AdminLayout>
        <div class="min-h-screen py-6 bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Product Stocks</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Manage your inventory and stock levels
                        </p>
                    </div>
                    <Link :href="route('admin.product-stocks.create')"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Add Stock
                    </Link>
                </div>

                <!-- Summary Cards -->
                <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Products -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <CubeIcon class="h-6 w-6 text-gray-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Total Products
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ summary.total_products }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Stock Value -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <BanknotesIcon class="h-6 w-6 text-gray-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Current Stock Value
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            ৳{{ formatNumber(summary.total_value) }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Quantity -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ArchiveBoxIcon class="h-6 w-6 text-gray-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Available Quantity
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ formatNumber(summary.total_quantity) }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Items -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ExclamationTriangleIcon class="h-6 w-6 text-yellow-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Low Stock Items
                                        </dt>
                                        <dd class="text-lg font-medium text-yellow-600 dark:text-yellow-400">
                                            {{ summary.low_stock_items }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Stock Table -->
                <div class="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Product Info
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Available Qty
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Total Purchased
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Avg. Unit Cost
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Current Value
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Total Purchase Cost
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Status
                                </th>
                                <th v-if="user.role.name.toLowerCase() === 'admin'" scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="stock in stocks.data" :key="stock.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ stock.product.name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        SKU: {{ stock.product.sku }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatNumber(stock.quantity) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatNumber(stock.total_purchased) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        ৳{{ formatNumber(stock.average_unit_cost) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        ৳{{ formatNumber(stock.current_stock_value) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        ৳{{ formatNumber(stock.total_purchase_cost) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        All purchases
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                                        'bg-green-100 text-green-800': stock.stock_status === 'in',
                                        'bg-yellow-100 text-yellow-800': stock.stock_status === 'low',
                                        'bg-red-100 text-red-800': stock.stock_status === 'out'
                                    }">
                                        {{ stock.stock_status === 'in' ? 'In Stock' :
                                            stock.stock_status === 'low' ? 'Low Stock' : 'Out of Stock' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                                    <button @click="showHistory(stock.product)"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        History
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    <Pagination :links="stocks.links" />
                </div>
            </div>
        </div>



        <Modal :show="showHistoryModal" @close="closeHistoryModal" max-width="4xl">
            <div class="p-6">
                <!-- Modal header -->
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Stock History - {{ selectedProduct?.name }}
                    </h3>
                    <button @click="closeHistoryModal" class="text-gray-400 hover:text-gray-500">
                        <XMarkIcon class="w-6 h-6" />
                    </button>
                </div>

                <!-- Stock History Table -->
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Date
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Type
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">
                                Quantity</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Unit
                                Cost</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Total
                                Cost</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">
                                Balance</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Note
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">Created
                                By</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300">Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="entry in stockHistory" :key="entry.id"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-4 py-3 text-sm">{{ entry.created_at }}</td>
                            <td class="px-4 py-3 text-sm capitalize">
                                <span :class="{
                                    'px-2 py-1 rounded-full text-xs font-medium': true,
                                    'bg-blue-100 text-blue-800': entry.type === 'purchase',
                                    'bg-yellow-100 text-yellow-800': entry.type === 'adjustment',
                                    'bg-red-100 text-red-800': entry.type === 'sale'
                                }">
                                    {{ entry.type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right">{{ formatNumber(entry.quantity) }}</td>
                            <td class="px-4 py-3 text-sm text-right">৳{{ formatNumber(entry.unit_cost) }}</td>
                            <td class="px-4 py-3 text-sm text-right">৳{{ formatNumber(entry.total_cost) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ formatNumber(entry.available_quantity) }}</td>
                            <td class="px-4 py-3 text-sm">{{ entry.note }}</td>
                            <td class="px-4 py-3 text-sm">{{ entry.created_by }}</td>

                            <td class="px-4 py-3 text-sm text-right">
                                <button v-if="entry.type === 'purchase' && userIsAdmin"
                                    @click="openDeleteConfirmation(entry)"
                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Delete Confirmation Section (inside history modal) -->
                <div v-if="showDeleteConfirmation"
                    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Delete Stock Entry
                        </h3>
                        <p>Are you sure you want to delete this stock entry? This will:</p>
                        <ul class="list-disc ml-6 mt-2">
                            <li>Remove the stock entry</li>
                            <li>Refund the purchase amount to bank account</li>
                            <li>Update product's average cost</li>
                        </ul>
                        <p class="mt-2 text-sm text-red-600">This action cannot be undone.</p>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button @click="closeDeleteConfirmation" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700
                                   hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                Cancel
                            </button>
                            <button @click="deleteStock" class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium
                                   text-white hover:bg-red-700">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import {
    PlusIcon,
    CubeIcon,
    BanknotesIcon,
    ArchiveBoxIcon,
    ExclamationTriangleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth.user);

const userIsAdmin = computed(() => {
    return user.value?.role?.name?.toLowerCase() === 'admin';
});

const stockToDelete = ref(null);
const showHistoryModal = ref(false);
const selectedProduct = ref(null);
const stockHistory = ref([]);
const showDeleteConfirmation = ref(false);



const props = defineProps({
    stocks: Object,
    summary: Object
});

const confirmDelete = (stock) => {
    stockToDelete.value = stock;
};

const openDeleteConfirmation = (stock) => {
    stockToDelete.value = stock;
    showDeleteConfirmation.value = true;
};

const closeDeleteConfirmation = () => {
    stockToDelete.value = null;
    showDeleteConfirmation.value = false;
};

const deleteStock = async () => {
    if (!stockToDelete.value) return;

    try {
        const response = await axios.delete(
            route('admin.product-stocks.destroy', stockToDelete.value.id)
        );

        if (response.data.success) {
            await showHistory(selectedProduct.value);
            closeDeleteConfirmation();
        }
    } catch (error) {
        console.error('Error deleting stock:', error);
    }
};

// Modify closeHistoryModal to also close delete confirmation
const closeHistoryModal = () => {
    showHistoryModal.value = false;
    selectedProduct.value = null;
    stockHistory.value = [];
    closeDeleteConfirmation();
};

// Methods
const formatNumber = (value) => {
    if (!value) return '0.00';
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const showHistory = async (product) => {
    selectedProduct.value = product;
    try {
        const response = await axios.get(route('admin.product-stocks.history', product.id));
        stockHistory.value = response.data.data;
        showHistoryModal.value = true;
    } catch (error) {
        console.error('Error fetching stock history:', error);
    }
};


</script>

<style scoped>
.dark {
    color-scheme: dark;
}
.fixed {
    z-index: 60;
}
</style>
