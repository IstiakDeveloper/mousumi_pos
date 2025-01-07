<template>

    <Head title="Products" />
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header Section -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <!-- Stats -->
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
                                    {{ activeProducts }} Active
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                    <ExclamationTriangleIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-yellow-500" />
                                    {{ lowStockProducts }} Low Stock
                                </div>
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                                    <CurrencyBangladeshiIcon class="mr-1.5 h-5 w-5 flex-shrink-0 text-indigo-500" />
                                    {{ totalValue }}
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-shrink-0 gap-2">
                            <!-- View Toggle -->
                            <div class="flex rounded-md shadow-sm">
                                <button @click="viewMode = 'grid'" :class="viewToggleClass('grid')">
                                    <Squares2X2Icon class="h-5 w-5" />
                                </button>
                                <button @click="viewMode = 'table'" :class="viewToggleClass('table')">
                                    <ListBulletIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- Add Product Button -->
                            <Link :href="route('admin.products.create')"
                                class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                            <PlusIcon class="h-5 w-5" />
                            Add Product
                            </Link>

                            <!-- Print Barcodes -->
                            <button @click="printBarcodes"
                                class="inline-flex items-center gap-x-2 rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                <QrCodeIcon class="h-5 w-5 text-gray-400" />
                                Print Barcodes
                            </button>
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
                <!-- Product Grid/Table Views -->
                <div v-if="products.data.length > 0">
                    <ProductGrid v-if="viewMode === 'grid'" :products="products.data" @delete="deleteProduct" />
                    <ProductTable v-else :products="products.data" @delete="deleteProduct" />
                </div>

                <!-- Empty State -->
                <div v-else class="text-center bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-12">
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
# /resources/js/Pages/Admin/Products/Index.vue (Script Part)
<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link, Head } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import ProductGrid from './Partials/ProductGrid.vue';
import ProductTable from './Partials/ProductTable.vue';

// Import Icons
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    TrashIcon,
    EyeIcon,
    PhotoIcon,
    ShoppingBagIcon,
    CubeIcon,
    ShieldCheckIcon,
    ExclamationTriangleIcon,
    CurrencyBangladeshiIcon,
    QrCodeIcon,
    Squares2X2Icon,
    ListBulletIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    products: Object,
    filters: Object,
    categories: Array,
    brands: Array,
});

// State Management
const viewMode = ref(localStorage.getItem('productViewMode') || 'grid');
const search = ref(props.filters?.search || '');
const filters = ref({
    category_id: props.filters?.category_id || '',
    brand_id: props.filters?.brand_id || '',
    status: props.filters?.status || ''
});

const showConfirmDialog = ref(false);
const confirmMessage = ref('');
const productToDelete = ref(null);

// Computed Properties
const activeProducts = computed(() =>
    props.products.data.filter(p => p.status).length
);

const lowStockProducts = computed(() =>
    props.products.data.filter(p =>
        p.stocks.reduce((sum, stock) => sum + stock.quantity, 0) <= p.alert_quantity
    ).length
);

const totalValue = computed(() => {
    const total = props.products.data.reduce((sum, product) => {
        // Calculate total stock value for each product
        const stockValue = product.stocks.reduce((stockSum, stock) =>
            // Use stock's unit_cost instead of product's cost_price
            stockSum + (stock.quantity * stock.unit_cost), 0
        );
        return sum + stockValue;
    }, 0);

    // Format as BDT
    const number = new Intl.NumberFormat('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(total);
    return `৳ ${number}`;
});

// Methods
const viewToggleClass = (mode) => {
    const baseClass = 'relative inline-flex items-center px-3 py-2 text-sm font-medium ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 focus:z-10';
    const activeClass = 'bg-indigo-600 text-white dark:bg-indigo-500';
    const inactiveClass = 'bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400';
    const positionClass = mode === 'grid' ? 'rounded-l-lg' : 'rounded-r-lg -ml-px';

    return [baseClass, viewMode.value === mode ? activeClass : inactiveClass, positionClass].join(' ');
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

// Product Actions
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

const printBarcodes = () => {
    router.post('/admin/products/barcode/print', {
        products: props.products.data.map(product => ({
            id: product.id,
            copies: 1
        }))
    });
};

// Watchers
watch(() => props.filters, (newFilters) => {
    search.value = newFilters.search ?? '';
    filters.value = {
        category_id: newFilters.category_id ?? '',
        brand_id: newFilters.brand_id ?? '',
        status: newFilters.status ?? ''
    };
}, { deep: true });

watch(viewMode, (newMode) => {
    localStorage.setItem('productViewMode', newMode);
});

// Image Helper
const getImageUrl = (path) => {
    return path ? `/storage/${path}` : null;
};

// Price Formatter
const formatPrice = (price) => {
    return new Intl.NumberFormat('en-BD', {
        style: 'currency',
        currency: 'BDT',
        minimumFractionDigits: 2
    }).format(price || 0);
};

// Stock Helper
const getTotalStock = (product) => {
    return product.stocks?.reduce((sum, stock) => sum + stock.quantity, 0) || 0;
};

// Stock Status
const getStockStatus = (product) => {
    const total = getTotalStock(product);
    if (total <= 0) return { text: 'Out of Stock', class: 'text-red-600 bg-red-100' };
    if (total <= product.alert_quantity) return { text: 'Low Stock', class: 'text-yellow-600 bg-yellow-100' };
    return { text: 'In Stock', class: 'text-green-600 bg-green-100' };
};
</script>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

@media print {
    .no-print {
        display: none !important;
    }
}
</style>
