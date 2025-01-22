# resources/js/Pages/Admin/Reports/BankReport.vue
<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Bank Balance Report
            </h2>
        </template>

        <!-- Filters -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Account Select -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Bank Account
                    </label>
                    <select v-model="filters.account_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">All Accounts</option>
                        <option v-for="account in accounts" :key="account.id" :value="account.id">
                            {{ account.bank_name }} - {{ account.account_name }}
                        </option>
                    </select>
                </div>

                <!-- Date Range -->
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

                <!-- Action Buttons -->
                <div class="flex items-end gap-2">
                    <button @click="resetFilters"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                        <ArrowPathIcon class="h-5 w-5 mr-2" />
                        Reset
                    </button>
                    <button @click="exportReport"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        <DocumentArrowDownIcon class="h-5 w-5 mr-2" />
                        Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Accounts</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ summary.total_accounts }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Balance</div>
                <div class="mt-1 text-2xl font-semibold" :class="getBalanceColorClass(summary.total_balance)">
                    {{ formatPrice(summary.total_balance) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Inflows</div>
                <div class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                    {{ formatPrice(summary.total_inflows) }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Outflows</div>
                <div class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                    {{ formatPrice(summary.total_outflows) }}
                </div>
            </div>
        </div>

        <!-- Account Reports -->
        <div class="space-y-6">
            <TransitionGroup name="fade">
                <div v-for="report in reports" :key="report.account.id"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <!-- Account Header -->
                    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ report.account.bank }} - {{ report.account.name }}
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Account No: {{ report.account.number }}
                                </p>
                            </div>
                            <div class="text-right">
                                <div class="text-sm text-gray-500 dark:text-gray-400">Current Balance</div>
                                <div class="text-lg font-medium" :class="getBalanceColorClass(report.current_balance)">
                                    {{ formatPrice(report.current_balance) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Summary -->
                    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Monthly Summary</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-left">Month</th>
                                        <th class="px-4 py-2 text-right">Inflows</th>
                                        <th class="px-4 py-2 text-right">Outflows</th>
                                        <th class="px-4 py-2 text-right">Net</th>
                                        <th class="px-4 py-2 text-right">Transactions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="summary in report.monthly_summary" :key="summary.month"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                        <td class="px-4 py-2">{{ summary.month }}</td>
                                        <td class="px-4 py-2 text-right text-green-600">
                                            {{ formatPrice(summary.inflows) }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-red-600">
                                            {{ formatPrice(summary.outflows) }}
                                        </td>
                                        <td class="px-4 py-2 text-right" :class="getBalanceColorClass(summary.net)">
                                            {{ formatPrice(summary.net) }}
                                        </td>
                                        <td class="px-4 py-2 text-right">{{ summary.transaction_count }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Detailed Transactions -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left">Date</th>
                                    <th class="px-4 py-3 text-left">Description</th>
                                    <th class="px-4 py-3 text-left">Type</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                    <th class="px-4 py-3 text-right">Balance</th>
                                    <th class="px-4 py-3 text-left">Created By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="transaction in report.transactions" :key="transaction.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-4 py-3">{{ formatDate(transaction.date) }}</td>
                                    <td class="px-4 py-3">{{ transaction.description }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="getTransactionTypeClass(transaction.type)"
                                            class="px-2 py-1 rounded-full text-xs font-medium">
                                            {{ formatTransactionType(transaction.type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right" :class="getAmountColorClass(transaction.type)">
                                        {{ formatPrice(transaction.amount) }}
                                    </td>
                                    <td class="px-4 py-3 text-right" :class="getBalanceColorClass(transaction.running_balance)">
                                        {{ formatPrice(transaction.running_balance) }}
                                    </td>
                                    <td class="px-4 py-3">{{ transaction.created_by }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { DocumentArrowDownIcon, ArrowPathIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    accounts: {
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
})

const filters = ref({
    account_id: props.filters.account_id,
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
        month: 'long',
        day: 'numeric'
    })
}

const getBalanceColorClass = (amount) => {
    if (amount > 0) return 'text-green-600 dark:text-green-400'
    if (amount < 0) return 'text-red-600 dark:text-red-400'
    return 'text-gray-600 dark:text-gray-400'
}

const getAmountColorClass = (type) => {
    return type === 'in' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
}

const formatTransactionType = (type) => {
    return type === 'in' ? 'Inflow' : 'Outflow'
}

const getTransactionTypeClass = (type) => {
    return type === 'in'
        ? 'bg-green-100 text-green-800 dark:bg-green-200 dark:text-green-900'
        : 'bg-red-100 text-red-800 dark:bg-red-200 dark:text-red-900'
}

const applyFilters = () => {
    router.get(route('admin.reports.bank'), {
        account_id: filters.value.account_id,
        from_date: filters.value.from_date,
        to_date: filters.value.to_date
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['reports', 'summary']
    })
}

const exportReport = () => {
    const params = new URLSearchParams({
        account_id: filters.value.account_id || '',
        from_date: filters.value.from_date || '',
        to_date: filters.value.to_date || ''
    }).toString()

    window.location.href = `${route('admin.reports.bank.download')}?${params}`
}

const resetFilters = () => {
    filters.value = {
        account_id: '',
        from_date: new Date().toISOString().split('T')[0],
        to_date: new Date().toISOString().split('T')[0]
    }
    applyFilters()
}
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

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
