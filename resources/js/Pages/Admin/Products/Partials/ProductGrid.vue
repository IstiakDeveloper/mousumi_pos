# ProductGrid.vue
<template>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <div v-for="product in products" :key="product.id"
            class="bg-white dark:bg-gray-800 overflow-hidden rounded-lg shadow-sm hover:shadow-md transition-shadow">
            <!-- Product Image -->
            <div class="aspect-w-16 aspect-h-9 bg-gray-200 dark:bg-gray-700">
                <img v-if="getProductImage(product)" :src="getImageUrl(getProductImage(product))" :alt="product.name"
                    class="object-cover w-full h-48">
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
                        getStockStatusClass(product.stock_status)
                    ]">
                        {{ getStockStatusText(product.stock_status) }}
                    </span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Selling Price</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatPrice(product.selling_price) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Available Stock</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ formatNumber(product.available_quantity) }}

                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stock Value</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ formatPrice(product.current_stock_value) }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Avg Cost: {{ formatPrice(product.cost_price) }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="mt-4 flex justify-end space-x-2">
                    <Link :href="route('admin.products.show', product.id)"
                        class="inline-flex items-center p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <EyeIcon class="h-5 w-5" />
                    </Link>
                    <Link :href="route('admin.products.edit', product.id)"
                        class="inline-flex items-center p-2 text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-200">
                    <PencilIcon class="h-5 w-5" />
                    </Link>
                    <button @click="$emit('delete', product)"
                        class="inline-flex items-center p-2 text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200">
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

const getProductImage = (product) => {
    if (!product.images?.length) return null;
    // First try to find primary image
    const primaryImage = product.images.find(img => img.is_primary);
    // If no primary image, use the first image
    return primaryImage ? primaryImage.image : product.images[0].image;
};

const getImageUrl = (path) => {
    if (!path) return null;
    return `/storage/${path}`;
};

const formatPrice = (price) => {
    const number = new Intl.NumberFormat('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price || 0);
    return `৳ ${number}`;
};

// Add this helper function in script setup
const formatNumber = (number) => {
    // Check if the number has decimal points
    if (Number.isInteger(Number(number))) {
        return Math.round(number); // Return without decimals
    }
    // For decimal numbers, show 2 decimal places
    return Number(number).toFixed(2);
};

const getStockStatusText = (status) => {
    switch (status) {
        case 'out': return 'Out of Stock';
        case 'low': return 'Low Stock';
        default: return 'In Stock';
    }
};

const getStockStatusClass = (status) => {
    switch (status) {
        case 'out':
            return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
        case 'low':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
        default:
            return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
    }
};
</script>

<style scoped>
.aspect-w-16 {
    position: relative;
    padding-bottom: 56.25%;
}

.aspect-w-16>img,
.aspect-w-16>div {
    position: absolute;
    height: 100%;
    width: 100%;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
}
</style>
