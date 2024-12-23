<template>
    <div class="p-4">
      <!-- Print Controls -->
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
      <div class="barcode-grid">
        <template v-for="product in products" :key="product.id">
          <template v-for="copy in product.copies" :key="`${product.id}-${copy}`">
            <!-- Single Label -->
            <div class="label-wrapper">
              <div class="label-container">
                <div class="label-content">
                  <div class="product-name">{{ truncate(product.name, 20) }}</div>
                  <img
                    :src="product.image_url"
                    :alt="product.barcode"
                    class="barcode-image"
                  >
                  <div class="barcode-number">{{ product.barcode }}</div>
                  <div class="price">{{ formatPrice(product.price) }} BDT</div>
                </div>
              </div>
            </div>
          </template>
        </template>
      </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { onMounted } from 'vue'

const props = defineProps({
  products: {
    type: Array,
    required: true
  }
})

onMounted(() => {
  // Add meta tags to control printing
  const meta = document.createElement('meta')
  meta.name = 'viewport'
  meta.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'
  document.head.appendChild(meta)
})

const formatPrice = (price) => {
  const numPrice = Number(price) || 0;
  return numPrice.toFixed(2);
}

const truncate = (str, length) => {
  if (str.length <= length) return str;
  return str.substring(0, length) + '...';
}

const print = () => {
  window.print();
}
</script>

<style>
/* Wrapper to enforce landscape orientation for 1.5x1 inch label */
.label-wrapper {
  width: 38.1mm;  /* 1.5 inches */
  height: 25.4mm; /* 1 inch */
  page-break-inside: avoid;
  page-break-after: always;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Label container with exact dimensions */
.label-container {
  width: 38.1mm;
  height: 25.4mm;
  box-sizing: border-box;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1mm;
}

.label-content {
  width: 36.1mm;
  height: 23.4mm;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
}

.product-name {
  font-size: 2mm;
  font-weight: 600;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 36.1mm;
  line-height: 1;
  margin-bottom: 0.5mm;
}

.barcode-image {
  width: 28mm;   /* Exact width to match generated barcode */
  height: 12mm;  /* Exact height to match generated barcode */
  object-fit: contain;
  margin: 0.5mm 0;
}

.barcode-number {
  font-size: 2mm;
  line-height: 1;
  margin: 0.5mm 0;
}

.price {
  font-size: 2.5mm;
  font-weight: bold;
  line-height: 1;
  margin-top: 0.5mm;
}

/* Print Settings */
@page {
  size: 38.1mm 25.4mm;
  margin: 0;
}

@media print {
  @page {
    size: 38.1mm 25.4mm;
    margin: 0;
  }

  html, body {
    margin: 0 !important;
    padding: 0 !important;
    width: 38.1mm;
    height: 25.4mm;
  }

  body {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .print\:hidden {
    display: none !important;
  }

  .barcode-grid {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 38.1mm;
    height: 25.4mm;
    margin: 0;
    padding: 0;
  }

  /* Reset visibility */
  body * {
    visibility: hidden;
  }

  .label-container,
  .label-container * {
    visibility: visible;
  }

  .label-container {
    position: fixed;
    left: 0;
    top: 0;
    margin: 0;
    transform-origin: center;
  }
}
</style>
