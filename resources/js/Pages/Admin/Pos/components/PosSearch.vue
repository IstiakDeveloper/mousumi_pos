<template>
    <div class="space-y-4">
      <!-- Search Bar -->
      <div class="relative">
        <input v-model="searchQuery"
          type="text"
          class="w-full h-12 pl-12 pr-4 rounded-lg border-2 focus:border-blue-500 dark:bg-gray-700"
          placeholder="Search products or scan barcode..."
          @keyup="handleSearch"
          ref="searchInput" />
        <SearchIcon class="absolute left-4 top-4 text-gray-400" />
        <QrCodeIcon class="absolute right-4 top-4 text-gray-400" />
      </div>

      <!-- Categories -->
      <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-thin">
        <button @click="$emit('filter', null)"
          :class="[
            'px-4 py-2 rounded-lg whitespace-nowrap transition-colors',
            !selectedCategory ? 'btn-primary' : 'btn-secondary'
          ]">
          All Products
        </button>
        <button v-for="category in categories"
          :key="category.id"
          @click="$emit('filter', category.id)"
          :class="[
            'px-4 py-2 rounded-lg whitespace-nowrap transition-colors',
            selectedCategory === category.id ? 'btn-primary' : 'btn-secondary'
          ]">
          {{ category.name }}
        </button>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue'
  import { Search as SearchIcon, QrCode as QrCodeIcon } from 'lucide-vue-next'

  const props = defineProps({
    categories: Array,
    selectedCategory: Number
  })

  const emit = defineEmits(['search', 'filter'])
  const searchQuery = ref('')
  const searchInput = ref(null)

  const handleSearch = () => emit('search', searchQuery.value)

  onMounted(() => {
    // Barcode scanner handling
    let buffer = ''
    let lastKeyTime = 0

    document.addEventListener('keydown', (e) => {
      const currentTime = new Date().getTime()

      if (currentTime - lastKeyTime > 20) {
        buffer = ''
      }

      if (e.key === 'Enter' && buffer) {
        searchQuery.value = buffer
        handleSearch()
        buffer = ''
      } else {
        buffer += e.key
      }

      lastKeyTime = currentTime
    })

    // Focus search on mount
    searchInput.value?.focus()
  })
  </script>

  <style>
  .btn-primary {
    @apply bg-blue-500 text-white hover:bg-blue-600 disabled:bg-gray-400;
  }

  .btn-secondary {
    @apply bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600;
  }
  </style>
