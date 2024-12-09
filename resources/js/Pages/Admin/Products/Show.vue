<template>
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                                {{ product.name }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                SKU: {{ product.sku }}
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <Link :href="route('admin.products.edit', product.id)"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600">
                                <PencilIcon class="h-5 w-5 mr-2" />
                                Edit
                            </Link>
                            <Link :href="route('admin.products.index')"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400">
                                Back
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <main class="py-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                        <!-- Product Images -->
                        <div class="px-4 py-5 sm:px-6">
                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                                <div v-for="image in product.images" :key="image.id"
                                    class="relative aspect-w-1 aspect-h-1 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
                                    <img :src="getImageUrl(image.image)" :alt="product.name"
                                        class="object-cover">
                                </div>
                            </div>
                        </div>

                        <!-- Product Details -->
                        <div class="border-t border-gray-200 dark:border-gray-700">
                            <dl>
                                <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                                        {{ product.category.name }}
                                    </dd>
                                </div>
                                <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50 dark:bg-gray-700">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Brand</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                                        {{ product.brand?.name || 'N/A' }}
                                    </dd>
                                </div>
                                <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Unit</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                                        {{ product.unit.name }} ({{ product.unit.short_name }})
                                    </dd>
                                </div>
                                <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50 dark:bg-gray-700">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Pricing</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                                        <div class="space-y-2">
                                            <div>Cost Price: {{ formatPrice(product.cost_price) }}</div>
                                            <div>Selling Price: {{ formatPrice(product.selling_price) }}</div>
                                        </div>
                                    </dd>
                                </div>
                                <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                                        {{ product.description || 'No description available' }}
                                    </dd>
                                </div>
                                <div class="px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 bg-gray-50 dark:bg-gray-700">
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Specifications</dt>
                                    <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:mt-0 sm:col-span-2">
                                        <dl v-if="Object.keys(product.specifications || {}).length">
                                            <div v-for="(value, key) in product.specifications" :key="key"
                                                class="grid grid-cols-2 gap-4">
                                                <dt class="font-medium">{{ key }}</dt>
                                                <dd>{{ value }}</dd>
                                            </div>
                                        </dl>
                                        <p v-else>No specifications available</p>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </AdminLayout>
 </template>

 <script setup>
 import { Link } from '@inertiajs/vue3';
 import AdminLayout from '@/Layouts/AdminLayout.vue';
 import { PencilIcon } from '@heroicons/vue/24/outline';

 const props = defineProps({
    product: Object
 });

 const formatPrice = (price) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(price);
 };

 const getImageUrl = (path) => {
    return `/storage/${path}`;
 };
 </script>
