<template>
    <AdminLayout>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Product Stocks
                </h1>
                <button
                    @click="navigateToCreate"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition"
                >
                    Add Stock
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-700">
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                            >
                                Product
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                            >
                                Variant
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                            >
                                Quantity
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="stock in stocks.data"
                            :key="stock.id"
                            class="border-t border-gray-200 dark:border-gray-700"
                        >
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                {{ stock.product?.name || 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ stock.variant?.name || 'Default' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                {{ stock.quantity || 0 }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-4">
                                    <button
                                        @click="editStock(stock.id)"
                                        class="text-blue-600 hover:underline dark:text-blue-400"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="deleteStock(stock.id)"
                                        class="text-red-600 hover:underline dark:text-red-400"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p v-if="stocks.data.length === 0" class="text-center text-gray-500 dark:text-gray-400 mt-4">
                    No stock records found.
                </p>
            </div>

            <div class="mt-4">
                <button
                    v-if="stocks.prev_page_url"
                    @click="navigateToPage(stocks.prev_page_url)"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Previous
                </button>
                <button
                    v-if="stocks.next_page_url"
                    @click="navigateToPage(stocks.next_page_url)"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    Next
                </button>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import AdminLayout from '@/Layouts/AdminLayout.vue';

export default {
    props: {
        stocks: {
            type: Object,
            required: true,
        },
    },
    components: {
        AdminLayout,
    },
    methods: {
        navigateToCreate() {
            Inertia.visit(route('admin.product-stocks.create'));
        },
        editStock(id) {
            Inertia.visit(route('admin.product-stocks.edit', id));
        },
        deleteStock(id) {
            if (confirm('Are you sure you want to delete this stock?')) {
                Inertia.delete(route('admin.product-stocks.destroy', id), {
                    preserveScroll: true,
                });
            }
        },
        navigateToPage(url) {
            Inertia.visit(url, { preserveScroll: true });
        },
    },
};
</script>
