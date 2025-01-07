<template>
    <Head title="Customer Edit" />
    <AdminLayout>
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Edit Customer
        </h2>
      </template>

      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
              <form @submit.prevent="submit">
                <div class="mb-4">
                  <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                  <input type="text" id="name" v-model="form.name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                  <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                  <input type="email" id="email" v-model="form.email" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                  <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                  <input type="text" id="phone" v-model="form.phone" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                </div>
                <div class="mb-4">
                  <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                  <textarea id="address" v-model="form.address" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"></textarea>
                </div>
                <div class="mb-4">
                  <label for="credit_limit" class="block text-sm font-medium text-gray-700">Credit Limit</label>
                  <input type="number" id="credit_limit" v-model="form.credit_limit" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                  <label for="balance" class="block text-sm font-medium text-gray-700">Balance</label>
                  <input type="number" id="balance" v-model="form.balance" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                  <label for="points" class="block text-sm font-medium text-gray-700">Points</label>
                  <input type="number" id="points" v-model="form.points" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                </div>
                <div class="mb-4">
                  <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                  <select id="status" v-model="form.status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option :value="true">Active</option>
                    <option :value="false">Inactive</option>
                  </select>
                </div>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                  Update
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </AdminLayout>
  </template>

  <script>
  import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3';

  export default {
    components: {
      AdminLayout,
    },
    props: {
      customer: Object,
    },
    data() {
      return {
        form: {
          name: this.customer.name,
          email: this.customer.email,
          phone: this.customer.phone,
          address: this.customer.address,
          credit_limit: this.customer.credit_limit,
          balance: this.customer.balance,
          points: this.customer.points,
          status: this.customer.status,
        },
      }
    },
    methods: {
      submit() {
        this.$inertia.put(`/admin/customers/${this.customer.id}`, this.form)
      },
    },
  }
  </script>
