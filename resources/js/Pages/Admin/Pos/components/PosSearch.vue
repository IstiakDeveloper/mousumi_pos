<template>
    <div class="mb-4">
        <div class="flex space-x-4">
            <div class="flex-1 relative">
                <input v-model="searchQuery"
                    type="text"
                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
                    placeholder="Search products by name, SKU, or scan barcode..."
                    @input="handleManualSearch"
                    @keydown.enter.prevent="handleEnter"
                    ref="searchInput" />
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
            <button v-for="category in categories"
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
import { ref, onMounted, onUnmounted } from 'vue'
import { QrCode as QrCodeIcon } from 'lucide-vue-next'
import axios from 'axios'
import { debounce } from 'lodash'

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
const lastScanTime = ref(0)
const SCAN_COOLDOWN = 1000 // 1 second cooldown between scans

// Debounced search function for manual typing
const debouncedSearch = debounce((query) => {
    emit('search', query)
}, 300)

const handleManualSearch = () => {
    if (!isScanning.value) {
        debouncedSearch(searchQuery.value)
    }
}

const searchByBarcode = async (barcode) => {
    const currentTime = Date.now()

    // Check if enough time has passed since the last scan
    if (currentTime - lastScanTime.value < SCAN_COOLDOWN) {
        console.log('Scanning too fast, please wait')
        return
    }

    try {
        const response = await axios.get(route('admin.pos.search-by-barcode'), {
            params: { barcode }
        })

        if (response.data && response.data.length > 0) {
            // Update last scan time
            lastScanTime.value = currentTime

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
        new Audio('/sounds/error.mp3').play()
    }
}

const handleEnter = () => {
    if (searchQuery.value.length >= 6) { // Minimum barcode length check
        searchByBarcode(searchQuery.value)
    }
}

// Handle barcode scanner
onMounted(() => {
    let barcodeBuffer = ''
    let lastKeyTime = 0
    const BARCODE_DELAY = 20 // Time between keystrokes for barcode scanner

    const handleKeyDown = (e) => {
        // Ignore if user is typing in a textarea or number input
        if (
            e.target.tagName === 'TEXTAREA' ||
            (e.target.tagName === 'INPUT' && e.target.type === 'number') ||
            (e.target === searchInput.value && !isScanning.value)
        ) {
            return
        }

        const currentTime = new Date().getTime()

        // If delay between keystrokes is longer than BARCODE_DELAY,
        // assume it's manual typing
        if (currentTime - lastKeyTime > BARCODE_DELAY) {
            barcodeBuffer = ''
            isScanning.value = false
        }

        // If it's scanning speed, mark as scanning
        if (currentTime - lastKeyTime <= BARCODE_DELAY) {
            isScanning.value = true
        }

        // Handle Enter key from scanner
        if (e.key === 'Enter' && barcodeBuffer) {
            e.preventDefault()
            searchByBarcode(barcodeBuffer)
            barcodeBuffer = ''
            isScanning.value = false
        } else if (e.key.length === 1) { // Only add single characters to buffer
            barcodeBuffer += e.key
        }

        lastKeyTime = currentTime
    }

    document.addEventListener('keydown', handleKeyDown)

    // Cleanup
    onUnmounted(() => {
        document.removeEventListener('keydown', handleKeyDown)
        debouncedSearch.cancel()
    })

    // Initially focus search input
    searchInput.value?.focus()
})
</script>
