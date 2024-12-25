<template>
    <Head title="Product" />
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header Section -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                                {{ product.name }}
                            </h2>
                            <div class="mt-1 flex items-center space-x-4">
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    SKU: {{ product.sku }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400" v-if="product.barcode">
                                    Barcode: {{ product.barcode }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <BarcodeGenerator :product-id="product.id" />
                            <BarcodePrintSelection :products="[product]" />
                            <Link
                                :href="route('admin.products.edit', product.id)"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600"
                            >
                                <PencilIcon class="h-5 w-5 mr-2" />
                                Edit
                            </Link>
                            <Link
                                :href="route('admin.products.index')"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                            >
                                Back
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="py-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <!-- Left Column: Images and Quick Info -->
                        <div class="lg:col-span-2 space-y-6">
                            <!-- Product Images -->
                            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Product Images</h3>
                                </div>
                                <div class="px-4 py-5 sm:p-6">
                                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                                        <div v-for="image in product.images" :key="image.id"
                                            class="relative aspect-w-1 aspect-h-1 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 group">
                                            <img :src="getImageUrl(image.image)" :alt="product.name"
                                                class="object-cover group-hover:opacity-75">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Info -->
                            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Quick Information</h3>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700">
                                    <dl>
                                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                                                {{ product.category.name }}
                                            </dd>
                                        </div>
                                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50 dark:bg-gray-700">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Brand</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                                                {{ product.brand?.name || 'N/A' }}
                                            </dd>
                                        </div>
                                        <div class="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Unit</dt>
                                            <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                                                {{ product.unit.name }} ({{ product.unit.short_name }})
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Pricing, Stock, and Details -->
                        <div class="space-y-6">
                            <!-- Pricing Card -->
                            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pricing Information</h3>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
                                    <div class="space-y-4">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Cost Price</span>
                                            <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ formatPrice(product.cost_price) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Selling Price</span>
                                            <span class="text-lg font-semibold text-green-600 dark:text-green-400">
                                                {{ formatPrice(product.selling_price) }}
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center pt-4 border-t dark:border-gray-700">
                                            <span class="text-sm text-gray-500 dark:text-gray-400">Profit Margin</span>
                                            <span class="text-lg font-semibold" :class="marginColor">
                                                {{ calculateMargin }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Stock Alert -->
                            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Stock Information</h3>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Alert Quantity</span>
                                        <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ product.alert_quantity }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Specifications -->
                            <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                                <div class="px-4 py-5 sm:px-6">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Specifications</h3>
                                </div>
                                <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
                                    <dl v-if="Object.keys(product.specifications || {}).length">
                                        <div v-for="(value, key) in product.specifications" :key="key"
                                            class="grid grid-cols-2 gap-4 py-2">
                                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ key }}</dt>
                                            <dd class="text-sm text-gray-900 dark:text-white">{{ value }}</dd>
                                        </div>
                                    </dl>
                                    <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                                        No specifications available
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description Section -->
                    <div class="mt-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg overflow-hidden">
                        <div class="px-4 py-5 sm:px-6">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Product Description</h3>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-5 sm:p-6">
                            <p class="text-sm text-gray-900 dark:text-white whitespace-pre-line">
                                {{ product.description || 'No description available' }}
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { PencilIcon } from '@heroicons/vue/24/outline';
import BarcodeGenerator from '@/Components/BarcodeGenerator.vue';
import BarcodePrintSelection from '@/Components/BarcodePrintSelection.vue';
import { computed } from 'vue';

const props = defineProps({
    product: {
        type: Object,
        required: true
    }
});

const formatPrice = (price) => {
    const number = new Intl.NumberFormat('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price || 0);
    return `৳ ${number}`
};

const getImageUrl = (path) => {
    return `/storage/${path}`;
};

const calculateMargin = computed(() => {
    if (!props.product.cost_price || !props.product.selling_price) return 0;
    const margin = ((props.product.selling_price - props.product.cost_price) / props.product.selling_price) * 100;
    return margin.toFixed(2);
});

const marginColor = computed(() => {
    const margin = Number(calculateMargin.value);
    if (margin >= 30) return 'text-green-600 dark:text-green-400';
    if (margin >= 15) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
});
</script>
