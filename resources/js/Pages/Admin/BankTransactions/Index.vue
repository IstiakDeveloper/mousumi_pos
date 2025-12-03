<template>
    <Head title="Bank Transactions" />
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Bank Transactions
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-green-500 to-green-600 overflow-hidden shadow-xl sm:rounded-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm font-medium">Available Balance</p>
                                <p class="text-3xl font-bold mt-2">{{ formatCurrency(summary.available_balance) }}</p>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-full p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 overflow-hidden shadow-xl sm:rounded-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm font-medium">Total In</p>
                                <p class="text-3xl font-bold mt-2">{{ formatCurrency(summary.total_in) }}</p>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-full p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-red-500 to-red-600 overflow-hidden shadow-xl sm:rounded-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-100 text-sm font-medium">Total Out</p>
                                <p class="text-3xl font-bold mt-2">{{ formatCurrency(summary.total_out) }}</p>
                            </div>
                            <div class="bg-white bg-opacity-20 rounded-full p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters & Actions -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4">
                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <input
                                v-model="localFilters.search"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Search transactions..."
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Transaction Type Filter -->
                        <select
                            v-model="localFilters.transaction_type"
                            @change="applyFilters"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="in">In</option>
                            <option value="out">Out</option>
                        </select>

                        <!-- Bank Account Filter -->
                        <select
                            v-model="localFilters.bank_account_id"
                            @change="applyFilters"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Accounts</option>
                            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                {{ account.account_name }}
                            </option>
                        </select>

                        <!-- Action Button -->
                        <a :href="route('admin.bank-transactions.create')"
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-center flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create
                        </a>
                    </div>

                    <!-- Date Range Filters -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Date</label>
                            <input
                                v-model="localFilters.date_from"
                                @change="applyFilters"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To Date</label>
                            <input
                                v-model="localFilters.date_to"
                                @change="applyFilters"
                                type="date"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="clearFilters"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" @click="sortBy('date')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <div class="flex items-center gap-1">
                                            Date
                                            <svg v-if="localFilters.sort_field === 'date'" class="w-4 h-4" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" @click="sortBy('bank_account_id')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <div class="flex items-center gap-1">
                                            Bank Account
                                            <svg v-if="localFilters.sort_field === 'bank_account_id'" class="w-4 h-4" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" @click="sortBy('transaction_type')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <div class="flex items-center gap-1">
                                            Type
                                            <svg v-if="localFilters.sort_field === 'transaction_type'" class="w-4 h-4" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" @click="sortBy('amount')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800">
                                        <div class="flex items-center gap-1">
                                            Amount
                                            <svg v-if="localFilters.sort_field === 'amount'" class="w-4 h-4" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="bankTransactions.data.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No transactions found
                                    </td>
                                </tr>
                                <tr v-for="transaction in bankTransactions.data" :key="transaction.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                            {{ formatDate(transaction.date) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                            {{ transaction.bank_account.account_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            :class="transaction.transaction_type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                            {{ formatTransactionType(transaction.transaction_type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold" :class="getAmountClass(transaction.transaction_type)">
                                            {{ formatCurrency(transaction.amount) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 dark:text-gray-300 max-w-xs truncate">
                                            {{ transaction.description || '-' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <a :href="route('admin.bank-transactions.edit', transaction.id)"
                                                class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <button @click="destroy(transaction.id)"
                                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <a v-if="bankTransactions.prev_page_url" :href="bankTransactions.prev_page_url"
                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Previous
                                </a>
                                <a v-if="bankTransactions.next_page_url" :href="bankTransactions.next_page_url"
                                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Next
                                </a>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        Showing
                                        <span class="font-medium">{{ bankTransactions.from || 0 }}</span>
                                        to
                                        <span class="font-medium">{{ bankTransactions.to || 0 }}</span>
                                        of
                                        <span class="font-medium">{{ bankTransactions.total }}</span>
                                        results
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <select v-model="localFilters.per_page" @change="applyFilters"
                                        class="px-3 py-1 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md text-sm">
                                        <option :value="10">10</option>
                                        <option :value="15">15</option>
                                        <option :value="25">25</option>
                                        <option :value="50">50</option>
                                        <option :value="100">100</option>
                                    </select>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <a v-for="link in bankTransactions.links" :key="link.label"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="[
                                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                                link.active
                                                    ? 'z-10 bg-blue-50 dark:bg-blue-900 border-blue-500 text-blue-600 dark:text-blue-200'
                                                    : 'bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                                                !link.url ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
                                            ]">
                                        </a>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3';
import { format } from 'date-fns'

export default {
    components: {
        AdminLayout,
        Head,
    },
    props: {
        bankTransactions: Object,
        summary: Object,
        bankAccounts: Array,
        filters: Object,
    },
    data() {
        return {
            localFilters: {
                search: this.filters.search || '',
                transaction_type: this.filters.transaction_type || '',
                bank_account_id: this.filters.bank_account_id || '',
                date_from: this.filters.date_from || '',
                date_to: this.filters.date_to || '',
                sort_field: this.filters.sort_field || 'date',
                sort_order: this.filters.sort_order || 'desc',
                per_page: this.filters.per_page || 15,
            },
            searchTimeout: null,
        }
    },
    methods: {
        formatCurrency(amount) {
            const number = new Intl.NumberFormat('en-BD', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount)
            return `৳ ${number}`
        },
        formatDate(date) {
            return format(new Date(date), 'MMM dd, yyyy')
        },
        formatTransactionType(type) {
            return type === 'in' ? 'Money In' : 'Money Out'
        },
        getAmountClass(type) {
            return type === 'in' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
        },
        debouncedSearch() {
            clearTimeout(this.searchTimeout)
            this.searchTimeout = setTimeout(() => {
                this.applyFilters()
            }, 500)
        },
        applyFilters() {
            router.get(route('admin.bank-transactions.index'), this.localFilters, {
                preserveState: true,
                preserveScroll: true,
            })
        },
        sortBy(field) {
            if (this.localFilters.sort_field === field) {
                this.localFilters.sort_order = this.localFilters.sort_order === 'asc' ? 'desc' : 'asc'
            } else {
                this.localFilters.sort_field = field
                this.localFilters.sort_order = 'asc'
            }
            this.applyFilters()
        },
        clearFilters() {
            this.localFilters = {
                search: '',
                transaction_type: '',
                bank_account_id: '',
                date_from: '',
                date_to: '',
                sort_field: 'date',
                sort_order: 'desc',
                per_page: 15,
            }
            this.applyFilters()
        },
        destroy(id) {
            if (confirm('Are you sure you want to delete this transaction? This will affect bank balances.')) {
                router.delete(route('admin.bank-transactions.destroy', id), {
                    preserveScroll: true,
                })
            }
        },
    },
}
</script>
