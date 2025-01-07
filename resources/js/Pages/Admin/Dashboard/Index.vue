<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Date Range Selector -->
                <div class="mb-6">
                    <DateRangePicker
                        v-model:startDate="filters.startDate"
                        v-model:endDate="filters.endDate"
                        @update:range="updateDateRange"
                    />
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 gap-6 mb-6 sm:grid-cols-2 lg:grid-cols-3">
                    <SummaryCard
                        title="Total Sales"
                        :value="summary.total_sales"
                        icon="cash"
                        type="currency"
                    />
                    <SummaryCard
                        title="Total Profit"
                        :value="summary.total_profit"
                        icon="trending-up"
                        type="currency"
                    />
                    <SummaryCard
                        title="Total Expenses"
                        :value="summary.total_expenses"
                        icon="trending-down"
                        type="currency"
                    />
                    <SummaryCard
                        title="Cash Received"
                        :value="summary.cash_received"
                        icon="wallet"
                        type="currency"
                    />
                    <SummaryCard
                        title="Stock Value"
                        :value="summary.stock_value"
                        icon="package"
                        type="currency"
                    />
                    <SummaryCard
                        title="Stock Worth"
                        :value="summary.stock_worth"
                        icon="box"
                        type="currency"
                    />
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <SalesChart :data="salesData" />
                    <StockChart :data="stockData" />
                </div>

                <!-- Tables Section -->
                <div class="grid grid-cols-1 gap-6 mt-6">
                    <TransactionsList :transactions="bankTransactions" />
                    <ExpensesList :expenses="expensesData" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';

import SummaryCard from './Components/SummaryCard.vue';
import SalesChart from './Components/SalesChart.vue';
import StockChart from './Components/StockChart.vue';
import TransactionsList from './Components/TransactionsList.vue';
import ExpensesList from './Components/ExpensesList.vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DateRangePicker from '@/Components/DateRangePicker.vue';

const props = defineProps({
    salesData: {
        type: Array,
        required: true
    },
    stockData: {
        type: Array,
        required: true
    },
    expensesData: {
        type: Array,
        required: true
    },
    bankTransactions: {
        type: Array,
        required: true
    },
    summary: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        required: true
    }
});

const filters = ref({
    startDate: new Date(props.filters.startDate),
    endDate: new Date(props.filters.endDate)
});

const updateDateRange = () => {
    router.get(route('admin.dashboard'), {
        start_date: filters.value.startDate,
        end_date: filters.value.endDate
    }, {
        preserveState: true,
        preserveScroll: true
    });
};
</script>
