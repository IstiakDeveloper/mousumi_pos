<template>
    <AdminLayout>
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Edit Sale
        </h2>
      </template>

      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
              <form @submit.prevent="submit">
                <!-- Form fields similar to Create.vue -->
                <!-- Populate the form fields with the existing sale data -->
                <!-- Update the submit method to send a PUT request -->
              </form>
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
      customers: Array,
      products: Array,
    },
    data() {
      return {
        form: {
          customer_id: this.sale.customer_id,
          items: this.sale.items,
          tax: this.sale.tax,
          discount: this.sale.discount,
          payments: this.sale.payments,
          note: this.sale.note,
        },
      }
    },
    methods: {
      addItem() {
        this.form.items.push({
          product_id: '',
          quantity: 1,
          unit_price: 0,
        })
      },
      addPayment() {
        this.form.payments.push({
          amount: 0,
          payment_method: '',
          transaction_id: '',
          note: '',
        })
      },
      submit() {
        this.$inertia.put(route('sales.update', this.sale.id), this.form)
      },
    },
  }
  </script>
