<template>
    <div class="bg-white p-8 max-w-md mx-auto print:p-0" ref="receiptRef">
      <!-- Company Info -->
      <div class="text-center mb-6">
        <h1 class="text-xl font-bold">Your Company Name</h1>
        <p class="text-gray-600">123 Business Street</p>
        <p class="text-gray-600">City, State, ZIP</p>
        <p class="text-gray-600">Phone: (123) 456-7890</p>
      </div>

      <!-- Receipt Details -->
      <div class="mb-6">
        <div class="flex justify-between mb-2">
          <span class="font-medium">Invoice No:</span>
          <span>{{ sale.invoice_no }}</span>
        </div>
        <div class="flex justify-between mb-2">
          <span class="font-medium">Date:</span>
          <span>{{ formatDate(sale.created_at) }}</span>
        </div>
        <div class="flex justify-between mb-2" v-if="sale.customer">
          <span class="font-medium">Customer:</span>
          <span>{{ sale.customer.name }}</span>
        </div>
      </div>

      <!-- Items -->
      <table class="w-full mb-6">
        <thead class="border-t border-b">
          <tr>
            <th class="py-2 text-left">Item</th>
            <th class="py-2 text-right">Qty</th>
            <th class="py-2 text-right">Price</th>
            <th class="py-2 text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in sale.sale_items" :key="item.id">
            <td class="py-2">{{ item.product.name }}</td>
            <td class="py-2 text-right">{{ item.quantity }}</td>
            <td class="py-2 text-right">${{ formatNumber(item.unit_price) }}</td>
            <td class="py-2 text-right">${{ formatNumber(item.subtotal) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Summary -->
      <div class="border-t pt-4">
        <div class="flex justify-between mb-2">
          <span>Subtotal</span>
          <span>${{ formatNumber(sale.subtotal) }}</span>
        </div>
        <div class="flex justify-between mb-2">
          <span>Tax</span>
          <span>${{ formatNumber(sale.tax) }}</span>
        </div>
        <div class="flex justify-between mb-2" v-if="sale.discount > 0">
          <span>Discount</span>
          <span>${{ formatNumber(sale.discount) }}</span>
        </div>
        <div class="flex justify-between font-bold text-lg border-t pt-2">
          <span>Total</span>
          <span>${{ formatNumber(sale.total) }}</span>
        </div>
      </div>

      <!-- Payment Info -->
      <div class="mt-6 border-t pt-4">
        <div class="flex justify-between mb-2">
          <span>Paid Amount</span>
          <span>${{ formatNumber(sale.paid) }}</span>
        </div>
        <div class="flex justify-between mb-2" v-if="sale.due > 0">
          <span>Due Amount</span>
          <span>${{ formatNumber(sale.due) }}</span>
        </div>
        <div class="flex justify-between">
          <span>Payment Method</span>
          <span>{{ formatPaymentMethod(sale.sale_payments[0]?.payment_method) }}</span>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-8 text-center text-gray-600">
        <p>Thank you for your business!</p>
        <p class="text-sm mt-2">Keep this receipt for your records</p>
      </div>
    </div>
  </template>

  <script setup>
  import { ref, onMounted } from 'vue'
  import { format } from 'date-fns'

  const props = defineProps({
    sale: {
      type: Object,
      required: true
    }
  })

  const receiptRef = ref(null)

  const formatDate = (date) => {
    return format(new Date(date), 'MMM dd, yyyy HH:mm')
  }

  const formatNumber = (number) => {
    return Number(number).toFixed(2)
  }

  const formatPaymentMethod = (method) => {
    const methods = {
      cash: 'Cash',
      card: 'Card',
      bank: 'Bank Transfer',
      mobile_banking: 'Mobile Banking'
    }
    return methods[method] || method
  }

  onMounted(() => {
    // Auto print when component is mounted
    if (props.autoPrint) {
      window.print()
    }
  })
  </script>

  <style scoped>
  @media print {
    @page {
      size: 80mm 297mm;
      margin: 0;
    }

    body {
      margin: 0;
      padding: 10mm;
    }

    .no-print {
      display: none;
    }
  }
  </style>
