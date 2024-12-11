<template>
    <AdminLayout title="Point of Sale">
      <div class="flex h-[calc(100vh-64px)]">
        <!-- Left Side - Product Search & Cart -->
        <div class="w-2/3 flex flex-col p-4 border-r">
          <!-- Search Bar -->
          <div class="mb-4">
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="Search products by name, SKU, or scan barcode..."
                @keyup="searchProducts"
                ref="searchInput"
              />
              <div class="absolute right-3 top-2.5 text-gray-400">
                <BarcodeIcon class="w-5 h-5" />
              </div>
            </div>

            <!-- Search Results -->
            <div v-if="showSearchResults" class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg">
              <ul class="py-1">
                <li
                  v-for="product in searchResults"
                  :key="product.id"
                  class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                  @click="addToCart(product)"
                >
                  <div class="flex justify-between">
                    <div>
                      <span class="font-medium">{{ product.name }}</span>
                      <span class="text-sm text-gray-500 ml-2">({{ product.sku }})</span>
                    </div>
                    <div class="text-right">
                      <span class="font-medium">${{ product.selling_price }}</span>
                      <span class="text-sm text-gray-500 ml-2">Stock: {{ product.stock }}</span>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <!-- Cart Items -->
          <div class="flex-1 overflow-auto">
            <table class="min-w-full bg-white">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(item, index) in cartItems" :key="index">
                  <td class="px-6 py-4">
                    <div class="text-sm font-medium text-gray-900">{{ item.name }}</div>
                    <div class="text-sm text-gray-500">{{ item.sku }}</div>
                  </td>
                  <td class="px-6 py-4 text-right text-sm">
                    ${{ item.unit_price.toFixed(2) }}
                  </td>
                  <td class="px-6 py-4 text-right">
                    <input
                      type="number"
                      v-model.number="item.quantity"
                      class="w-20 text-right border rounded"
                      min="1"
                      @change="updateCart"
                    />
                  </td>
                  <td class="px-6 py-4 text-right text-sm font-medium">
                    ${{ (item.quantity * item.unit_price).toFixed(2) }}
                  </td>
                  <td class="px-6 py-4 text-right">
                    <button @click="removeItem(index)" class="text-red-600 hover:text-red-900">
                      <TrashIcon class="w-5 h-5" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Right Side - Payment Details -->
        <div class="w-1/3 flex flex-col p-4 bg-gray-50">
          <!-- Customer Selection -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
            <select
              v-model="selectedCustomer"
              class="w-full border rounded-lg p-2"
            >
              <option :value="null">Walk-in Customer</option>
              <option
                v-for="customer in customers"
                :key="customer.id"
                :value="customer.id"
              >
                {{ customer.name }} ({{ customer.phone }})
              </option>
            </select>
          </div>

          <!-- Cart Summary -->
          <div class="bg-white rounded-lg p-4 mb-4">
            <div class="flex justify-between mb-2">
              <span class="text-gray-600">Subtotal</span>
              <span class="font-medium">${{ cartSummary.subtotal.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="text-gray-600">Tax ({{ taxRate }}%)</span>
              <span class="font-medium">${{ cartSummary.tax.toFixed(2) }}</span>
            </div>
            <div class="flex justify-between mb-2">
              <span class="text-gray-600">Discount</span>
              <div class="flex items-center">
                <input
                  type="number"
                  v-model.number="discount"
                  class="w-20 text-right border rounded mr-2"
                  min="0"
                  step="0.01"
                  @input="updateCart"
                />
              </div>
            </div>
            <div class="flex justify-between text-lg font-bold mt-4 pt-4 border-t">
              <span>Total</span>
              <span>${{ cartSummary.total.toFixed(2) }}</span>
            </div>
          </div>

          <!-- Payment Method -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
            <select
              v-model="paymentMethod"
              class="w-full border rounded-lg p-2"
            >
              <option
                v-for="method in paymentMethods"
                :key="method.id"
                :value="method.id"
              >
                {{ method.name }}
              </option>
            </select>
          </div>

          <!-- Bank Account (for bank/card payments) -->
          <div v-if="['bank', 'card'].includes(paymentMethod)" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Account</label>
            <select
              v-model="selectedBankAccount"
              class="w-full border rounded-lg p-2"
            >
              <option
                v-for="account in bankAccounts"
                :key="account.id"
                :value="account.id"
              >
                {{ account.account_name }} - {{ account.bank_name }}
              </option>
            </select>
          </div>

          <!-- Transaction ID (for bank/mobile banking) -->
          <div v-if="['bank', 'mobile_banking'].includes(paymentMethod)" class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID</label>
            <input
              v-model="transactionId"
              type="text"
              class="w-full border rounded-lg p-2"
            />
          </div>

          <!-- Amount Paid -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid</label>
            <input
              v-model.number="amountPaid"
              type="number"
              class="w-full border rounded-lg p-2 text-right"
              min="0"
              step="0.01"
              @input="calculateChange"
            />
          </div>

          <!-- Change -->
          <div class="mb-4 p-3 bg-white rounded-lg">
            <div class="flex justify-between text-lg">
              <span>Change</span>
              <span :class="{ 'text-green-600': change > 0 }">${{ change.toFixed(2) }}</span>
            </div>
          </div>

          <!-- Note -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Note</label>
            <textarea
              v-model="note"
              class="w-full border rounded-lg p-2"
              rows="2"
            ></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="mt-auto grid grid-cols-2 gap-4">
            <button
              @click="resetCart"
              class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 flex items-center justify-center"
            >
              <RefreshIcon class="w-5 h-5 mr-2" />
              Reset
            </button>
            <button
              @click="processSale"
              :disabled="!canProcessSale"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center justify-center"
            >
              <CashIcon class="w-5 h-5 mr-2" />
              Process Sale
            </button>
          </div>
        </div>
      </div>

      <!-- Success Modal -->
      <TransitionRoot appear :show="showSuccessModal" as="template">
        <Dialog as="div" class="relative z-10" @close="closeSuccessModal">
          <TransitionChild
            enter="ease-out duration-300"
            enter-from="opacity-0"
            enter-to="opacity-100"
            leave="ease-in duration-200"
            leave-from="opacity-100"
            leave-to="opacity-0"
          >
            <div class="fixed inset-0 bg-black bg-opacity-25" />
          </TransitionChild>

          <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
              <TransitionChild
                enter="ease-out duration-300"
                enter-from="opacity-0 scale-95"
                enter-to="opacity-100 scale-100"
                leave="ease-in duration-200"
                leave-from="opacity-100 scale-100"
                leave-to="opacity-0 scale-95"
              >
                <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                  <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                    Sale Completed Successfully!
                  </DialogTitle>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      Invoice No: {{ lastSale?.invoice_no }}
                    </p>
                  </div>

                  <div class="mt-4 flex justify-end space-x-3">
                    <button
                      @click="printReceipt"
                      class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 px-4 py-2 text-sm font-medium text-blue-900 hover:bg-blue-200"
                    >
                      Print Receipt
                    </button>
                    <button
                      @click="closeSuccessModal"
                      class="inline-flex justify-center rounded-md border border-transparent bg-green-100 px-4 py-2 text-sm font-medium text-green-900 hover:bg-green-200"
                    >
                      New Sale
                    </button>
                  </div>
                </DialogPanel>
              </TransitionChild>
            </div>
          </div>
        </Dialog>
      </TransitionRoot>
    </AdminLayout>
  </template>

  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
  import {
    BarcodeIcon,
    TrashIcon,
    RefreshIcon,
    CashIcon
  } from '@heroicons/vue/outline'
  import { useForm } from '@inertiajs/vue3'
  import axios from 'axios'
  import { Howl } from 'howler'
import AdminLayout from '@/Layouts/AdminLayout.vue'

  const props = defineProps({
    customers: Array,
    bankAccounts: Array,
    paymentMethods: Array,
  })

  // Sound effects
  const beepSound = new Howl({
    src: ['/sounds/beep.mp3']
  })

  const successSound = new Howl({
    src: ['/sounds/success.mp3']
  })

  // State
  const searchQuery = ref('')
  const searchResults = ref([])
  const showSearchResults = ref(false)
  const cartItems = ref([])
  const selectedCustomer = ref(null)
  const paymentMethod = ref('cash')
  const selectedBankAccount = ref(null)
  const transactionId = ref('')
  const amountPaid = ref(0)
  const note = ref('')
  const showSuccessModal = ref(false)
  const lastSale = ref(null)
  const searchInput = ref(null)
  const taxRate = ref(5) // 5% tax rate
  const discount = ref(0)
  const change = ref(0)

  // Computed
  const cartSummary = computed(() => {
    const subtotal = cartItems.value.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)
    const tax = (subtotal * taxRate.value) / 100
    const total = subtotal + tax - discount.value
    return { subtotal, tax, total }
  })

  const canProcessSale = computed(() => {
    return cartItems.value.length > 0 && amountPaid.value >= cartSummary.value.total
  })

  // Methods
  const searchProducts = async () => {
    if (!searchQuery.value) {
      showSearchResults.value = false
      return
    }

    try {
      const response = await axios.get(`/admin/pos/search-products`, {
        params: { search: searchQuery.value }
      })
      searchResults.value = response.data
      showSearchResults.value = true
    } catch (error) {
      console.error('Error searching products:', error)
    }
  }

  const addToCart = (product) => {
    const existingItem = cartItems.value.find(item => item.product_id === product.id)

    if (existingItem) {
      existingItem.quantity++
    } else {
      cartItems.value.push({
        product_id: product.id,
        name: product.name,
        sku: product.sku,
        unit_price: product.selling_price,
        quantity: 1
      })
    }

    beepSound.play()
    searchQuery.value = ''
    showSearchResults.value = false
    updateCart()
  }

  const removeItem = (index) => {
    cartItems.value.splice(index, 1)
    updateCart()
  }

  const updateCart = () => {
    calculateChange()
  }

  const calculateChange = () => {
    change.value = Math.max(0, amountPaid.value - cartSummary.value.total)
  }

  const resetCart = () => {
    cartItems.value = []
    selectedCustomer.value = null
    paymentMethod.value = 'cash'
    selectedBankAccount.value = null
    transactionId.value = ''
    amountPaid.value = 0
    note.value = ''
    discount.value = 0
    change.value = 0
    searchInput.value?.focus()
  }

  const processSale = async () => {
    try {
      const response = await axios.post('/admin/pos/store', {
        customer_id: selectedCustomer.value,
        items: cartItems.value,
        subtotal: cartSummary.value.subtotal,
        tax: cartSummary.value.tax,
        discount: discount.value,
        total: cartSummary.value.total,
        paid: amountPaid.value,
        payment_method: paymentMethod.value,
        bank_account_id: selectedBankAccount.value,
        transaction_id: transactionId.value,
        note: note.value
      })

      lastSale.value = response.data.sale
      successSound.play()
      showSuccessModal.value = true
    } catch (error) {
      console.error('Error processing sale:', error)
      // Show error notification
    }
  }

  const closeSuccessModal = () => {
    showSuccessModal.value = false
    resetCart()
  }

  const printReceipt = () => {
    // Implement receipt printing logic
    window.open(`/admin/sales/${lastSale.value.id}/print`, '_blank')
  }

  // Lifecycle
  onMounted(() => {
    // Focus search input on mount
    searchInput.value?.focus()

    // Listen for barcode scanner input
    let barcodeBuffer = ''
    let lastKeyTime = 0
    const BARCODE_DELAY = 20 // Max delay between keystrokes for barcode scanner

    document.addEventListener('keydown', (e) => {
      const currentTime = new Date().getTime()

      if (currentTime - lastKeyTime > BARCODE_DELAY) {
        barcodeBuffer = ''
      }

      if (e.key === 'Enter' && barcodeBuffer) {
        searchQuery.value = barcodeBuffer
        searchProducts()
        barcodeBuffer = ''
      } else {
        barcodeBuffer += e.key
      }

      lastKeyTime = currentTime
    })
  })
  </script>

  <style scoped>
  /* Add any component-specific styles here */
  </style>
