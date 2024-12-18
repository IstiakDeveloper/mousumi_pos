# /resources/js/Pages/Products/BarcodePrint.vue
<template>
  <div class="p-4">
    <!-- Print Controls - Hidden during printing -->
    <div class="mb-4 print:hidden">
      <button
        @click="print"
        class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700"
      >
        Print Now
      </button>
      <Link
        :href="route('admin.products.index')"
        class="ml-2 text-gray-600 hover:text-gray-800"
      >
        Back to Products
      </Link>
    </div>

    <!-- Barcode Grid -->
    <div class="grid gap-[2mm]">
      <!-- Each row -->
      <template v-for="product in products" :key="product.id">
        <template v-for="copy in product.copies" :key="`${product.id}-${copy}`">
          <!-- Label Container - 45mm x 35mm -->
          <div class="label-container">
            <!-- Content wrapper -->
            <div class="label-content">
              <div class="product-name">{{ product.name }}</div>
              <img
                :src="product.image_url"
                :alt="product.barcode"
                class="barcode-image"
              >
              <div class="barcode-text">{{ product.barcode }}</div>
              <div class="price">৳{{ formatPrice(product.price) }}</div>
            </div>
          </div>
        </template>
      </template>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  products: {
    type: Array,
    required: true
  },
  paperSize: {
    type: String,
    default: 'A4'
  }
})

const formatPrice = (price) => {
  const numPrice = Number(price) || 0;
  return numPrice.toFixed(2);
}

const print = () => {
  window.print()
}
</script>

<style>
/* Label specific styles */
.label-container {
  width: 45mm;
  height: 35mm;
  padding: 1mm;
  overflow: hidden;
  box-sizing: border-box;
}

.label-content {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.product-name {
  font-size: 2.5mm;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 43mm;
}

.barcode-image {
  height: 15mm;
  width: auto;
  max-width: 43mm;
  object-fit: contain;
  margin: 1mm 0;
}

.barcode-text {
  font-size: 2.5mm;
  margin-bottom: 1mm;
}

.price {
  font-size: 3mm;
  font-weight: bold;
}

/* Print specific styles */
@media print {
  @page {
    size: A4;
    margin: 5mm;
  }

  body {
    margin: 0;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  .print\:hidden {
    display: none !important;
  }

  /* Grid layout for A4 paper */
  .grid {
    display: grid;
    grid-template-columns: repeat(4, 45mm); /* 4 labels per row for A4 */
    gap: 2mm;
    padding: 0;
  }

  /* Ensure each label maintains exact dimensions */
  .label-container {
    page-break-inside: avoid;
    break-inside: avoid;
  }
}
</style>
