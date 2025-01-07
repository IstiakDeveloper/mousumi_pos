<template>
    <div class="text-center bg-white dark:bg-gray-800 rounded-lg shadow px-6 py-12">
        <ShoppingBagIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No products found</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Get started by creating a new product.
        </p>
        <div class="mt-6">
            <Link
                :href="route('admin.products.create')"
                class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:hover:bg-indigo-400"
            >
                <PlusIcon class="-ml-0.5 mr-1.5 h-5 w-5" />
                Add Product
            </Link>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { ShoppingBagIcon, PlusIcon } from '@heroicons/vue/24/outline';
</script>
