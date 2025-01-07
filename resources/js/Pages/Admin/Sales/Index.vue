<template>

    <Head title="Sales" />
    <AdminLayout title="Sales List">
        <!-- Page Container -->
        <div class="container mx-auto px-4 py-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <!-- Total Sales Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Sales</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ summary.total_sales }}
                            </p>
                        </div>
                        <ShoppingBagIcon class="w-12 h-12 text-blue-500" />
                    </div>
                </div>

                <!-- Total Amount Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Amount</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                ৳{{ formatNumber(summary.total_amount) }}
                            </p>
                        </div>
                        <BanknoteIcon class="w-12 h-12 text-green-500" />
                    </div>
                </div>

                <!-- Total Paid Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Paid</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                ৳{{ formatNumber(summary.total_paid) }}
                            </p>
                        </div>
                        <CreditCardIcon class="w-12 h-12 text-purple-500" />
                    </div>
                </div>

                <!-- Total Due Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Due</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                ৳{{ formatNumber(summary.total_due) }}
                            </p>
                        </div>
                        <ClockIcon class="w-12 h-12 text-red-500" />
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                <div class="p-6">
                    <form @submit.prevent="applyFilters">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                            <!-- Date Filters -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Start Date
                                </label>
                                <input type="date" v-model="filters.start_date"
                                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    End Date
                                </label>
                                <input type="date" v-model="filters.end_date"
                                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm" />
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Payment Status
                                </label>
                                <select v-model="filters.payment_status"
                                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm">
                                    <option :value="null">All</option>
                                    <option v-for="status in paymentStatuses" :key="status" :value="status">
                                        {{ status }}
                                    </option>
                                </select>
                            </div>

                            <!-- Customer Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Customer
                                </label>
                                <select v-model="filters.customer_id"
                                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm">
                                    <option :value="null">All</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.name }} ({{ customer.phone }})
                                    </option>
                                </select>
                            </div>

                            <!-- Invoice Search -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Invoice No
                                </label>
                                <input type="text" v-model="filters.invoice_no" placeholder="Search by invoice no..."
                                    class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 rounded-md shadow-sm" />
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="mt-4 flex justify-end space-x-3">
                            <button type="button" @click="resetFilters"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Reset
                            </button>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sales Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <!-- Table Headers -->
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Invoice No
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Total
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Paid
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Due
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="sale in sales.data" :key="sale.id">
                            <!-- Invoice Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                    <Link :href="route('admin.sales.show', sale.id)">
                                    {{ sale.invoice_no }}
                                    </Link>
                                </div>
                            </td>

                            <!-- Date Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ sale.date }}
                                </div>
                            </td>

                            <!-- Customer Column -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div v-if="sale.customer" class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ sale.customer.name }}
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ sale.customer.phone }}
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-500 dark:text-gray-400">
                                    Walk-in Customer
                                </div>
                            </td>

                            <!-- Amount Columns -->
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    ৳{{ formatNumber(sale.total) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm text-green-600 dark:text-green-400">
                                    ৳{{ formatNumber(sale.paid) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-sm text-red-600 dark:text-red-400">
                                    ৳{{ formatNumber(sale.due) }}
                                </div>
                            </td>

                            <!-- Status Column -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': sale.payment_status === 'paid',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': sale.payment_status === 'partial',
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': sale.payment_status === 'due'
                                }">
                                    {{ sale.payment_status }}
                                </span>
                            </td>

                            <!-- Actions Column -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm space-x-3">
                                <Link :href="route('admin.sales.show', sale.id)"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                View
                                </Link>
                                <Link :href="route('admin.sales.edit', sale.id)"
                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                Edit
                                </Link>
                                <button @click="printReceipt(sale.id)"
                                    class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                                    Print
                                </button>
                                <button @click.prevent="confirmDelete(sale)"
                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="sales.data.length === 0">
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No sales found
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                    <Pagination :links="sales.links" />
                </div>
            </div>
        </div>

        <!-- Replace the old modal with this -->
        <ConfirmationModal :show="!!saleToDelete" title="Delete Sale"
            message="Are you sure you want to delete this sale? This will reverse all transactions and stock changes."
            confirm-text="Delete Sale" @close="saleToDelete = null" @confirm="deleteSale" />
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import Pagination from '@/Components/Pagination.vue'
import {
    ShoppingBag as ShoppingBagIcon,
    Banknote as BanknoteIcon,
    CreditCard as CreditCardIcon,
    Clock as ClockIcon
} from 'lucide-vue-next'

const props = defineProps({
    sales: Object,
    filters: Object,
    customers: Array,
    paymentStatuses: Array,
    summary: Object
})

// State
const saleToDelete = ref(null)
const filters = ref({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    payment_status: props.filters.payment_status || null,
    customer_id: props.filters.customer_id || null,
    invoice_no: props.filters.invoice_no || ''
})

// Methods
const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const applyFilters = () => {
    router.get(route('admin.sales.index'), filters.value, {
        preserveState: true,
        preserveScroll: true
    })
}

const resetFilters = () => {
    filters.value = {
        start_date: '',
        end_date: '',
        payment_status: null,
        customer_id: null,
        invoice_no: ''
    }
    applyFilters()
}

const printReceipt = (id) => {
    window.open(route('admin.sales.print-receipt', id), '_blank')
}

const canDelete = (sale) => {
    const saleDate = new Date(sale.created_at)
    const daysDiff = Math.floor((new Date() - saleDate) / (1000 * 60 * 60 * 24))
    return daysDiff <= 7
}

const confirmDelete = (sale) => {
    // Make sure we're setting the full sale object
    console.log('Confirming delete for sale:', sale); // Debug
    saleToDelete.value = sale;
}

const deleteSale = () => {
    if (!saleToDelete.value) {
        console.log('No sale to delete'); // Debug
        return;
    }

    router.delete(route('admin.sales.destroy', { sale: saleToDelete.value.id }), {
        onSuccess: () => {
            console.log('Delete successful'); // Debug
            saleToDelete.value = null;
        },
        onError: (errors) => {
            console.error('Delete failed:', errors); // Debug
        },
        preserveScroll: true,
        preserveState: true
    });
}
</script>

<style scoped>
.dark {
    color-scheme: dark;
}
</style>
