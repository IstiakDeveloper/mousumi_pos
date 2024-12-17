<template>
    <AdminLayout :title="`Customer: ${customer.name}`">
        <div class="container mx-auto px-4 py-6">
            <!-- Customer Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ customer.name }}
                    </h1>
                    <div class="text-gray-500 dark:text-gray-400 space-x-2">
                        <span>{{ customer.phone }}</span>
                        <span v-if="customer.email">• {{ customer.email }}</span>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <Link
                        :href="route('admin.customers.edit', customer.id)"
                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600"
                    >
                        <EditIcon class="w-5 h-5 inline-block mr-1" />
                        Edit
                    </Link>
                    <button
                        @click="toggleCustomerStatus"
                        class="px-4 py-2 rounded-lg"
                        :class="customer.status ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'"
                    >
                        {{ customer.status ? 'Deactivate' : 'Activate' }}
                    </button>
                </div>
            </div>

            <!-- Customer Details Grid -->
            <div class="grid md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Customer Info</h3>
                    <div class="space-y-2">
                        <p><strong>Address:</strong> {{ customer.address || 'N/A' }}</p>
                        <p><strong>Credit Limit:</strong> ৳{{ formatNumber(customer.credit_limit) }}</p>
                        <p><strong>Balance:</strong> ৳{{ formatNumber(customer.balance) }}</p>
                        <p><strong>Loyalty Points:</strong> {{ customer.points }}</p>
                        <p><strong>Status:</strong>
                            <span
                                :class="customer.status ? 'text-green-600' : 'text-red-600'"
                            >
                                {{ customer.status ? 'Active' : 'Inactive' }}
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Sales Summary -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Sales Summary</h3>
                    <div class="space-y-2">
                        <p><strong>Total Invoices:</strong> {{ salesSummary.total_invoices }}</p>
                        <p><strong>Total Sales:</strong> ৳{{ formatNumber(salesSummary.total_amount) }}</p>
                        <p><strong>Total Paid:</strong> ৳{{ formatNumber(salesSummary.total_paid) }}</p>
                        <p><strong>Total Due:</strong> ৳{{ formatNumber(salesSummary.total_due) }}</p>
                        <div class="flex space-x-2 mt-2">
                            <span class="badge bg-green-100 text-green-800">
                                Paid: {{ salesSummary.paid_invoices }}
                            </span>
                            <span class="badge bg-yellow-100 text-yellow-800">
                                Partial: {{ salesSummary.partial_invoices }}
                            </span>
                            <span class="badge bg-red-100 text-red-800">
                                Due: {{ salesSummary.due_invoices }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Sales Chart -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Monthly Sales</h3>
                    <LineChart
                        v-if="monthlySalesChartData.length"
                        :data="monthlySalesChartData"
                        :lines="[
                            { dataKey: 'total_amount', stroke: '#3B82F6' },
                            { dataKey: 'paid_amount', stroke: '#10B981' }
                        ]"
                        height={250}
                    />
                    <p v-else class="text-gray-500">No sales data available</p>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Recent Payments</h3>
                    <button
                        @click="openPaymentModal"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                    >
                        <PlusIcon class="w-5 h-5 inline-block mr-1" />
                        Add Payment
                    </button>
                </div>

                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Date</th>
                            <th class="text-left">Invoice</th>
                            <th class="text-right">Amount</th>
                            <th class="text-left">Method</th>
                            <th class="text-left">Bank</th>
                            <th class="text-left">Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="payment in recentPayments"
                            :key="payment.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="py-2">{{ payment.date }}</td>
                            <td>{{ payment.invoice_no }}</td>
                            <td class="text-right">৳{{ formatNumber(payment.amount) }}</td>
                            <td>{{ payment.payment_method }}</td>
                            <td>{{ payment.bank_account || 'N/A' }}</td>
                            <td>{{ payment.created_by }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Sales History -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">Sales History</h3>
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">Date</th>
                            <th class="text-left">Invoice</th>
                            <th class="text-right">Total</th>
                            <th class="text-right">Paid</th>
                            <th class="text-right">Due</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="sale in sales"
                            :key="sale.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="py-2">{{ sale.date }}</td>
                            <td>{{ sale.invoice_no }}</td>
                            <td class="text-right">৳{{ formatNumber(sale.total) }}</td>
                            <td class="text-right">৳{{ formatNumber(sale.paid) }}</td>
                            <td class="text-right">৳{{ formatNumber(sale.due) }}</td>
                            <td class="text-center">
                                <span
                                    :class="{
                                        'bg-green-100 text-green-800': sale.payment_status === 'paid',
                                        'bg-yellow-100 text-yellow-800': sale.payment_status === 'partial',
                                        'bg-red-100 text-red-800': sale.payment_status === 'due'
                                    }"
                                    class="px-2 py-1 rounded-full text-xs"
                                >
                                    {{ sale.payment_status }}
                                </span>
                            </td>
                            <td class="text-right">
                                <button
                                    @click="viewSaleDetails(sale)"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    <EyeIcon class="w-5 h-5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add Payment Modal -->
            <PaymentModal
                v-if="showPaymentModal"
                :customer-id="customer.id"
                @close="closePaymentModal"
            />
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
    EditIcon,
    PlusIcon,
    EyeIcon
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PaymentModal from '@/Components/PaymentModal.vue'
import LineChart from '@/Components/LineChart.vue'

// Props from the server
const props = defineProps({
    customer: Object,
    sales: Array,
    recent_payments: Array,
    sales_summary: Object,
    monthly_sales: Array
})

// Reactive state
const showPaymentModal = ref(false)

// Computed properties for chart and summary
const monthlySalesChartData = computed(() =>
    props.monthly_sales.map(month => ({
        ...month,
        month: new Date(month.month + '-01').toLocaleString('default', { month: 'short' })
    }))
)

const salesSummary = computed(() => props.sales_summary || {
    total_invoices: 0,
    total_amount: 0,
    total_paid: 0,
    total_due: 0,
    paid_invoices: 0,
    partial_invoices: 0,
    due_invoices: 0
})

const recentPayments = computed(() => props.recent_payments || [])
const sales = computed(() => props.sales || [])

// Methods
const toggleCustomerStatus = () => {
    router.put(route('admin.customers.toggle-status', props.customer.id), {}, {
        preserveState: true,
        preserveScroll: true
    })
}

const formatNumber = (value) => {
    return new Intl.NumberFormat('en-BD').format(value || 0)
}

const openPaymentModal = () => {
    showPaymentModal.value = true
}

const closePaymentModal = () => {
    showPaymentModal.value = false
}

const viewSaleDetails = (sale) => {
    router.visit(route('admin.sales.show', sale.id))
}
</script>
