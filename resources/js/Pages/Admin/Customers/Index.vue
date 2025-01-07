<template>
    <Head title="Customers" />
    <AdminLayout title="Customers">
        <div class="container mx-auto px-4 py-6">
            <!-- Header Actions -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" v-model="search" placeholder="Search customers..."
                            class="w-64 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200" />
                        <SearchIcon class="absolute right-3 top-2.5 w-5 h-5 text-gray-400" />
                    </div>
                    <select v-model="filters.status"
                        class="border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200">
                        <option :value="null">All Status</option>
                        <option :value="true">Active</option>
                        <option :value="false">Inactive</option>
                    </select>
                </div>
                <Link :href="route('admin.customers.create')"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <PlusIcon class="w-5 h-5 inline-block mr-1" />
                    Add Customer
                </Link>
            </div>

            <!-- Customers Table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Customer Info
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Total Sales
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Due Amount
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Status
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="customer in customers.data" :key="customer.id">
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ customer.name }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ customer.phone }}
                                </div>
                                <div v-if="customer.email" class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ customer.email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    ৳{{ formatNumber(customer.total_sales) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div v-if="customer.total_due > 0" class="text-sm text-red-600 dark:text-red-400">
                                    ৳{{ formatNumber(customer.total_due) }}
                                </div>
                                <div v-else class="text-sm text-green-600 dark:text-green-400">
                                    ৳0.00
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': customer.status,
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': !customer.status
                                    }">
                                    {{ customer.status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-3">
                                <Link :href="route('admin.customers.show', customer.id)"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                                    View
                                </Link>
                                <Link :href="route('admin.customers.edit', customer.id)"
                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300">
                                    Edit
                                </Link>
                                <button @click="confirmToggleStatus(customer)"
                                    class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300">
                                    {{ customer.status ? 'Deactivate' : 'Activate' }}
                                </button>
                                <button v-if="!customer.total_sales" @click="confirmDelete(customer)"
                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <tr v-if="customers.data.length === 0">
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No customers found
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                    <Pagination :links="customers.links" />
                </div>
            </div>
        </div>

        <!-- Status Toggle Confirmation Modal -->
        <ConfirmationModal :show="!!customerToToggle" @close="customerToToggle = null"
            :title="customerToToggle?.status ? 'Deactivate Customer' : 'Activate Customer'"
            :message="customerToToggle?.status ?
                'Are you sure you want to deactivate this customer? They won\'t be able to make purchases while inactive.' :
                'Are you sure you want to activate this customer?'"
            :confirm-text="customerToToggle?.status ? 'Deactivate' : 'Activate'"
            @confirm="toggleStatus"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="!!customerToDelete" @close="customerToDelete = null"
            title="Delete Customer"
            message="Are you sure you want to delete this customer? This action cannot be undone."
            confirm-text="Delete"
            @confirm="deleteCustomer"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import _ from 'lodash'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import {
    Search as SearchIcon,
    Plus as PlusIcon
} from 'lucide-vue-next'

const props = defineProps({
    customers: Object,
    filters: Object
});

// Search and Filters
const search = ref(props.filters.search || '');
const filters = ref({
    status: props.filters.status || null,
    search: props.filters.search || ''
});

// Modals state
const customerToToggle = ref(null);
const customerToDelete = ref(null);

// Debounced search
watch(search, _.debounce((value) => {
    filters.value.search = value;
    applyFilters();
}, 300));

// Methods
const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const applyFilters = () => {
    router.get(route('admin.customers.index'), filters.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const confirmToggleStatus = (customer) => {
    customerToToggle.value = customer;
};

const toggleStatus = () => {
    if (!customerToToggle.value) return;

    router.post(route('admin.customers.toggle-status', customerToToggle.value.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            customerToToggle.value = null;
        }
    });
};

const confirmDelete = (customer) => {
    customerToDelete.value = customer;
};

const deleteCustomer = () => {
    if (!customerToDelete.value) return;

    router.delete(route('admin.customers.destroy', customerToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            customerToDelete.value = null;
        }
    });
};

// Watch filters
watch(() => filters.value.status, applyFilters);
</script>
