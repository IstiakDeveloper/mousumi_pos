# PosProductGrid.vue
<template>
    <div class="flex-1 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div v-if="loading" class="flex items-center justify-center h-full">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
        </div>

        <div v-else class="grid grid-cols-3 md:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-2 p-2">
            <div v-for="product in products"
                :key="product.id"
                class="bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200"
                @click="$emit('add-to-cart', product)"
                :class="{ 'cursor-not-allowed opacity-75': product.stock <= 0 }"
            >
                <div class="p-2">
                    <!-- Image Container - Made Smaller -->
                    <div class="w-full aspect-square bg-gray-100 dark:bg-gray-600 rounded-lg mb-2 flex items-center justify-center overflow-hidden">
                        <img v-if="product.image"
                            :src="getImageUrl(product.image)"
                            :alt="product.name"
                            class="object-contain w-3/4 h-3/4 rounded"
                        />
                        <CubeIcon v-else class="w-8 h-8 text-gray-400" />
                    </div>

                    <!-- Product Info - More Compact -->
                    <div class="space-y-1">
                        <div class="text-xs font-medium text-gray-900 dark:text-gray-200 line-clamp-1" :title="product.name">
                            {{ product.name }}
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-sm font-bold text-gray-900 dark:text-gray-200">
                                ৳{{ formatNumber(product.selling_price) }}
                            </div>
                            <div class="text-[10px] px-1.5 py-0.5 rounded"
                                :class="[
                                    product.stock > 0
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-400'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-400'
                                ]"
                            >
                                {{ product.stock }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!loading && products.length === 0"
            class="flex flex-col items-center justify-center h-full text-gray-500 dark:text-gray-400">
            <CubeIcon class="w-12 h-12 mb-2" />
            <p class="text-sm">No products found</p>
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
