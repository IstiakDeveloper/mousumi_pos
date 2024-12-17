<template>
    <AdminLayout title="Customers">
        <div class="container mx-auto px-4 py-6">
            <!-- Header Actions -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input
                            type="text"
                            v-model="search"
                            @input="handleSearch"
                            placeholder="Search customers..."
                            class="w-64 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
                        />
                        <SearchIcon class="absolute right-3 top-2.5 w-5 h-5 text-gray-400" />
                    </div>
                    <select
                        v-model="filters.status"
                        @change="handleFilterChange"
                        class="border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
                    >
                        <option :value="null">All Status</option>
                        <option :value="true">Active</option>
                        <option :value="false">Inactive</option>
                    </select>
                </div>
                <Link
                    :href="route('admin.customers.create')"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                >
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
                                <div class="text-sm text-gray-900 dark:text-gray-100">
                                    ৳{{ formatNumber(customer.total_due) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span
                                    :class="customer.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                >
                                    {{ customer.status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end space-x-2">
                                    <Link
                                        :href="route('admin.customers.show', customer.id)"
                                        class="text-blue-600 hover:text-blue-900"
                                        title="View Details"
                                    >
                                        <EyeIcon class="w-5 h-5" />
                                    </Link>
                                    <Link
                                        :href="route('admin.customers.edit', customer.id)"
                                        class="text-yellow-600 hover:text-yellow-900"
                                        title="Edit Customer"
                                    >
                                        <EditIcon class="w-5 h-5" />
                                    </Link>
                                    <button
                                        @click="toggleCustomerStatus(customer.id)"
                                        class="text-gray-600 hover:text-gray-900"
                                        title="Toggle Status"
                                    >
                                        <ToggleLeftIcon v-if="customer.status" class="w-5 h-5 text-red-500" />
                                        <ToggleRightIcon v-else class="w-5 h-5 text-green-500" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-700">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link
                            v-if="customers.prev_page_url"
                            :href="customers.prev_page_url"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Previous
                        </Link>
                        <Link
                            v-if="customers.next_page_url"
                            :href="customers.next_page_url"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            Next
                        </Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Showing
                                <span class="font-medium">{{ customers.from }}</span>
                                to
                                <span class="font-medium">{{ customers.to }}</span>
                                of
                                <span class="font-medium">{{ customers.total }}</span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <Link
                                    v-for="link in customers.links"
                                    :key="link.label"
                                    :href="link.url || ''"
                                    :class="[
                                        link.active ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                    ]"
                                    v-html="link.label"
                                />
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import {
    SearchIcon,
    PlusIcon,
    EyeIcon,
    EditIcon,
    ToggleLeftIcon,
    ToggleRightIcon
} from 'lucide-vue-next'
import AdminLayout from '@/Layouts/AdminLayout.vue'

// Destructure props from Inertia
const props = defineProps({
    customers: Object,
    filters: Object
})

// Reactive search and filter state
const search = ref(props.filters.search || '')
const filters = ref({
    status: props.filters.status || null
})

// Debounced search handler
const handleSearch = () => {
    router.get(route('admin.customers.index'), {
        search: search.value,
        status: filters.value.status
    }, {
        preserveState: true,
        replace: true
    })
}

// Filter change handler
const handleFilterChange = () => {
    router.get(route('admin.customers.index'), {
        search: search.value,
        status: filters.value.status
    }, {
        preserveState: true,
        replace: true
    })
}

// Toggle customer status
const toggleCustomerStatus = (customerId) => {
    router.put(route('admin.customers.toggle-status', customerId), {}, {
        preserveState: true,
        preserveScroll: true
    })
}

// Number formatting utility
const formatNumber = (value) => {
    return new Intl.NumberFormat('en-BD').format(value || 0)
}
</script>
