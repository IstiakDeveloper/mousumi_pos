<template>
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <div class="lg:flex lg:items-center lg:justify-between">
                        <div class="min-w-0 flex-1">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white sm:truncate">
                                Products
                            </h2>
                            <div class="mt-1 flex flex-col sm:mt-0 sm:flex-row sm:flex-wrap sm:space-x-6">
                                <div class="mt-2 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                    Total Products: {{ products.total }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 flex lg:ml-4 lg:mt-0 space-x-3">
                            <SpeedDialButton></SpeedDialButton>
                            <Link :href="route('admin.products.create')"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                <PlusIcon class="h-5 w-5 mr-2" />
                                Add Product
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                <!-- Filters Section -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                            </div>
                            <input v-model="search" type="text"
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Search products..." @input="debouncedSearch">
                        </div>

                        <!-- Category Filter -->
                        <select v-model="filters.category_id"
                            class="block w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            @change="filterChanged">
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>

                        <!-- Brand Filter -->
                        <select v-model="filters.brand_id"
                            class="block w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            @change="filterChanged">
                            <option value="">All Brands</option>
                            <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                {{ brand.name }}
                            </option>
                        </select>

                        <!-- Status Filter -->
                        <select v-model="filters.status"
                            class="block w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            @change="filterChanged">
                            <option value="">All Status</option>
                            <option :value="1">Active</option>
                            <option :value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div v-for="product in products.data" :key="product.id"
                        class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <!-- Product Image -->
                        <div class="relative aspect-w-3 aspect-h-2">
                            <img v-if="product.image" :src="getImageUrl(product.image)"
                                class="w-full h-48 object-cover object-center" :alt="product.name">
                            <div v-else
                                class="w-full h-48 bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                <PhotoIcon class="h-12 w-12 text-gray-400 dark:text-gray-500" />
                            </div>
                            <div class="absolute top-2 right-2">
                                <span :class="[
                                    'px-2 py-1 text-xs font-medium rounded-full',
                                    product.status
                                        ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                        : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
                                ]">
                                    {{ product.status ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>

                        <!-- Product Info -->
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                                {{ product.name }}
                            </h3>
                            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                SKU: {{ product.sku }}
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ formatPrice(product.selling_price) }}
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    Stock: {{ product.current_stock }}
                                </div>
                            </div>
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ product.category?.name }}
                                <span v-if="product.brand">
                                    • {{ product.brand.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div
                            class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex justify-end space-x-3">
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
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="products.data.length === 0"
                    class="bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-12 text-center">
                    <PackageIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No products</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Get started by creating a new product.
                    </p>
                    <div class="mt-6">
                        <Link :href="route('admin.products.create')"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            <PlusIcon class="h-5 w-5 mr-2" />
                            New Product
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    <Pagination :links="products.links" />
                </div>
            </main>

            <!-- Confirm Delete Dialog -->
            <ConfirmDialog v-model:show="showConfirmDialog" title="Delete Product"
                :message="confirmMessage" confirmText="Delete" cancelText="Cancel" @confirm="confirmDelete" />
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
    brands: Array
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

// Helpers
const getImageUrl = (path) => {
    if (!path) return null;
    return `${usePage().props.appUrl}/storage/${path}`;
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
