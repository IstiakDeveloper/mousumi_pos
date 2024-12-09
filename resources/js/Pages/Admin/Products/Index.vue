<!-- resources/js/Pages/Admin/Products/Index.vue -->
<template>
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header Section -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <!-- Stats & Actions -->
                    <div class="flex flex-wrap items-center justify-between sm:flex-nowrap">
                        <div class="flex-grow">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Products</h2>
                            <div class="mt-2 flex flex-wrap gap-4">
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                    <CubeIcon class="mr-1.5 h-5 w-5 flex-shrink-0" />
                                    {{ products.total }} Total Products
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                    <ShieldCheckIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-green-500" />
                                    {{ activeCount }} Active
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                    <ExclamationTriangleIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-yellow-500" />
                                    {{ lowStockCount }} Low Stock
                                </div>
                            </div>
                        </div>
                        <div class="flex flex-shrink-0 gap-2">
                            <!-- View Toggle -->
                            <div class="flex rounded-md shadow-sm">
                                <button @click="viewMode = 'grid'" :class="[
                                    viewMode === 'grid'
                                        ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                    'relative inline-flex items-center px-3 py-2 text-sm font-medium ring-1 ring-inset ring-gray-300 dark:ring-gray-600 rounded-l-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:z-10'
                                ]">
                                    <Squares2X2Icon class="h-5 w-5" />
                                </button>
                                <button @click="viewMode = 'table'" :class="[
                                    viewMode === 'table'
                                        ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                        : 'bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                                    'relative -ml-px inline-flex items-center px-3 py-2 text-sm font-medium ring-1 ring-inset ring-gray-300 dark:ring-gray-600 rounded-r-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:z-10'
                                ]">
                                    <ListBulletIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- Actions Dropdown -->
                            <Menu as="div" class="relative">
                                <MenuButton
                                    class="inline-flex items-center gap-x-1.5 rounded-md bg-white dark:bg-gray-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Actions
                                    <ChevronDownIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                </MenuButton>

                                <transition enter-active-class="transition ease-out duration-200"
                                    enter-from-class="opacity-0 translate-y-1"
                                    enter-to-class="opacity-100 translate-y-0"
                                    leave-active-class="transition ease-in duration-150"
                                    leave-from-class="opacity-100 translate-y-0"
                                    leave-to-class="opacity-0 translate-y-1">
                                    <MenuItems
                                        class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <MenuItem v-slot="{ active }">
                                        <button @click="exportProducts" :class="[
                                            active ? 'bg-gray-100 dark:bg-gray-700' : '',
                                            'flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200'
                                        ]">
                                            <ArrowDownTrayIcon class="mr-3 h-5 w-5 text-gray-400" />
                                            Export Products
                                        </button>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                        <button @click="importProducts" :class="[
                                            active ? 'bg-gray-100 dark:bg-gray-700' : '',
                                            'flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200'
                                        ]">
                                            <ArrowUpTrayIcon class="mr-3 h-5 w-5 text-gray-400" />
                                            Import Products
                                        </button>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                        <button @click="printBarcodes" :class="[
                                            active ? 'bg-gray-100 dark:bg-gray-700' : '',
                                            'flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200'
                                        ]">
                                            <QrCodeIcon class="mr-3 h-5 w-5 text-gray-400" />
                                            Print Barcodes
                                        </button>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>

                            <Link :href="route('admin.products.create')"
                                class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                            <PlusIcon class="h-5 w-5" />
                            Add Product
                            </Link>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Search -->
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                            </div>
                            <input v-model="search" type="text" placeholder="Search products..."
                                class="block w-full rounded-md border-0 py-1.5 pl-10 pr-3 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 sm:text-sm sm:leading-6"
                                @input="debouncedSearch">
                        </div>

                        <!-- Category Filter -->
                        <select v-model="filters.category_id"
                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 sm:text-sm sm:leading-6"
                            @change="filterChanged">
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>

                        <!-- Brand Filter -->
                        <select v-model="filters.brand_id"
                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 sm:text-sm sm:leading-6"
                            @change="filterChanged">
                            <option value="">All Brands</option>
                            <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                {{ brand.name }}
                            </option>
                        </select>

                        <!-- Status Filter -->
                        <select v-model="filters.status"
                            class="block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 sm:text-sm sm:leading-6"
                            @change="filterChanged">
                            <option value="">All Status</option>
                            <option :value="1">Active</option>
                            <option :value="0">Inactive</option>
                        </select>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                <!-- Grid View -->
                <div v-if="viewMode === 'grid'"
                    class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="product in products.data" :key="product.id"
                        class="bg-white dark:bg-gray-800 overflow-hidden rounded-lg shadow transition-shadow hover:shadow-lg">
                        <!-- Product Image -->
                        <div class="aspect-w-3 aspect-h-2">
                            <img v-if="product.image" :src="getImageUrl(product.image)" class="h-48 w-full object-cover"
                                :alt="product.name">
                            <div v-else
                                class="h-48 w-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <PhotoIcon class="h-12 w-12 text-gray-400" />
                            </div>
                        </div>

                        <!-- Product Info -->
                        <div class="p-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate">
                                {{ product.name }}
                            </h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                SKU: {{ product.sku }}
                            </p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-lg font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(product.selling_price) }}
                                </span>
                                <span :class="[
                                    'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                    product.status
                                        ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100'
                                        : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-100'
                                ]">
                                    {{ product.status ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <div class="mt-4 flex justify-end space-x-2">
                                <Link :href="route('admin.products.show', product.id)"
                                    class="rounded-md bg-white dark:bg-gray-700 px-2 py-1 text-sm text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <EyeIcon class="h-4 w-4" />
                                </Link>
                                <Link :href="route('admin.products.edit', product.id)"
                                    class="rounded-md bg-white dark:bg-gray-700 px-2 py-1 text-sm text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <PencilIcon class="h-4 w-4" />
                                </Link>
                                <button @click="deleteProduct(product)"
                                    class="rounded-md bg-white dark:bg-gray-700 px-2 py-1 text-sm text-red-600 dark:text-red-400 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table View -->
                <div v-else class="bg-white dark:bg-gray-800 shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                        <!-- Table View continued -->
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col"
                                    class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-200 sm:pl-6">
                                    Product
                                </th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">
                                    Category
                                </th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">
                                    Price
                                </th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">
                                    Stock
                                </th>
                                <th scope="col"
                                    class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">
                                    Status
                                </th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="product in products.data" :key="product.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            <!-- Check if product has any images -->
                                            <img v-if="product.images.length > 0"
                                                :src="getImageUrl(product.images.find(img => img.is_primary)?.image || product.images[0]?.image)"
                                                class="h-10 w-10 rounded-full object-cover" :alt="product.name">
                                            <div v-else
                                                class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                <PhotoIcon class="h-6 w-6 text-gray-400" />
                                            </div>
                                        </div>

                                        <div class="ml-4">
                                            <div class="font-medium text-gray-900 dark:text-white">{{ product.name }}
                                            </div>
                                            <div class="text-gray-500 dark:text-gray-400">SKU: {{ product.sku }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ product.category.name }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-white">
                                    {{ formatPrice(product.selling_price) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ product.stocks.reduce((sum, stock) => sum + stock.quantity, 0) || 0 }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm">
                                    <span :class="[
                                        'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                        product.status
                                            ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100'
                                            : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-100'
                                    ]">
                                        {{ product.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td
                                    class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                                    <div class="flex justify-end space-x-2">
                                        <Link :href="route('admin.products.show', product.id)"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300">
                                        <EyeIcon class="h-5 w-5" />
                                        </Link>
                                        <Link :href="route('admin.products.edit', product.id)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        <PencilIcon class="h-5 w-5" />
                                        </Link>
                                        <button @click="deleteProduct(product)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="products.data.length === 0"
                    class="text-center bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-12">
                    <ShoppingBagIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No products</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Get started by creating a new product.
                    </p>
                    <div class="mt-6">
                        <Link :href="route('admin.products.create')"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                        <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                        Add Product
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    <Pagination :links="products.links" />
                </div>

                <!-- Confirm Delete Dialog -->
                <ConfirmDialog v-model:show="showConfirmDialog" title="Delete Product" :message="confirmMessage"
                    @confirm="confirmDelete" />
            </main>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    PhotoIcon,
    ShoppingBagIcon, // Changed from PackageIcon to ShoppingBagIcon
} from '@heroicons/vue/24/outline';

import SpeedDialButton from '@/Components/SpeedDialButton.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    products: Object,
    filters: Object,
    categories: Array,
    brands: Array,
});


// State
const search = ref(props.filters.search ?? '');
const filters = ref({
    category_id: props.filters.category_id ?? '',
    brand_id: props.filters.brand_id ?? '',
    status: props.filters.status ?? ''
});
const showConfirmDialog = ref(false);
const confirmMessage = ref('');
const productToDelete = ref(null);

const getImageUrl = (path) => {
    return `/storage/${path}`;
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
};

// Search and Filters
const debouncedSearch = debounce(() => {
    applyFilters({ search: search.value });
}, 300);

const filterChanged = () => {
    applyFilters(filters.value);
};

const applyFilters = (newFilters) => {
    router.get(route('admin.products.index'), {
        ...filters.value,
        ...newFilters
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

// Delete Product
const deleteProduct = (product) => {
    productToDelete.value = product;
    confirmMessage.value = `Are you sure you want to delete "${product.name}"? This action cannot be undone.`;
    showConfirmDialog.value = true;
};

const confirmDelete = () => {
    if (productToDelete.value) {
        router.delete(route('admin.products.destroy', productToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showConfirmDialog.value = false;
                productToDelete.value = null;
            }
        });
    }
};

// Watch for filter changes
watch(() => props.filters, (newFilters) => {
    search.value = newFilters.search ?? '';
    filters.value = {
        category_id: newFilters.category_id ?? '',
        brand_id: newFilters.brand_id ?? '',
        status: newFilters.status ?? ''
    };
}, { deep: true });


</script>
