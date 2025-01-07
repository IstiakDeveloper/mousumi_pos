<!-- resources/js/Pages/Admin/Dashboard/Components/ExpensesList.vue -->
<template>
    <div class="p-6 bg-white rounded-lg shadow-sm">
        <h3 class="mb-4 text-lg font-medium">Recent Expenses</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Reference</th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Description</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="expense in expenses" :key="expense.id">
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ formatDate(expense.date) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ expense.expense_category.name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-red-600">
                            {{ formatCurrency(expense.amount) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ expense.reference_no }}
                        </td>
                        <td class="px-6 py-4">
                            {{ expense.description }}
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
    expenses: {
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
