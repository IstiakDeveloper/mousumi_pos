<template>
    <AdminLayout>
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Customers
        </h2>
      </template>

      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
              <div class="flex justify-between items-center mb-4">
                <input v-model="search" type="text" placeholder="Search..." class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <a :href="route('admin.customers.create')" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                  Create Customer
                </a>
              </div>
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Name
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Email
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Phone
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="customer in filteredCustomers" :key="customer.id">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">{{ customer.name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-500">{{ customer.email }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-500">{{ customer.phone }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="{ 'bg-green-100 text-green-800': customer.status, 'bg-red-100 text-red-800': !customer.status }">
                        {{ customer.status ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <a :href="route('admin.customers.edit', customer.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                      <a @click="destroy(customer.id)" class="text-red-600 hover:text-red-900 cursor-pointer">Delete</a>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  </template>

  <script>
  import AdminLayout from '@/Layouts/AdminLayout.vue'

  export default {
    components: {
      AdminLayout,
    },
    props: {
      customers: Array,
    },
    data() {
      return {
        search: '',
      }
    },
    computed: {
      filteredCustomers() {
        return this.customers.filter(customer =>
          customer.name.toLowerCase().includes(this.search.toLowerCase()) ||
          customer.email.toLowerCase().includes(this.search.toLowerCase()) ||
          customer.phone.toLowerCase().includes(this.search.toLowerCase())
        )
      },
    },
    methods: {
      destroy(id) {
        if (confirm('Are you sure you want to delete this customer?')) {
          this.$inertia.delete(`/customers/${id}`)
        }
      },
    },
  }
  </script>
