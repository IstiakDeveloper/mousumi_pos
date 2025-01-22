<template>
    <div class="p-6">
      <div class="mb-6">
        <h2 class="text-2xl font-bold">Product Report</h2>
        <button @click="printReport" class="bg-blue-500 text-white px-4 py-2 rounded">
          Print Report
        </button>
      </div>

      <div id="printSection">
        <div class="print-header">
          <h2 class="text-xl font-bold">Product Report</h2>
          <p>Date: {{ currentDate }}</p>
        </div>

        <table class="min-w-full bg-white border border-gray-300">
          <thead>
            <tr>
              <th class="border p-2">SL No.</th>
              <th class="border p-2">Name</th>
              <th class="border p-2">SKU</th>
              <th class="border p-2">Category</th>
              <th class="border p-2">Cost Price</th>
              <th class="border p-2">Selling Price</th>
              <th class="border p-2">Stock</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(product, index) in products" :key="product.sku">
              <td class="border p-2">{{ index + 1 }}</td>
              <td class="border p-2">{{ product.name }}</td>
              <td class="border p-2">{{ product.sku }}</td>
              <td class="border p-2">{{ product.category }}</td>
              <td class="border p-2">{{ product.cost_price }}</td>
              <td class="border p-2">{{ product.selling_price }}</td>
              <td class="border p-2">{{ product.stock }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </template>

  <script setup>
  import { computed } from 'vue'

  // Get props from Inertia
  const props = defineProps({
    products: {
      type: Array,
      required: true
    }
  })

  const currentDate = computed(() => new Date().toLocaleDateString())

  const printReport = () => {
    const printContents = document.getElementById('printSection').innerHTML
    const originalContents = document.body.innerHTML

    document.body.innerHTML = `
      <html>
        <head>
          <title>Product Report</title>
          <style>
            table {
              width: 100%;
              border-collapse: collapse;
              margin-top: 20px;
            }
            th, td {
              border: 1px solid #ddd;
              padding: 8px;
              text-align: left;
            }
            th {
              background-color: #f5f5f5;
            }
            @media print {
              table { page-break-inside: auto; }
              tr { page-break-inside: avoid; page-break-after: auto; }
              .print-header { margin-bottom: 20px; }
            }
          </style>
        </head>
        <body>
          ${printContents}
        </body>
      </html>
    `

    window.print()
    document.body.innerHTML = originalContents
  }
  </script>

  <style scoped>
  @media print {
    button {
      display: none;
    }
  }

  .print-header {
    margin-bottom: 20px;
  }

  /* Hide header and navigation during print */
  @media print {
    header,
    nav,
    .no-print {
      display: none;
    }
  }
  </style>
