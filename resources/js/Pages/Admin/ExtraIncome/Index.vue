<!-- resources/js/Pages/Admin/ExtraIncome/Index.vue -->
<template>
    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 leading-tight">
                        Extra Income Management
                    </h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Manage and track all extra income transactions
                    </p>
                </div>
                <Link :href="route('admin.extra-incomes.create')"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add New Income
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters and Search -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="col-span-1">
                            <InputLabel for="search" class="sr-only">Search</InputLabel>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <TextInput v-model="filters.search" type="text" name="search" id="search" class="pl-10"
                                    placeholder="Search by title or description..." @input="debouncedFilter" />
                            </div>
                        </div>

                        <!-- Bank Account Filter -->
                        <div class="col-span-1">
                            <select v-model="filters.bank_account"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                                @change="filterData">
                                <option value="">All Bank Accounts</option>
                                <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                    {{ account.bank_name }} - {{ account.account_number }}
                                </option>
                            </select>
                        </div>

                        <!-- Category Filter - Add this -->
                        <div class="col-span-1">
                            <select v-model="filters.category"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                                @change="filterData">
                                <option value="">All Categories</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Date Range -->
                        <div class="col-span-1">
                            <TextInput v-model="filters.date_from" type="date" class="w-full" placeholder="From Date"
                                @input="filterData" />
                        </div>
                        <div class="col-span-1">
                            <TextInput v-model="filters.date_to" type="date" class="w-full" placeholder="To Date"
                                @input="filterData" />
                        </div>
                    </div>

                    <!-- Active Filters -->
                    <div v-if="hasActiveFilters" class="mt-4 flex flex-wrap gap-2">
                        <div v-for="(filter, index) in activeFilters" :key="index"
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                            <span>{{ filter.label }}: {{ filter.value }}</span>
                            <button @click="removeFilter(filter.key)"
                                class="ml-2 inline-flex items-center p-0.5 rounded-full text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-800">
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" />
                                </svg>
                            </button>
                        </div>
                        <button @click="clearFilters"
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600">
                            Clear All
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <!-- Total Income -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center">

                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-green-500 p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Income</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{
                                    formatCurrency(statistics.totalIncome) }}</dd>
                            </div>
                        </div>
                    </div>

                    <!-- This Month -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-blue-500 p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">This Month</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{
                                    formatCurrency(statistics.thisMonthIncome) }}</dd>
                            </div>
                        </div>
                    </div>

                    <!-- Average Income -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-purple-500 p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Income</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{
                                    formatCurrency(statistics.averageIncome) }}</dd>
                            </div>
                        </div>
                    </div>

                    <!-- Total Transactions -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="rounded-md bg-yellow-500 p-3">
                                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transactions</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{
                                    statistics.totalTransactions }}</dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th v-for="header in tableHeaders" :key="header.key"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer"
                                    @click="sortBy(header.key)">
                                    <div class="flex items-center space-x-1">
                                        <span>{{ header.label }}</span>
                                        <span v-if="sort.key === header.key" class="text-gray-400">
                                            <svg v-if="sort.direction === 'asc'" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            </svg>
                                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </span>
                                    </div>
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Category
                                </th>
                                <th class="relative px-6 py-3">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="income in extraIncomes.data" :key="income.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ formatDate(income.date) }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{
                                        formatTime(income.created_at) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ income.title }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">
                                        {{ income.description || 'No description' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ income.bank_account.bank_name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ income.bank_account.account_number }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ income.category?.name || 'Uncategorized' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ formatCurrency(income.amount) }}
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-3">
                                        <Link :href="route('admin.extra-incomes.edit', income.id)"
                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                        Edit
                                        </Link>
                                        <button @click="confirmDelete(income)"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="extraIncomes.data.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No extra income records found
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div
                        class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                        <Pagination :links="extraIncomes.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="deleteModal.show" @close="deleteModal.show = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Confirm Delete
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Are you sure you want to delete this income record? This action cannot be undone.
                </p>
                <div class="mt-4 bg-gray-50 dark:bg-gray-700 rounded-md p-4">
                    <div class="text-sm">
                        <div><span class="font-medium">Title:</span> {{ deleteModal.income?.title }}</div>
                        <div><span class="font-medium">Amount:</span> {{ formatCurrency(deleteModal.income?.amount) }}
                        </div>
                        <div><span class="font-medium">Date:</span> {{ formatDate(deleteModal.income?.date) }}</div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                        @click="deleteModal.show = false">
                        Cancel
                    </button>
                    <button type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800"
                        :disabled="form.processing" @click="deleteIncome">
                        {{ form.processing ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import Pagination from '@/Components/Pagination.vue'
import Modal from '@/Components/Modal.vue'
import debounce from 'lodash/debounce'

const props = defineProps({
    extraIncomes: {
        type: Object,
        required: true
    },
    bankAccounts: {
        type: Array,
        required: true
    },
    categories: {
        type: Array,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    statistics: {
        type: Object,
        required: true
    }
})

const filters = ref({
    search: props.filters.search || '',
    bank_account: props.filters.bank_account || '',
    date_from: props.filters.date_from || '',
    category: props.filters.category || '',  // Add this
    date_to: props.filters.date_to || ''
})

const sort = ref({
    key: 'date',
    direction: 'desc'
})

const deleteModal = ref({
    show: false,
    income: null
})

const form = useForm({})

const tableHeaders = [
    { key: 'date', label: 'Date' },
    { key: 'title', label: 'Title' },

    { key: 'bank_account', label: 'Bank Account' },
    { key: 'category', label: 'Category' },
    { key: 'amount', label: 'Amount' }
]

const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => value !== '')
})

