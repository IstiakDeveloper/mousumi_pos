<!-- resources/js/Components/SpeedDialButton.vue -->
<template>
    <div class="relative">
        <div class="fixed bottom-8 right-8 z-50">
            <div class="group">
                <button @click="isOpen = !isOpen"
                    class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-white shadow-lg hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400 focus:outline-none transition-all duration-200"
                >
                    <PlusIcon class="h-6 w-6 transition-transform duration-200" :class="{ 'rotate-45': isOpen }" />
                </button>
                <div v-show="isOpen"
                    class="absolute bottom-full right-0 mb-4 flex flex-col items-end space-y-2"
                >
                    <!-- Add Product -->
                    <Link :href="route('admin.products.create')"
                        class="flex items-center rounded-lg bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-100 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                    >
                        <PlusIcon class="h-5 w-5 mr-2" />
                        Add Product
                    </Link>

                    <!-- Import Products -->
                    <button @click="importProducts"
                        class="flex w-full items-center rounded-lg bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-100 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                    >
                        <ArrowUpTrayIcon class="h-5 w-5 mr-2" />
                        Import Products
                    </button>

                    <!-- Export Products -->
                    <button @click="exportProducts"
                        class="flex w-full items-center rounded-lg bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-100 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                    >
                        <ArrowDownTrayIcon class="h-5 w-5 mr-2" />
                        Export Products
                    </button>

                    <!-- Print Barcodes -->
                    <button @click="printBarcodes"
                        class="flex w-full items-center rounded-lg bg-white dark:bg-gray-800 px-4 py-2 text-sm font-medium text-gray-900 dark:text-gray-100 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                    >
                        <QrCodeIcon class="h-5 w-5 mr-2" />
                        Print Barcodes
                    </button>
                </div>
            </div>
        </div>

        <!-- Click Away Listener -->
        <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-40"></div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    PlusIcon,
    ArrowUpTrayIcon,
    ArrowDownTrayIcon,
    QrCodeIcon
} from '@heroicons/vue/24/outline';

const isOpen = ref(false);

const importProducts = () => {
    // Implement import functionality
    isOpen.value = false;
};

const exportProducts = () => {
    // Implement export functionality
    window.location.href = route('admin.products.export');
    isOpen.value = false;
};

const printBarcodes = () => {
    // Implement barcode printing functionality
    isOpen.value = false;
};
</script>

<style scoped>
/* Optional: Add animations */
.group:hover .rotate-45 {
    transform: rotate(45deg);
}

/* Transition for menu items */
.absolute > * {
    transition: all 0.2s ease-in-out;
}

.absolute > *:hover {
    transform: translateX(-4px);
}
</style>
