<template>
  <div>
    <button
      @click="generateBarcode"
      :disabled="loading"
      class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
    >
      <template v-if="loading">
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Generating...
      </template>
      <template v-else>
        Generate Barcode
      </template>
    </button>

    <div v-if="barcode" class="mt-4">
      <img :src="barcode.image_url" :alt="barcode.barcode" class="mb-2">
      <p class="text-sm text-gray-600">Barcode: {{ barcode.barcode }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  productId: {
    type: Number,
    required: true
  }
})

const loading = ref(false)
const barcode = ref(null)

const generateBarcode = async () => {
  loading.value = true
  try {
    const response = await axios.post(`/admin/products/${props.productId}/barcode`)
    barcode.value = response.data
  } catch (error) {
    console.error('Error generating barcode:', error)
  } finally {
    loading.value = false
  }
}
</script>