const activeFilters = computed(() => {
    const active = []

    if (filters.value.search) {
        active.push({ key: 'search', label: 'Search', value: filters.value.search })
    }

    if (filters.value.bank_account) {
        const bank = props.bankAccounts.find(b => b.id === Number(filters.value.bank_account))
        active.push({
            key: 'bank_account',
            label: 'Bank',
            value: bank ? `${bank.bank_name} - ${bank.account_number}` : ''
        })
    }

    if (filters.value.category) {
        const category = props.categories.find(c => c.id === Number(filters.value.category))
        active.push({
            key: 'category',
            label: 'Category',
            value: category ? category.name : ''
        })
    }

    if (filters.value.date_from) {
        active.push({ key: 'date_from', label: 'From', value: formatDate(filters.value.date_from) })
    }

    if (filters.value.date_to) {
        active.push({ key: 'date_to', label: 'To', value: formatDate(filters.value.date_to) })
    }

    return active
})

// Update clearFilters method to include category
const clearFilters = () => {
    filters.value = {
        search: '',
        bank_account: '',
        category: '',
        date_from: '',
        date_to: ''
    }
    filterData()
}

const formatDate = (date) => {
    if (!date) return ''
    return new Date(date).toLocaleDateString()
}

const formatTime = (datetime) => {
    if (!datetime) return ''
    return new Date(datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const formatCurrency = (amount) => {
    const formattedNumber = new Intl.NumberFormat('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0)

    return `৳ ${formattedNumber}`
}

const debouncedFilter = debounce(() => {
    filterData()
}, 300)

const filterData = () => {
    Inertia.get(
        route('admin.extra-incomes.index'),
        { ...filters.value, sort: `${sort.value.key}-${sort.value.direction}` },
        { preserveState: true, preserveScroll: true }
    )
}

const sortBy = (key) => {
    sort.value.direction = sort.value.key === key && sort.value.direction === 'asc' ? 'desc' : 'asc'
    sort.value.key = key
    filterData()
}

const removeFilter = (key) => {
    filters.value[key] = ''
    filterData()
}


const confirmDelete = (income) => {
    deleteModal.value = {
        show: true,
        income
    }
}

const deleteIncome = () => {
    if (!deleteModal.value.income) return

    form.delete(route('admin.extra-incomes.destroy', deleteModal.value.income.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleteModal.value.show = false
        }
    })
}
</script>
