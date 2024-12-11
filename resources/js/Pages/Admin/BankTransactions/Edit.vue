<template>
    <AdminLayout>
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Edit Bank Transaction
        </h2>
      </template>

      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
            <div class="p-6">
              <form @submit.prevent="submit">
                <div class="mb-4">
                  <label for="bank_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Bank Account</label>
                  <select id="bank_account_id" v-model="form.bank_account_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    <option v-for="bankAccount in bankAccounts" :key="bankAccount.id" :value="bankAccount.id">
                      {{ bankAccount.account_name }}
                    </option>
                  </select>
                </div>
                <div class="mb-4">
                  <label for="transaction_type" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Transaction Type</label>
                  <select id="transaction_type" v-model="form.transaction_type" class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    <option value="deposit">Deposit</option>
                    <option value="withdrawal">Withdrawal</option>
                    <option value="transfer">Transfer</option>
                  </select>
                </div>
                <div class="mb-4">
                  <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Amount</label>
                  <input type="number" id="amount" v-model="form.amount" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md" required>
                </div>
                <div class="mb-4">
                  <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
                  <textarea id="description" v-model="form.description" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md"></textarea>
                </div>
                <div class="mb-4">
                  <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Date</label>
                  <input type="date" id="date" v-model="form.date" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200 rounded-md" required>
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

  export default {
    components: {
      AdminLayout,
    },
    props: {
      bankTransaction: Object,
      bankAccounts: Array,
    },
    data() {
      return {
        form: {
          bank_account_id: this.bankTransaction.bank_account_id,
          transaction_type: this.bankTransaction.transaction_type,
          amount: this.bankTransaction.amount,
          description: this.bankTransaction.description,
          date: this.bankTransaction.date,
        },
      }
    },
    methods: {
      submit() {
        this.$inertia.put(route('admin.bank-transactions.update', this.bankTransaction.id), this.form)
      },
    },
  }
  </script>
