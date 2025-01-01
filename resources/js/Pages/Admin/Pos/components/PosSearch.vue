<template>
    <div class="mb-4">
        <div class="mb-2 flex space-x-2">
            <button @click="testScan('371760')" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded">
                Test Scan 1
            </button>
            <button @click="testScan('9876543210')" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded">
                Test Scan 2
            </button>
        </div>


        <div class="flex space-x-4">
            <div class="flex-1 relative">
                <input v-model="searchQuery" type="text"
                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
                    placeholder="Search products by name, SKU, or scan barcode..." @keyup="handleSearch"
                    @keydown.enter.prevent="handleEnter" ref="searchInput" />
                <div class="absolute right-3 top-3 text-gray-400">
                    <QrCodeIcon class="w-5 h-5" />
                </div>
            </div>
        </div>

        <!-- Categories -->
        <div class="mt-4 flex space-x-2 overflow-x-auto pb-2">
            <button @click="$emit('filter', null)" :class="[
                'px-4 py-2 rounded-lg whitespace-nowrap',
                !selectedCategory
                    ? 'bg-blue-500 text-white'
                    : 'bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300'
            ]">
                All Products
            </button>
            <button v-for="category in categories" :key="category.id" @click="$emit('filter', category.id)" :class="[
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
import { ref, onMounted, onUnmounted } from 'vue'
import { QrCode as QrCodeIcon } from 'lucide-vue-next'
import axios from 'axios'

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

const emit = defineEmits(['search', 'filter', 'add-scanned-product'])

const searchQuery = ref('')
const searchInput = ref(null)
const isScanning = ref(false)

const handleSearch = () => {
    if (!isScanning.value) {
        emit('search', searchQuery.value)
    }
}
const testScan = async (barcode) => {
    searchQuery.value = barcode;
    await searchByBarcode(barcode);
};

const searchByBarcode = async (barcode) => {
    try {
        const response = await axios.get(route('admin.pos.search-by-barcode'), {
            params: { barcode }
        })

        if (response.data && response.data.length > 0) {
            // Found product, emit to add to cart
            emit('add-scanned-product', response.data[0])
            // Clear search after successful scan
            searchQuery.value = ''
            // Play success sound
            new Audio('/sounds/beep.mp3').play()
        } else {
            // Product not found
            new Audio('/sounds/error.mp3').play()
        }
    } catch (error) {
        console.error('Error searching by barcode:', error)
    }
}

const handleEnter = () => {
    if (searchQuery.value.length >= 6) { // Assuming minimum barcode length is 8
        searchByBarcode(searchQuery.value)
    }
}

// Handle barcode scanner
onMounted(() => {
    let barcodeBuffer = ''
    let lastKeyTime = 0
    const BARCODE_DELAY = 20

    document.addEventListener('keydown', (e) => {
        // Ignore if user is typing in a text area or number input
        if (
            e.target.tagName === 'TEXTAREA' ||
            (e.target.tagName === 'INPUT' && e.target.type === 'number')
        ) {
            return
        }

        const currentTime = new Date().getTime()

        // If the delay between keystrokes is longer than BARCODE_DELAY,
        // assume it's manual typing and focus the search input
        if (currentTime - lastKeyTime > BARCODE_DELAY) {
            barcodeBuffer = ''
            isScanning.value = true

            // If it's a regular keystroke (not fast like scanner),
            // focus the search input and let normal typing handle it
            if (!isScanning.value) {
                searchInput.value?.focus()
                return
            }
        }

        // Handle Enter key from scanner
        if (e.key === 'Enter' && barcodeBuffer) {
            e.preventDefault()
            searchQuery.value = barcodeBuffer
            searchByBarcode(barcodeBuffer)
            barcodeBuffer = ''
            isScanning.value = false
        } else {
            // Only add to buffer if it's a valid character
            if (e.key.length === 1) { // Only single characters
                barcodeBuffer += e.key
            }
        }

        lastKeyTime = currentTime
    })

    // Add cleanup
    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyDown)
    })

    // Initially focus search input
    searchInput.value?.focus()
})
</script>
