<!-- resources/js/Pages/Admin/Dashboard/Components/TransactionsList.vue -->
<template>
    <div class="p-6 bg-white rounded-lg shadow-sm">
        <h3 class="mb-4 text-lg font-medium">Recent Transactions</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Account</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Description</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="transaction in transactions" :key="transaction.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ formatDate(transaction.date) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ transaction.bank_account.account_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'px-2 py-1 text-sm rounded-full': true,
                                'bg-green-100 text-green-800': transaction.transaction_type === 'in',
                                'bg-red-100 text-red-800': transaction.transaction_type === 'out'
                            }">
                                {{ transaction.transaction_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ formatCurrency(transaction.amount) }}
                        </td>
                        <td class="px-6 py-4">
                            {{ transaction.description }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from 'vue';

const props = defineProps({
    transactions: {
        type: Array,
        required: true
    }
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
};
</script>
