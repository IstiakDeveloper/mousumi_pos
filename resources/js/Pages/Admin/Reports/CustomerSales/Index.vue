<script setup>
import { ref, onMounted } from 'vue';
import Modal from '@/Components/Modal.vue';
import { Printer } from 'lucide-vue-next';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios'; // Add this import

const props = defineProps({
    customers: {
        type: Array,
        required: true
    },
    months: {
        type: Array,
        required: true
    },
    years: {
        type: Array,
        required: true
    }
});

const filters = ref({
    customer_id: '',
    year: new Date().getFullYear(),
    month: new Date().getMonth() + 1,
});

const reportData = ref(null);
const selectedSale = ref(null);
const loading = ref(false);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0);
};

const loadReport = async () => {
    if (!filters.value.customer_id) {
        reportData.value = null;
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(route('reports.customer-sales.data'), {
            params: filters.value
        });
        reportData.value = response.data;
    } catch (error) {
        console.error('Error loading report:', error);
    } finally {
        loading.value = false;
    }
};

const showSaleDetails = (sale) => {
    selectedSale.value = sale;
};

const printReport = () => {
    window.print();
};

// Load report if customer is pre-selected
onMounted(() => {
    if (filters.value.customer_id) {
        loadReport();
    }
});

const calculateTotal = (items) => {
    return items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0);
};
</script>

<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Customer Sales Report
                </h2>
                <button v-if="reportData" @click="printReport"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-gray-600 hover:bg-gray-700 rounded-md">
                    <Printer class="w-4 h-4 mr-2" />
                    Print Report
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Filters Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Customer Select -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Customer</label>
                                <select v-model="filters.customer_id" @change="loadReport"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Customer</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.name }} - {{ customer.phone }}
                                    </option>
                                </select>
                            </div>

                            <!-- Year Select -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Year</label>
                                <select v-model="filters.year" @change="loadReport"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="year in years" :key="year.value" :value="year.value">
                                        {{ year.label }}
                                    </option>
                                </select>
                            </div>

                            <!-- Month Select -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Month</label>
                                <select v-model="filters.month" @change="loadReport"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option v-for="month in months" :key="month.value" :value="month.value">
                                        {{ month.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div v-if="loading" class="text-center py-12">
                    <div class="spinner"></div>
                    Loading...
                </div>

                <!-- Report Content -->
                <div v-else-if="reportData" class="space-y-6">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900">Previous Balance</h3>
                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ formatCurrency(reportData.previousBalance) }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900">Monthly Sales</h3>
                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ formatCurrency(reportData.monthlyTotals.total_sales) }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900">Monthly Paid</h3>
                                <p class="mt-2 text-3xl font-bold text-gray-900">
                                    {{ formatCurrency(reportData.monthlyTotals.total_paid) }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900">Current Due</h3>
                                <p class="mt-2 text-3xl font-bold text-red-600">
                                    {{ formatCurrency(reportData.monthlyTotals.total_due) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Table -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Sales Details</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th
                                                class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date
                                            </th>
                                            <th
                                                class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Invoice
                                            </th>
                                            <th
                                                class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Total
                                            </th>
                                            <th
                                                class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Paid
                                            </th>
                                            <th
                                                class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Due
                                            </th>
                                            <th
                                                class="px-6 py-3 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="sale in reportData.monthlySales" :key="sale.id">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ sale.date }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ sale.invoice_no }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                                {{ formatCurrency(sale.total) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                                {{ formatCurrency(sale.paid) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                                {{ formatCurrency(sale.due) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                <button @click="showSaleDetails(sale)"
                                                    class="text-indigo-600 hover:text-indigo-900">
                                                    View Details
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Data Selected Message -->
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <p class="text-gray-500 text-center">
                            Please select a customer to view their sales report.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sale Details Modal -->
        <Modal :show="!!selectedSale" @close="selectedSale = null">
            <!-- ... rest of the modal code remains the same ... -->
        </Modal>
    </AdminLayout>
</template>

<style scoped>
@media print {
    .no-print {
        display: none;
    }

    .print-full-width {
        width: 100% !important;
        max-width: none !important;
    }
}

.spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>
