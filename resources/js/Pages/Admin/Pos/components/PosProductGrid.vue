<template>
    <div class="flex-1 bg-gray-50 dark:bg-gray-800">
        <!-- Loading State -->
        <div v-if="loading"
             class="flex items-center justify-center h-full">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <!-- Product Grid -->
        <div v-else
             class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 p-4">
            <div v-for="product in products"
                :key="product.id"
                @click="product.stock > 0 ? $emit('add-to-cart', product) : showOutOfStockAlert(product)"
                :class="[
                    'group transform transition-all duration-200',
                    product.stock <= 0 ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer hover:scale-102'
                ]"
            >
                <div class="bg-white dark:bg-gray-700 rounded-xl shadow-sm overflow-hidden
                           group-hover:shadow-md transition-shadow duration-200">
                    <!-- Product Image -->
                    <div class="aspect-square bg-gray-100 dark:bg-gray-600 relative">
                        <img v-if="product.image"
                            :src="getImageUrl(product.image)"
                            :alt="product.name"
                            class="w-full h-full object-contain p-4"
                        />
                        <div v-else
                             class="w-full h-full flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>

                        <!-- Stock Badge -->
                        <div class="absolute top-2 right-2 px-2 py-1 rounded-full text-xs font-medium"
                             :class="[
                                 product.stock > 0
                                     ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400'
                                     : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400'
                             ]">
                            {{ product.stock }} in stock
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="p-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2 mb-2"
                            :title="product.name">
                            {{ product.name }}
                        </h3>

                        <div class="flex items-center justify-between">
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                ৳{{ formatNumber(product.selling_price) }}
                            </span>
                            <button v-if="product.stock > 0"
                                    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-600
                                           text-gray-600 dark:text-gray-300
                                           opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!loading && products.length === 0"
             class="flex flex-col items-center justify-center h-full text-gray-500 dark:text-gray-400 p-8">
            <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <p class="text-lg font-medium mb-2">No Products Found</p>
            <p class="text-sm text-center">Try adjusting your search or filter to find what you're looking for.</p>
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

const showOutOfStockAlert = (product) => {
    alert(`❌ ${product.name} is out of stock!`)
}

const getImageUrl = (path) => {
    return path ? `/storage/${path}` : null
}

const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}
</script>

<style scoped>
/* Optional: Add smooth hover effect */
.grid > div {
    @apply transition-all duration-200;
}
.grid > div:active {
    @apply transform scale-95;
}
</style>
