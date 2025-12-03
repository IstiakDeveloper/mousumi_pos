<!-- Index.vue -->
<template>

    <Head title="Product Stocks" />
    <AdminLayout>
        <div class="min-h-screen py-6 bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Product Stocks</h1>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Manage your inventory and stock levels
                        </p>
                    </div>
                    <Link :href="route('admin.product-stocks.create')"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <PlusIcon class="h-5 w-5 mr-2" />
                    Add Stock
                    </Link>
                </div>

                <!-- Summary Cards -->
                <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Products -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <CubeIcon class="h-6 w-6 text-gray-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Total Products
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ summary.total_products }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Stock Value -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <BanknotesIcon class="h-6 w-6 text-gray-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Current Stock Value
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            ৳{{ formatNumber(summary.total_value) }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Quantity -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ArchiveBoxIcon class="h-6 w-6 text-gray-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Available Quantity
                                        </dt>
                                        <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ formatNumber(summary.total_quantity) }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Items -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ExclamationTriangleIcon class="h-6 w-6 text-yellow-400" />
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            Low Stock Items
                                        </dt>
                                        <dd class="text-lg font-medium text-yellow-600 dark:text-yellow-400">
                                            {{ summary.low_stock_items }}
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search & Filters -->
                <div class="mt-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <input
                                v-model="localFilters.search"
                                @input="debouncedSearch"
                                type="text"
                                placeholder="Search by product name or SKU..."
                                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="flex gap-2">
                            <select
                                v-model="localFilters.per_page"
                                @change="applyFilters"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option :value="10">10 per page</option>
                                <option :value="15">15 per page</option>
                                <option :value="25">25 per page</option>
                                <option :value="50">50 per page</option>
                                <option :value="100">100 per page</option>
                            </select>
                            <button
                                v-if="localFilters.search"
                                @click="clearFilters"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>


                <!-- Stock Table -->
                <div class="mt-6 bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" @click="sortBy('product_name')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600">
                                        <div class="flex items-center gap-1">
                                            Product Info
                                            <svg v-if="localFilters.sort_field === 'product_name'" class="w-4 h-4" :class="{'rotate-180': localFilters.sort_order === 'desc'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                                            </svg>
                                        </div>
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Available Qty
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Total Purchased
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Avg. Unit Cost
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Current Value
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Total Purchase Cost
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th v-if="user.role.name.toLowerCase() === 'admin'" scope="col"
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="stocks.data.length === 0">
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        No products found
                                    </td>
                                </tr>
                                <tr v-for="stock in stocks.data" :key="stock.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ stock.product.name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        SKU: {{ stock.product.sku }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatNumber(stock.quantity) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ formatNumber(stock.total_purchased) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        ৳{{ formatNumber(stock.average_unit_cost) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        ৳{{ formatNumber(stock.current_stock_value) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900 dark:text-gray-100">
                                        ৳{{ formatNumber(stock.total_purchase_cost) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        All purchases
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{
                                        'bg-green-100 text-green-800': stock.stock_status === 'in',
                                        'bg-yellow-100 text-yellow-800': stock.stock_status === 'low',
                                        'bg-red-100 text-red-800': stock.stock_status === 'out'
                                    }">
                                        {{ stock.stock_status === 'in' ? 'In Stock' :
                                            stock.stock_status === 'low' ? 'Low Stock' : 'Out of Stock' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                                    <button @click="showHistory(stock.product)"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                        History
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a v-if="stocks.prev_page_url" :href="stocks.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Previous
                            </a>
                            <a v-if="stocks.next_page_url" :href="stocks.next_page_url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-700 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-700">
                                Next
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    Showing
                                    <span class="font-medium">{{ stocks.from || 0 }}</span>
                                    to
                                    <span class="font-medium">{{ stocks.to || 0 }}</span>
                                    of
                                    <span class="font-medium">{{ stocks.total }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <a v-for="link in stocks.links" :key="link.label"
                                        :href="link.url"
                                        v-html="link.label"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                            link.active
                                                ? 'z-10 bg-blue-50 dark:bg-blue-900 border-blue-500 text-blue-600 dark:text-blue-200'
                                                : 'bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700',
                                            !link.url ? 'cursor-not-allowed opacity-50' : 'cursor-pointer'
                                        ]">
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Stock History Modal - Full Width Professional Design -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showHistoryModal"
                    class="fixed inset-0 z-[100] overflow-y-auto"
                    @click.self="closeHistoryModal">

                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>

                    <!-- Modal Container -->
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <Transition
                            enter-active-class="transition ease-out duration-300"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-200"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95">
                            <div v-if="showHistoryModal"
                                class="relative w-full max-w-7xl bg-white dark:bg-gray-800 rounded-2xl shadow-2xl"
                                @click.stop>

                                <!-- Header with gradient -->
                                <div class="relative bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 px-8 py-6 rounded-t-2xl">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-2xl font-bold text-white">
                                                Stock History
                                            </h3>
                                            <p class="mt-1 text-blue-100 text-sm">
                                                {{ selectedProduct?.name }}
                                            </p>
                                        </div>
                                        <button
                                            @click="closeHistoryModal"
                                            class="rounded-full p-2 text-white/80 hover:text-white hover:bg-white/20 transition-all duration-200">
                                            <XMarkIcon class="w-6 h-6" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Table Content -->
                                <div class="p-6">
                                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                                        <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                                <thead class="bg-gray-100 dark:bg-gray-900 sticky top-0 z-10">
                                                    <tr>
                                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                            Date & Time
                                                        </th>
                                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                            Transaction Type
                                                        </th>
                                                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                            Quantity
                                                        </th>
                                                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                            Unit Cost
                                                        </th>
                                                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                            Total Cost
                                                        </th>
                                                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                            Stock Balance
                                                        </th>
                                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                            Note
                                                        </th>
                                                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                            Created By
                                                        </th>
                                                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                            Action
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                                    <tr v-if="stockHistory.length === 0">
                                                        <td colspan="9" class="px-6 py-16 text-center">
                                                            <div class="flex flex-col items-center justify-center">
                                                                <div class="rounded-full bg-gray-100 dark:bg-gray-700 p-4 mb-4">
                                                                    <ArchiveBoxIcon class="w-12 h-12 text-gray-400 dark:text-gray-500" />
                                                                </div>
                                                                <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No history records found</p>
                                                                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Stock transactions will appear here</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr v-for="entry in stockHistory" :key="entry.id"
                                                        class="hover:bg-blue-50/50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                {{ entry.created_at }}
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span :class="{
                                                                'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold uppercase tracking-wide': true,
                                                                'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-sm': entry.type === 'purchase',
                                                                'bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-sm': entry.type === 'adjustment',
                                                                'bg-gradient-to-r from-red-500 to-red-600 text-white shadow-sm': entry.type === 'sale'
                                                            }">
                                                                {{ entry.type }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100">
                                                                {{ formatNumber(entry.quantity) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                                                ৳{{ formatNumber(entry.unit_cost) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                                            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                                                ৳{{ formatNumber(entry.total_cost) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-700 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                                {{ formatNumber(entry.available_quantity) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4">
                                                            <span class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                                                {{ entry.note || '-' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="text-sm text-gray-900 dark:text-gray-100">
                                                                {{ entry.created_by }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                                            <button
                                                                v-if="entry.type === 'purchase' && userIsAdmin"
                                                                @click="openDeleteConfirmation(entry)"
                                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/40 text-red-700 dark:text-red-400 rounded-lg font-medium text-sm transition-all duration-200 hover:shadow-md">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                                Delete
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer -->
                                <div class="px-8 py-4 bg-gray-50 dark:bg-gray-900 rounded-b-2xl border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                                        <span>Total Entries: <strong class="text-gray-900 dark:text-gray-100">{{ stockHistory.length }}</strong></span>
                                        <button
                                            @click="closeHistoryModal"
                                            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium transition-colors">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Delete Confirmation Modal - Beautiful & Centered -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0">
                <div v-if="showDeleteConfirmation"
                    class="fixed inset-0 z-[200]"
                    @click.self="closeDeleteConfirmation">

                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>

                    <!-- Modal Container -->
                    <div class="fixed inset-0 flex items-center justify-center p-4">
                        <Transition
                            enter-active-class="transition ease-out duration-300"
                            enter-from-class="opacity-0 scale-90 -translate-y-4"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-200"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-90 -translate-y-4">
                            <div v-if="showDeleteConfirmation"
                                class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl shadow-2xl"
                                @click.stop>

                                <!-- Warning Icon Header -->
                                <div class="pt-8 pb-6 px-8 text-center">
                                    <div class="mx-auto w-20 h-20 rounded-full bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center shadow-lg shadow-red-500/50 animate-pulse-slow mb-6">
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>

                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                                        Delete Stock Entry?
                                    </h3>

                                    <p class="text-gray-600 dark:text-gray-400 text-base mb-6">
                                        This action is permanent and cannot be reversed.
                                    </p>

                                    <!-- Details Card -->
                                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-2 border-amber-200 dark:border-amber-800 rounded-xl p-6 text-left">
                                        <div class="flex items-start gap-3 mb-4">
                                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-500 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-amber-900 dark:text-amber-300 text-base mb-2">
                                                    What will happen:
                                                </h4>
                                                <ul class="space-y-2 text-sm text-amber-800 dark:text-amber-200">
                                                    <li class="flex items-start gap-2">
                                                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span><strong>Stock entry removed</strong> from records permanently</span>
                                                    </li>
                                                    <li class="flex items-start gap-2">
                                                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span><strong class="text-green-700 dark:text-green-400">৳{{ formatNumber(stockToDelete?.total_cost || 0) }} refunded</strong> to bank account</span>
                                                    </li>
                                                    <li class="flex items-start gap-2">
                                                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span><strong>Product average cost</strong> will be recalculated</span>
                                                    </li>
                                                    <li class="flex items-start gap-2">
                                                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                        <span><strong>Stock movement records</strong> adjusted automatically</span>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Warning Notice -->
                                    <div class="mt-5 flex items-center justify-center gap-2 text-red-600 dark:text-red-400 font-semibold">
                                        <svg class="w-5 h-5 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span>This action cannot be undone!</span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="px-8 pb-8 flex gap-3">
                                    <button
                                        @click="closeDeleteConfirmation"
                                        class="flex-1 px-6 py-3 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl font-semibold transition-all duration-200 hover:shadow-lg">
                                        Cancel
                                    </button>
                                    <button
                                        @click="deleteStock"
                                        class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-xl font-semibold transition-all duration-200 hover:shadow-lg hover:shadow-red-500/50 transform hover:scale-[1.02] active:scale-[0.98]">
                                        Yes, Delete Entry
                                    </button>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </Transition>
        </Teleport>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import {
    PlusIcon,
    CubeIcon,
    BanknotesIcon,
    ArchiveBoxIcon,
    ExclamationTriangleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const page = usePage();
const user = computed(() => page.props.auth.user);

const userIsAdmin = computed(() => {
    return user.value?.role?.name?.toLowerCase() === 'admin';
});

const stockToDelete = ref(null);
const showHistoryModal = ref(false);
const selectedProduct = ref(null);
const stockHistory = ref([]);
const showDeleteConfirmation = ref(false);



const props = defineProps({
    stocks: Object,
    summary: Object,
    filters: Object,
});

const localFilters = ref({
    search: props.filters?.search || '',
    sort_field: props.filters?.sort_field || 'product_id',
    sort_order: props.filters?.sort_order || 'asc',
    per_page: props.filters?.per_page || 15,
});

const searchTimeout = ref(null);

const confirmDelete = (stock) => {
    stockToDelete.value = stock;
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout.value);
    searchTimeout.value = setTimeout(() => {
        applyFilters();
    }, 500);
};

const applyFilters = () => {
    router.get(route('admin.product-stocks.index'), localFilters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const sortBy = (field) => {
    if (localFilters.value.sort_field === field) {
        localFilters.value.sort_order = localFilters.value.sort_order === 'asc' ? 'desc' : 'asc';
    } else {
        localFilters.value.sort_field = field;
        localFilters.value.sort_order = 'asc';
    }
    applyFilters();
};

const clearFilters = () => {
    localFilters.value = {
        search: '',
        sort_field: 'product_id',
        sort_order: 'asc',
        per_page: 15,
    };
    applyFilters();
};

const openDeleteConfirmation = (stock) => {
    stockToDelete.value = stock;
    showDeleteConfirmation.value = true;
};

const closeDeleteConfirmation = () => {
    stockToDelete.value = null;
    showDeleteConfirmation.value = false;
};

const deleteStock = async () => {
    if (!stockToDelete.value) return;

    try {
        const response = await axios.delete(
            route('admin.product-stocks.destroy', stockToDelete.value.id)
        );

        if (response.data.success) {
            await showHistory(selectedProduct.value);
            closeDeleteConfirmation();
        }
    } catch (error) {
        console.error('Error deleting stock:', error);
    }
};

// Modify closeHistoryModal to also close delete confirmation
const closeHistoryModal = () => {
    showHistoryModal.value = false;
    selectedProduct.value = null;
    stockHistory.value = [];
    closeDeleteConfirmation();
};

// Methods
const formatNumber = (value) => {
    if (!value) return '0.00';
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const showHistory = async (product) => {
    selectedProduct.value = product;
    try {
        const response = await axios.get(route('admin.product-stocks.history', product.id));
        stockHistory.value = response.data.data;
        showHistoryModal.value = true;
    } catch (error) {
        console.error('Error fetching stock history:', error);
    }
};


</script>

<style scoped>
.dark {
    color-scheme: dark;
}

/* Smooth animations */
@keyframes pulse-slow {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.9;
        transform: scale(1.05);
    }
}

.animate-pulse-slow {
    animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Smooth scrollbar for modal */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
}

.dark .overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
}

.dark .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}
</style>
