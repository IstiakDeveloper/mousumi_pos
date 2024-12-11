<template>
    <AdminLayout>
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Sale Details
        </h2>
      </template>

      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900">Sale Information</h3>
                <div class="mt-4">
                  <p class="text-sm font-medium text-gray-500">Invoice No</p>
                  <p class="mt-1 text-sm text-gray-900">{{ sale.invoice_no }}</p>
                </div>
                <div class="mt-4">
                  <p class="text-sm font-medium text-gray-500">Customer</p>
                  <p class="mt-1 text-sm text-gray-900">{{ sale.customer ? sale.customer.name : 'N/A' }}</p>
                </div>
                <div class="mt-4">
                  <p class="text-sm font-medium text-gray-500">Sale Date</p>
                  <p class="mt-1 text-sm text-gray-900">{{ sale.created_at }}</p>
                </div>
                <div class="mt-4">
                  <p class="text-sm font-medium text-gray-500">Note</p>
                  <p class="mt-1 text-sm text-gray-900">{{ sale.note }}</p>
                </div>
              </div>

              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900">Items</h3>
                <table class="mt-4 min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr>
                      <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                      <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                      <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                      <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in sale.items" :key="item.id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.product.name }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.quantity }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.unit_price }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ item.subtotal }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900">Sale Summary</h3>
                <div class="mt-4">
                  <div class="flex justify-between">
                    <p class="text-sm font-medium text-gray-500">Subtotal</p>
                    <p class="text-sm text-gray-900">{{ sale.subtotal }}</p>
                  </div>
                  <div class="flex justify-between mt-2">
                    <p class="text-sm font-medium text-gray-500">Tax</p>
                    <p class="text-sm text-gray-900">{{ sale.tax }}</p>
                  </div>
                  <div class="flex justify-between mt-2">
                    <p class="text-sm font-medium text-gray-500">Discount</p>
                    <p class="text-sm text-gray-900">{{ sale.discount }}</p>
                  </div>
                  <div class="flex justify-between mt-2">
                    <p class="text-sm font-medium text-gray-500">Total</p>
                    <p class="text-sm text-gray-900">{{ sale.total }}</p>
                  </div>
                  <div class="flex justify-between mt-2">
                    <p class="text-sm font-medium text-gray-500">Paid</p>
                    <p class="text-sm text-gray-900">{{ sale.paid }}</p>
                  </div>
                  <div class="flex justify-between mt-2">
                    <p class="text-sm font-medium text-gray-500">Due</p>
                    <p class="text-sm text-gray-900">{{ sale.due }}</p>
                  </div>
                </div>
              </div>

              <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900">Payments</h3>
                <table class="mt-4 min-w-full divide-y divide-gray-200">
                  <thead>
                    <tr>
                      <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                      <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                      <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                      <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="payment in sale.payments" :key="payment.id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ payment.amount }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ payment.payment_method }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ payment.transaction_id }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ payment.note }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="flex justify-end">
                <inertia-link :href="route('sales.edit', sale.id)" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                  Edit Sale
                </inertia-link>
                <button @click="deleteSale" class="ml-4 px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600">
                  Delete Sale
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  </template>

  <script>
  import AdminLayout from '@/Layouts/AdminLayout'

  export default {
    components: {
      AdminLayout,
    },
    props: {
      sale: Object,
    },
    methods: {
      deleteSale() {
        if (confirm('Are you sure you want to delete this sale?')) {
          this.$inertia.delete(route('sales.destroy', this.sale.id))
        }
      },
    },
  }
  </script>
