<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                Sales Report
            </h2>
        </template>

        <!-- Filters -->
        <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <!-- Customer Select -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Customer
                    </label>
                    <select v-model="filters.customer_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">All Customers</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name }}
                        </option>
                    </select>
                </div>

                <!-- Bank Account Select -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Bank Account
                    </label>
                    <select v-model="filters.bank_account_id"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">All Bank Accounts</option>
                        <option v-for="bank in bank_accounts" :key="bank.id" :value="bank.id">
                            {{ bank.bank_name }} - {{ bank.account_number }}
                        </option>
                    </select>
                </div>

                <!-- Payment Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Payment Status
                    </label>
                    <select v-model="filters.payment_status"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters">
                        <option value="">All Statuses</option>
                        <option value="paid">Paid</option>
                        <option value="partial">Partial</option>
                        <option value="due">Due</option>
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

                <!-- Download Button -->
                <div class="flex items-end">
                    <button @click="downloadReport"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        <DocumentArrowDownIcon class="h-5 w-5 mr-2" />
                        Download Report
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sales</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ summary.total_sales }}
                </div>
                <div class="text-sm text-gray-500">Amount: {{ formatPrice(summary.total_amount) }}</div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Received</div>
                <div class="mt-1 text-2xl font-semibold text-green-600">
                    {{ formatPrice(summary.received) }}
                </div>
                <div class="text-sm text-gray-500">
                    {{ ((summary.received / summary.total_amount) * 100).toFixed(1) }}% Collected
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Due</div>
                <div class="mt-1 text-2xl font-semibold text-red-600">
                    {{ formatPrice(summary.due) }}
                </div>
                <div class="text-sm text-gray-500">
                    {{ ((summary.due / summary.total_amount) * 100).toFixed(1) }}% Outstanding
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Average Sale</div>
                <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                    {{ formatPrice(summary.total_amount / summary.total_sales) }}
                </div>
            </div>
        </div>

        <!-- Monthly Reports -->
        <div class="space-y-6">
            <div v-for="report in reports" :key="report.month"
                class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <!-- Month Header -->
                <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ report.month }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Sales: {{ report.summary.total_sales }} |
                                Amount: {{ formatPrice(report.summary.total_amount) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Received/Due</div>
                            <div class="font-medium">
                                <span class="text-green-600">{{ formatPrice(report.summary.received) }}</span>
                                /
                                <span class="text-red-600">{{ formatPrice(report.summary.due) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods Summary -->
                    <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <template v-for="(method, key) in report.summary.payment_methods" :key="key">
                            <div class="bg-gray-50 dark:bg-gray-700 rounded p-3">
                                <div class="text-sm font-medium mb-2">
                                    {{ formatPaymentMethod(key) }}
                                </div>
                                <div class="font-medium">{{ formatPrice(method.amount) }}</div>
                                <!-- Bank Details if present -->
                                <template v-if="method.bank_details && Object.keys(method.bank_details).length">
                                    <div class="mt-2 space-y-1">
                                        <div v-for="(bank, bankId) in method.bank_details" :key="bankId"
                                            class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ bank.bank_name }}: {{ formatPrice(bank.amount) }}
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Daily Sales -->
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <div v-for="day in report.daily_data" :key="day.date" class="p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-base font-medium text-gray-900 dark:text-white">
                                {{ day.date }}
                            </h4>
                            <div class="text-sm text-gray-500">
                                {{ day.summary.total_sales }} sales |
                                {{ formatPrice(day.summary.total_amount) }}
                            </div>
                        </div>

                        <!-- Sales Table -->
                        # Update the Sales Table section in your Vue template:

                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <th class="px-3 py-2 text-left">Time</th>
                                    <th class="px-3 py-2 text-left">Invoice</th>
                                    <th class="px-3 py-2 text-left">Customer</th>
                                    <th class="px-3 py-2 text-right">Total</th>
                                    <th class="px-3 py-2 text-right">Paid</th>
                                    <th class="px-3 py-2">Payment Details</th>
                                    <th class="px-3 py-2 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="sale in day.sales" :key="sale.id">
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">
                                        {{ sale.created_at }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        {{ sale.invoice_no }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm">
                                        {{ sale.customer }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-right">
                                        {{ formatPrice(sale.total) }}
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-green-600">
                                        {{ formatPrice(sale.paid) }}
                                    </td>
                                    <td class="px-3 py-2 text-sm">
                                        <div v-for="payment in sale.payments" :key="payment.transaction_id"
                                            class="text-xs">
                                            {{ formatPaymentMethod(payment.method) }}:
                                            {{ formatPrice(payment.amount) }}
                                            <template v-if="payment.bank_name">
                                                <br>
                                                <span class="text-gray-500">
                                                    {{ payment.bank_name }} - {{ payment.account_number }}
                                                    <template v-if="payment.transaction_id">
                                                        ({{ payment.transaction_id }})
                                                    </template>
                                                </span>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-center">
                                        <span :class="getStatusClass(sale.payment_status)">
                                            {{ sale.payment_status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-gray-50 dark:bg-gray-700 font-medium">
                                    <td colspan="3" class="px-3 py-2">Daily Total</td>
                                    <td class="px-3 py-2 text-right">{{ formatPrice(day.summary.total_amount) }}</td>
                                    <td class="px-3 py-2 text-right text-green-600">
                                        {{ formatPrice(day.summary.received) }}
                                    </td>
                                    <td colspan="2" class="px-3 py-2 text-right text-red-600">
                                        Due: {{ formatPrice(day.summary.due) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Daily Payment Methods -->
                        <div class="mt-3 flex flex-wrap gap-4">
                            <template v-for="(method, key) in day.summary.payment_methods" :key="key">
                                <div class="text-sm">
                                    <span class="text-gray-500">{{ formatPaymentMethod(key) }}:</span>
                                    <span class="font-medium">{{ formatPrice(method.amount) }}</span>
                                    <template v-if="method.bank_details && Object.keys(method.bank_details).length">
                                        <div class="text-xs text-gray-500">
                                            <div v-for="(bank, bankId) in method.bank_details" :key="bankId">
                                                {{ bank.bank_name }}: {{ formatPrice(bank.amount) }}
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { DocumentArrowDownIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    customers: Array,
    bank_accounts: Array,
    filters: Object,
    reports: Array,
    summary: Object
})


const filters = ref({
    customer_id: props.filters.customer_id,
    bank_account_id: props.filters.bank_account_id,
    payment_status: props.filters.payment_status,
    from_date: props.filters.from_date,
    to_date: props.filters.to_date
})

const formatPrice = (amount) => {
    return new Intl.NumberFormat('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount || 0) + ' BDT';
}

const formatPaymentMethod = (method) => {
    const methods = {
        'cash': 'Cash',
        'card': 'Card',
        'bank': 'Bank Transfer',
        'mobile_banking': 'Mobile Banking'
    };
    return methods[method] || method;
}

const getStatusClass = (status) => {
    const classes = {
        'paid': 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
        'partial': 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
        'due': 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
    };
    return classes[status] || '';
}

const applyFilters = () => {
    router.get(route('admin.reports.sales'), {
        customer_id: filters.value.customer_id,
        bank_account_id: filters.value.bank_account_id,
        payment_status: filters.value.payment_status,
        from_date: filters.value.from_date,
        to_date: filters.value.to_date
    }, {
        preserveState: true,
        preserveScroll: true,
        only: ['reports', 'summary']
    });
}

const downloadReport = () => {
    const params = new URLSearchParams({
        customer_id: filters.value.customer_id || '',
        bank_account_id: filters.value.bank_account_id || '',
        payment_status: filters.value.payment_status || '',
        from_date: filters.value.from_date || '',
        to_date: filters.value.to_date || ''
    }).toString();

    window.location.href = `${route('admin.reports.sales.download')}?${params}`;
}

const getPaymentMethodIcon = (method) => {
    switch (method) {
        case 'cash':
            return 'fa-money-bill';
        case 'card':
            return 'fa-credit-card';
        case 'bank':
            return 'fa-university';
        case 'mobile_banking':
            return 'fa-mobile-alt';
        default:
            return 'fa-money-bill';
    }
}

const resetFilters = () => {
    filters.value = {
        customer_id: '',
        bank_account_id: '',
        payment_status: '',
        from_date: new Date().toISOString().split('T')[0],
        to_date: new Date().toISOString().split('T')[0]
    };
    applyFilters();
}

const calculateDayTotal = (payments) => {
    return Object.values(payments).reduce((total, method) => total + method.amount, 0);
}

// Computed for summary percentages
const collectionPercentage = computed(() => {
    if (!props.summary.total_amount) return 0;
    return ((props.summary.received / props.summary.total_amount) * 100).toFixed(1);
});

const duePercentage = computed(() => {
    if (!props.summary.total_amount) return 0;
    return ((props.summary.due / props.summary.total_amount) * 100).toFixed(1);
});

// Watch for changes in bank_account_id to update related data
watch(() => filters.value.bank_account_id, (newVal) => {
    if (newVal) {
        // You might want to fetch additional bank-specific data here
        applyFilters();
    }
});
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

.payment-method {
    @apply flex items-center space-x-2 text-sm;
}

.bank-details {
    @apply text-xs text-gray-500 ml-6 mt-1;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
