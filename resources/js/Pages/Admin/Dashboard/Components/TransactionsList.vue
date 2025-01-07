<template>
    <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-300">Recent Transactions</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">Date</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">Account</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">Type</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">Amount</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-300">Description</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    <tr v-for="transaction in transactions" :key="transaction.id">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-300">
                            {{ formatDate(transaction.date) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-300">
                            {{ transaction.bank_account.account_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span :class="{
                                'px-2 py-1 text-sm rounded-full': true,
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': transaction.transaction_type === 'in',
                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': transaction.transaction_type === 'out'
                            }">
                                {{ transaction.transaction_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-300">
                            {{ formatCurrency(transaction.amount) }}
                        </td>
                        <td class="px-6 py-4 text-gray-900 dark:text-gray-300">
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
    const number = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount);
    return `৳ ${number}`;
};

</script>
