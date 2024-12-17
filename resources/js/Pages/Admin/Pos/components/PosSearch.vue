
<template>
    <div class="mb-4">
        <div class="flex space-x-4">
            <div class="flex-1 relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
                    placeholder="Search products by name, SKU, or scan barcode..."
                    @keyup="handleSearch"
                    ref="searchInput"
                />
                <div class="absolute right-3 top-3 text-gray-400">
                    <QrCodeIcon class="w-5 h-5" />
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="mt-4 flex space-x-2 overflow-x-auto pb-2">
            <button
                @click="$emit('filter', null)"
                :class="[
                    'px-4 py-2 rounded-lg whitespace-nowrap',
                    !selectedCategory
                        ? 'bg-blue-500 text-white'
                        : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300'
                ]">
                All Products
            </button>
            <button
                v-for="category in categories"
                :key="category.id"
                @click="$emit('filter', category.id)"
                :class="[
                    'px-4 py-2 rounded-lg whitespace-nowrap',
                    selectedCategory === category.id
                        ? 'bg-blue-500 text-white'
                        : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300'
                ]">
                {{ category.name }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { QrCode as QrCodeIcon } from 'lucide-vue-next'

const props = defineProps({
    categories: {
        type: Array,
        required: true
    },
    selectedCategory: {
        type: [Number, null],
        default: null
    }
})

const emit = defineEmits(['search', 'filter'])

const searchQuery = ref('')
const searchInput = ref(null)

const handleSearch = () => {
    emit('search', searchQuery.value)
}

// Handle barcode scanner
onMounted(() => {
    let barcodeBuffer = ''
    let lastKeyTime = 0
    const BARCODE_DELAY = 20

    document.addEventListener('keydown', (e) => {
        const currentTime = new Date().getTime()

        if (currentTime - lastKeyTime > BARCODE_DELAY) {
            barcodeBuffer = ''
        }

        if (e.key === 'Enter' && barcodeBuffer) {
            searchQuery.value = barcodeBuffer
            handleSearch()
            barcodeBuffer = ''
        } else {
            barcodeBuffer += e.key
        }

        lastKeyTime = currentTime
    })

    // Focus search input on mount
    searchInput.value?.focus()
})
</script>
