<template>
    <Head title="Sale" />
    <AdminLayout :title="'Sale Details - ' + sale.invoice_no">
        <div class="container mx-auto px-4 py-6">
            <!-- Header Actions -->
            <div class="mb-6 flex justify-between items-center">
                <Link :href="route('admin.sales.index')"
                    class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    <ArrowLeftIcon class="w-5 h-5 mr-2" />
                    Back to Sales
                </Link>
                <div class="flex space-x-3">
                    <button @click="printReceipt"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <PrinterIcon class="w-4 h-4 mr-2" />
                        Print Receipt
                    </button>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Sale Info & Items -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Info Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Sale Information
                            </h3>
                        </div>
                        <div class="p-6 grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Invoice No
                                </label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ sale.invoice_no }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Date
                                </label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ sale.date }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Payment Status
                                </label>
                                <div class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': sale.payment_status === 'paid',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': sale.payment_status === 'partial',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': sale.payment_status === 'due'
                                        }">
                                        {{ sale.payment_status }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Created By
                                </label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                    {{ sale.created_by }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Sale Items
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Product
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Unit Price
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Quantity
                                        </th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                            Subtotal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="item in sale.items" :key="item.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">
                                                {{ item.product.name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ item.product.sku }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                            ৳{{ formatNumber(item.unit_price) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900 dark:text-gray-100">
                                            ৳{{ formatNumber(item.subtotal) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Customer & Summary -->
                <div class="space-y-6">
                    <!-- Customer Info Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Customer Information
                            </h3>
                        </div>
                        <div class="p-6" v-if="sale.customer">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">
                                        Name
                                    </label>
                                    <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ sale.customer.name }}
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">
                                        Phone
                                    </label>
                                    <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ sale.customer.phone }}
                                    </div>
                                </div>
                                <div v-if="sale.customer.address">
                                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">
                                        Address
                                    </label>
                                    <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                        {{ sale.customer.address }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-6" v-else>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Walk-in Customer
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Payment Summary
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Subtotal</span>
                                    <span class="text-sm text-gray-900 dark:text-gray-100">
                                        ৳{{ formatNumber(sale.subtotal) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Discount</span>
                                    <span class="text-sm text-red-600 dark:text-red-400">
                                        -৳{{ formatNumber(sale.discount) }}
                                    </span>
                                </div>
                                <div class="flex justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                    <span class="text-base font-medium text-gray-900 dark:text-gray-100">Total</span>
                                    <span class="text-base font-medium text-gray-900 dark:text-gray-100">
                                        ৳{{ formatNumber(sale.total) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Paid Amount</span>
                                    <span class="text-sm text-green-600 dark:text-green-400">
                                        ৳{{ formatNumber(sale.paid) }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Due Amount</span>
                                    <span class="text-sm text-red-600 dark:text-red-400">
                                        ৳{{ formatNumber(sale.due) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Note Card -->
                    <div v-if="sale.note" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Note
                            </h3>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ sale.note }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import {
    ArrowLeft as ArrowLeftIcon,
    Printer as PrinterIcon
} from 'lucide-vue-next'

const props = defineProps({
    sale: {
        type: Object,
        required: true
    }
})

// Format number for currency display
const formatNumber = (value) => {
    return Number(value).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

// Print receipt in new window
const printReceipt = () => {
    window.open(route('admin.sales.print-receipt', props.sale.id), '_blank')
}
</script>

<style scoped>
.dark {
    color-scheme: dark;
}
</style>
