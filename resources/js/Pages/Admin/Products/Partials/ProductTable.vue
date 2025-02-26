# ProductTable.vue
<template>
    <div class="bg-white dark:bg-gray-800 shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
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
                        Value
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
                <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                        <div class="flex items-center">
                            <div class="h-10 w-10 flex-shrink-0">
                                <img v-if="getProductImage(product)" :src="getImageUrl(getProductImage(product))"
                                    :alt="product.name" class="h-10 w-10 rounded-full object-cover">
                                <div v-else
                                    class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                    <PhotoIcon class="h-6 w-6 text-gray-400" />
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="font-medium text-gray-900 dark:text-white">{{ product.name }}</div>
                                <div class="text-gray-500 dark:text-gray-400">SKU: {{ product.sku }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-400">
                        {{ product.category?.name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <div class="text-gray-900 dark:text-white">{{ formatPrice(product.selling_price) }}</div>
                        <div class="text-gray-500 dark:text-gray-400">Cost: {{ formatPrice(product.cost_price) }}</div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <div class="text-gray-900 dark:text-white">
                            {{ formatNumber(product.available_quantity) }}
                        </div>
                        <span :class="[
                            'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                            getStockStatusClass(product.stock_status)
                        ]">
                            {{ getStockStatusText(product.stock_status) }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <div class="text-gray-900 dark:text-white">
                            {{ formatPrice(product.current_stock_value) }}
                        </div>
                        <div class="text-gray-500 dark:text-gray-400 text-xs">
                            Avg: {{ formatPrice(product.cost_price) }}
                        </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <span :class="[
                            'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                            product.status
                                ? 'bg-green-100 text-green-700 dark:bg-green-800 dark:text-green-100'
                                : 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-100'
                        ]">
                            {{ product.status ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <div class="flex justify-end space-x-2">
                            <Link v-if="user.role.name.toLowerCase() === 'admin'" :href="route('admin.products.show', product.id)"
                                class="text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                            <EyeIcon class="h-5 w-5" />
                            </Link>
                            <Link v-if="user.role.name.toLowerCase() === 'admin'" :href="route('admin.products.edit', product.id)"
                                class="text-blue-400 hover:text-blue-900 dark:hover:text-blue-100">
                            <PencilIcon class="h-5 w-5" />
                            </Link>
                            <button v-if="user.role.name.toLowerCase() === 'admin'" @click="$emit('delete', product)"
                                class="text-red-400 hover:text-red-900 dark:hover:text-red-100">
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { EyeIcon, PencilIcon, TrashIcon, PhotoIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    products: {
        type: Array,
        required: true
    }
});

const page = usePage();
const user = page.props.auth.user;

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
