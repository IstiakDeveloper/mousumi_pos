<template>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div v-for="product in products" :key="product.id"
            class="bg-white dark:bg-gray-800 overflow-hidden rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <!-- Product Image -->
            <div class="aspect-w-16 aspect-h-9 bg-gray-200 dark:bg-gray-700">
                <img v-if="getProductImage(product)"
                    :src="getImageUrl(getProductImage(product))"
                    :alt="product.name"
                    class="object-cover w-full h-48"
                >
                <div v-else class="flex items-center justify-center h-48">
                    <PhotoIcon class="h-12 w-12 text-gray-400" />
                </div>
            </div>

            <!-- Product Info -->
            <div class="p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate">
                            {{ product.name }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            SKU: {{ product.sku }}
                        </p>
                    </div>
                    <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        getStockStatus(product).class
                    ]">
                        {{ getStockStatus(product).text }}
                    </span>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{ formatPrice(product.selling_price) }}
                        </p>
                        <p v-if="product.cost_price" class="text-sm text-gray-500 dark:text-gray-400">
                            Cost: {{ formatPrice(product.cost_price) }}
                        </p>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Stock: {{ getTotalStock(product) }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="mt-4 flex justify-end space-x-2">
                    <Link :href="route('admin.products.show', product.id)"
                        class="inline-flex items-center p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <EyeIcon class="h-5 w-5" />
                    </Link>
                    <Link :href="route('admin.products.edit', product.id)"
                        class="inline-flex items-center p-2 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-200"
                    >
                        <PencilIcon class="h-5 w-5" />
                    </Link>
                    <button @click="$emit('delete', product)"
                        class="inline-flex items-center p-2 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200"
                    >
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { EyeIcon, PencilIcon, TrashIcon, PhotoIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    products: {
        type: Array,
        required: true
    }
});

defineEmits(['delete']);

// Helpers
const getProductImage = (product) => {
    if (!product.images?.length) return null;
    const primaryImage = product.images.find(img => img.is_primary);
    return primaryImage ? primaryImage.image : product.images[0].image;
};

window.appUrl = "{{ config('app.url') }}";

const getImageUrl = (path) => `${window.appUrl}/storage/${path}`;

const formatPrice = (price) => {
    const number = new Intl.NumberFormat('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price || 0);
    return `৳ ${number}`
};

const getTotalStock = (product) => {
    return product.stocks?.reduce((sum, stock) => sum + stock.quantity, 0) || 0;
};

const getStockStatus = (product) => {
    const total = getTotalStock(product);
    if (total <= 0) {
        return {
            text: 'Out of Stock',
            class: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
        };
    }
    if (total <= product.alert_quantity) {
        return {
            text: 'Low Stock',
            class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'
        };
    }
    return {
        text: 'In Stock',
        class: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
    };
};
</script>
