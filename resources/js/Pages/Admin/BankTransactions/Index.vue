<template>

    <Head title="Transections" />
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Bank Transactions
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Available Balance</h3>
                        <p class="text-2xl font-bold text-green-600">{{ formatCurrency(summary.available_balance) }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <input v-model="search" type="text" placeholder="Search..." class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900
                                          text-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2
                                          focus:ring-blue-500">
                            <a :href="route('admin.bank-transactions.create')"
                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                                Create Transaction
                            </a>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500
                                                         dark:text-gray-200 uppercase tracking-wider">
                                        Bank Account
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500
                                                         dark:text-gray-200 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500
                                                         dark:text-gray-200 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500
                                                         dark:text-gray-200 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500
                                                         dark:text-gray-200 uppercase tracking-wider">
                                        Description
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500
                                                         dark:text-gray-200 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="transaction in filteredTransactions" :key="transaction.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                            {{ transaction.bank_account.account_name }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm"
                                            :class="getTransactionTypeClass(transaction.transaction_type)">
                                            {{ formatTransactionType(transaction.transaction_type) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm" :class="getAmountClass(transaction.transaction_type)">
                                            {{ formatCurrency(transaction.amount) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500 dark:text-gray-200">
                                            {{ formatDate(transaction.date) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-500 dark:text-gray-200">
                                            {{ transaction.description }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a :href="route('admin.bank-transactions.edit', transaction.id)" class="text-indigo-600 dark:text-indigo-500 hover:text-indigo-900
                                                  dark:hover:text-indigo-400 mr-4">
                                            Edit
                                        </a>
                                        <a @click="destroy(transaction.id)" class="text-red-600 dark:text-red-500 hover:text-red-900
                                                  dark:hover:text-red-400 cursor-pointer">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3';
import { format } from 'date-fns'

export default {
    components: {
        AdminLayout,
    },
    props: {
        bankTransactions: Array,
        summary: Object,
    },
    data() {
        return {
            search: '',
        }
    },
    computed: {
        filteredTransactions() {
            return this.bankTransactions.filter(transaction =>
                transaction.bank_account.account_name.toLowerCase().includes(this.search.toLowerCase()) ||
                transaction.transaction_type.toLowerCase().includes(this.search.toLowerCase()) ||
                transaction.description?.toLowerCase().includes(this.search.toLowerCase())
            )
        },
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
            return type.split('_').map(word =>
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ')
        },
        getTransactionTypeClass(type) {
            const classes = {
                in: 'text-green-600',
                out: 'text-red-600',
            }
            return classes[type] || 'text-gray-500'
        },
        getAmountClass(type) {
            return this.getTransactionTypeClass(type)
        },
        destroy(id) {
            if (confirm('Are you sure you want to delete this transaction?')) {
                this.$inertia.delete(route('admin.bank-transactions.destroy', id))
            }
        },
    },
}
</script>
