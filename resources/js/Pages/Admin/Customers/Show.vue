<template>
    <Head title="Customer" />
    <AdminLayout :title="'Customer Details - ' + customer.name" :user="$page.props.auth.user">
        <div class="container mx-auto px-4 py-6">
            <!-- Back Button -->
            <Link :href="route('admin.customers.index')"
                class="mb-6 inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
            <ArrowLeftIcon class="w-5 h-5 mr-2" />
            Back to Customers
            </Link>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Summary Cards -->
                <div class="space-y-6">
                    <!-- Customer Info Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Customer Information
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">Name</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ customer.name }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">Phone</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ customer.phone }}</div>
                            </div>
                            <div v-if="customer.email">
                                <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">Email</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ customer.email }}</div>
                            </div>
                            <div v-if="customer.address">
                                <label
                                    class="block text-sm font-medium text-gray-600 dark:text-gray-400">Address</label>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ customer.address }}</div>
                            </div>
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Status</span>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': customer.status,
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': !customer.status
                                    }">
                                        {{ customer.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Summary -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Sales Summary</h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">Total Sales</label>
                                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ sales_summary.total_invoices }}
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">Total Amount</label>
                                    <div class="mt-1 text-2xl font-bold text-blue-600">
                                        ৳{{ formatNumber(sales_summary.total_amount) }}
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">Total Paid</label>
                                    <div class="mt-1 text-2xl font-bold text-green-600">
                                        ৳{{ formatNumber(sales_summary.total_paid) }}
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600 dark:text-gray-400">Total Due</label>
                                    <div class="mt-1 text-2xl font-bold text-red-600">
                                        ৳{{ formatNumber(sales_summary.total_due) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Payments -->
                    <div v-if="recent_payments.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Recent Payments</h3>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div v-for="payment in recent_payments" :key="payment.id" class="p-4">
                                <div class="flex justify-between">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            ৳{{ formatNumber(payment.amount) }}
                                        </div>
                                        <div class="text-sm text-gray-500">{{ payment.invoice_no }}</div>
                                    </div>
                                    <div class="text-sm text-gray-500">{{ payment.date }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales List & Payment Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Add Payment Form -->
                    <div v-if="salesWithDue.length > 0" class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Add Payment</h3>
                        </div>
                        <form @submit.prevent="submitPayment" class="p-6 space-y-4">
                            <!-- Sale Selection -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">Invoice</label>
                                <select v-model="form.sale_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                    <option value="">Select Invoice</option>
                                    <option v-for="sale in salesWithDue" :key="sale.id" :value="sale.id">
                                        {{ sale.invoice_no }} - Due: ৳{{ formatNumber(sale.due) }}
                                    </option>
                                </select>
                                <div v-if="form.errors.sale_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.sale_id }}
                                </div>
                            </div>

                            <!-- Amount -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Amount</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                                        <span class="text-gray-500 sm:text-sm">৳</span>
                                    </div>
                                    <input type="number" v-model="form.amount"
                                        class="mt-1 block w-full pl-7 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                                        step="0.01" :max="selectedSaleDue"
                                        :placeholder="selectedSale ? `Max: ${formatNumber(selectedSaleDue)}` : '0.00'" />
                                </div>
                                <div v-if="form.errors.amount" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.amount }}
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Bank Account
                                </label>
                                <select v-model="form.bank_account_id" @change="handleBankAccountSelect"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                                    <option value="">Select Bank Account</option>
                                    <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                        {{ account.bank_name }} - {{ account.account_name }} ({{ account.account_number
                                        }})
                                    </option>
                                </select>
                                <div v-if="form.errors.bank_account_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.bank_account_id }}
                                </div>
                            </div>

                            <!-- Note -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Note</label>
                                <textarea v-model="form.note"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                                    rows="2"></textarea>
                                <div v-if="form.errors.note" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.note }}
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end pt-4">
                                <button type="submit"
                                    :disabled="form.processing || !form.amount || !form.bank_account_id || !form.sale_id"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                                    Add Payment
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Sales List -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Sales History</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Invoice
                                        </th>
                                        <th
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Total
                                        </th>
                                        <th
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Paid
                                        </th>
                                        <th
                                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Due
                                        </th>
                                        <th
                                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="sale in sales" :key="sale.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 dark:text-gray-100">{{ sale.invoice_no }}
                                            </div>
                                            <div class="text-xs text-gray-500">{{ sale.date }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                            ৳{{ formatNumber(sale.total) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600">
                                            ৳{{ formatNumber(sale.paid) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600">
                                            ৳{{ formatNumber(sale.due) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="{
                                                    'bg-green-100 text-green-800': sale.payment_status === 'paid',
                                                    'bg-yellow-100 text-yellow-800': sale.payment_status === 'partial',
                                                    'bg-red-100 text-red-800': sale.payment_status === 'due'
                                                }">
                                                {{ sale.payment_status }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ArrowLeft as ArrowLeftIcon } from 'lucide-vue-next'

const props = defineProps({
    customer: {
        type: Object,
        required: true
    },
    sales: {
        type: Array,
        required: true,
        default: () => []
    },
    sales_summary: {
        type: Object,
        required: true,
        default: () => ({
            total_invoices: 0,
            total_amount: '0.00',
            total_paid: '0.00',
            total_due: '0.00'
        })
    },
    recent_payments: {
        type: Array,
        default: () => []
    },
    bankAccounts: {
        type: Array,
        required: true,
        default: () => []
    }
})

const form = useForm({
    sale_id: '',
    amount: '',
    bank_account_id: '',
    payment_method: 'bank', // Default to bank since we're always using bank accounts
    note: ''
});

const handleBankAccountSelect = () => {
    if (form.bank_account_id) {
        form.payment_method = 'bank';
    }
};

const canSubmit = computed(() => {
    return !!form.amount && !!form.bank_account_id && !!form.sale_id && !form.processing;
});

// Computed Properties
const salesWithDue = computed(() => {
    return props.sales.filter(sale => {
        const due = parseFloat(sale.due || 0)
        return due > 0
    })
})

const selectedSale = computed(() => {
    if (!form.sale_id) return null
    return props.sales.find(sale => sale.id === form.sale_id) || null
})

const selectedSaleDue = computed(() => {
    if (!selectedSale.value) return 0
    return parseFloat(selectedSale.value.due || 0)
})

// Format number helper
const formatNumber = (value) => {
    if (!value) return '0.00'
    if (typeof value === 'string') {
        value = parseFloat(value)
    }
    return value.toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

// Methods
const submitPayment = () => {
    if (!canSubmit.value) return;

    if (selectedSale.value && parseFloat(form.amount) > selectedSaleDue.value) {
        form.amount = selectedSaleDue.value;
    }

    form.post(route('admin.customers.add-payment', props.customer.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Show success notification if you have one
            window.location.reload(); // Reload to refresh all balances
        }
    });
};

// Watch for changes
watch(() => form.sale_id, (newValue) => {
    if (newValue && selectedSale.value) {
        form.amount = selectedSale.value.due
    } else {
        form.amount = ''
    }
})
</script>

<style scoped>
.dark {
    color-scheme: dark;
}
</style>
