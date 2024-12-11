<template>
    <AdminLayout>
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Sales
        </h2>
      </template>

      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
              <div class="flex justify-end mb-4">
                <a :href="route('admin.sales.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-md">
                  Create Sale
                </a>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice No</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paid</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Status</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="sale in sales" :key="sale.id">
                      <td class="px-6 py-4 whitespace-nowrap">{{ sale.invoice_no }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">{{ sale.customer ? sale.customer.name : 'N/A' }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">{{ sale.total }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">{{ sale.paid }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">{{ sale.due }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">{{ sale.payment_status }}</td>
                      <td class="px-6 py-4 whitespace-nowrap">{{ sale.created_at }}</td>
                      <td class="px-6 py-4 whitespace-nowrap text-right">
                        <inertia-link :href="route('admin.sales.show', sale.id)" class="text-indigo-600 hover:text-indigo-900">View</inertia-link>
                        <inertia-link :href="route('admin.sales.edit', sale.id)" class="ml-4 text-indigo-600 hover:text-indigo-900">Edit</inertia-link>
                        <button @click="deleteSale(sale.id)" class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  </template>

  <script>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';


  export default {
    components: {
      AdminLayout,
    },
    props: {
      sales: Array,
    },
    methods: {
      deleteSale(id) {
        if (confirm('Are you sure you want to delete this sale?')) {
          this.$inertia.delete(route('admin.sales.destroy', id))
        }
      },
    },
  }
  </script>
