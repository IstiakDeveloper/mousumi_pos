<template>
    <div class="flex-1 overflow-auto bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div v-if="loading" class="flex items-center justify-center h-full">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-4">
            <div v-for="product in products"
                :key="product.id"
                class="bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                <div class="p-4">
                    <div class="w-full aspect-square bg-gray-100 dark:bg-gray-600 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                        <img v-if="product.image"
                            :src="getImageUrl(product.image)"
                            :alt="product.name"
                            class="object-cover w-4/5 h-4/5 rounded-lg">
                        <div v-else
                            class="flex items-center justify-center w-full h-full text-gray-400">
                            <CubeIcon class="w-12 h-12" />
                        </div>
                    </div>
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-200 mb-1">
                        {{ product.name }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                        SKU: {{ product.sku }}
                    </div>
                    <div class="flex justify-between items-center">
                        <div class="text-lg font-bold text-gray-900 dark:text-gray-200">
                            ৳{{ formatNumber(product.selling_price) }}
                        </div>
                        <div class="text-xs"
                            :class="[
                                product.stock > 0
                                    ? 'text-green-600 dark:text-green-400'
                                    : 'text-red-600 dark:text-red-400'
                            ]">
                            Stock: {{ product.stock }}
                        </div>
                    </div>
                    <button
                        @click="$emit('add-to-cart', product)"
                        :disabled="product.stock <= 0"
                        class="mt-2 w-full bg-blue-500 hover:bg-blue-600 disabled:bg-gray-400 text-white rounded-lg py-2 text-sm">
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>

        <div v-if="!loading && products.length === 0"
            class="flex flex-col items-center justify-center h-full text-gray-500 dark:text-gray-400">
            <CubeIcon class="w-16 h-16 mb-4" />
            <p>No products found</p>
        </div>
    </div>
</template>

<script setup>
import * as LucideIcons from 'lucide-vue-next'
const CubeIcon = LucideIcons.Cube

const props = defineProps({
    products: {
        type: Array,
        required: true
    },
    loading: {
        type: Boolean,
        default: false
    }
})

defineEmits(['add-to-cart'])

const getImageUrl = (path) => {
    return path ? `/storage/${path}` : null
}

const formatNumber = (value) => {
    return Number(value).toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}
</script>
