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
/* Wrapper to enforce landscape orientation for 1.5x1 inch label (2x size) */
.label-wrapper {
  width: 76.2mm;  /* 3 inches (2x 1.5 inches) */
  height: 50.8mm; /* 2 inches (2x 1 inch) */
  page-break-inside: avoid;
  page-break-after: always;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Label container with exact dimensions */
.label-container {
  width: 76.2mm;
  height: 50.8mm;
  box-sizing: border-box;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2mm;
}

.label-content {
  width: 72.2mm;
  height: 46.8mm;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: center;
}

.product-name {
  font-size: 4mm;  /* 2x original size */
  font-weight: 600;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 72.2mm;
  line-height: 1.2;
  margin-bottom: 1mm;
}

.barcode-image {
  width: 56mm;   /* 2x original width */
  height: 24mm;  /* 2x original height */
  object-fit: contain;
  margin: 1mm 0;
}

.barcode-number {
  font-size: 4mm;  /* 2x original size */
  line-height: 1.2;
  margin: 1mm 0;
}

.price {
  font-size: 5mm;  /* 2x original size */
  font-weight: bold;
  line-height: 1.2;
  margin-top: 1mm;
}

/* Print Settings */
@page {
  size: 76.2mm 50.8mm;
  margin: 0;
}

@media print {
  @page {
    size: 76.2mm 50.8mm;
    margin: 0;
  }

  html, body {
    margin: 0 !important;
    padding: 0 !important;
    width: 76.2mm;
    height: 50.8mm;
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
    width: 76.2mm;
    height: 50.8mm;
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
