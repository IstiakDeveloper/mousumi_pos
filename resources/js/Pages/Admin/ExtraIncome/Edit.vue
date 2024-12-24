<!-- resources/js/Pages/Admin/ExtraIncome/Edit.vue -->
<template>
    <AdminLayout>
      <template #header>
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200 leading-tight">
            Edit Extra Income
          </h2>
          <Link
            :href="route('admin.extra-incomes.index')"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800"
          >
            <div class="flex items-center space-x-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              <span>Back to List</span>
            </div>
          </Link>
        </div>
      </template>

      <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <!-- Form Status Alert -->
            <div v-if="showSuccessAlert" class="bg-green-50 dark:bg-green-900 p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-green-800 dark:text-green-200">
                    Successfully updated the extra income!
                  </p>
                </div>
                <div class="ml-auto pl-3">
                  <div class="-mx-1.5 -my-1.5">
                    <button
                      @click="showSuccessAlert = false"
                      class="inline-flex rounded-md p-1.5 text-green-500 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600"
                    >
                      <span class="sr-only">Dismiss</span>
                      <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <form @submit.prevent="submit" class="p-6 space-y-6">
              <!-- Bank Account Selection -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1">
                  <InputLabel for="bank_account_id" class="text-gray-700 dark:text-gray-300">
                    Bank Account <span class="text-red-500">*</span>
                  </InputLabel>
                  <select
                    v-model="form.bank_account_id"
                    id="bank_account_id"
                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
                    required
                  >
                    <option value="" disabled>Select Bank Account</option>
                    <option
                      v-for="account in bankAccounts"
                      :key="account.id"
                      :value="account.id"
                    >
                      {{ account.bank_name }} - {{ account.account_number }}
                      (Balance: {{ formatCurrency(account.current_balance) }})
                    </option>
                  </select>
                  <InputError :message="form.errors.bank_account_id" />
                </div>

                <div class="col-span-1">
                  <InputLabel for="date" class="text-gray-700 dark:text-gray-300">
                    Date <span class="text-red-500">*</span>
                  </InputLabel>
                  <TextInput
                    v-model="form.date"
                    type="date"
                    id="date"
                    class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                    required
                    :max="maxDate"
                  />
                  <InputError :message="form.errors.date" />
                </div>
              </div>

              <!-- Amount and Title -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1">
                  <InputLabel for="title" class="text-gray-700 dark:text-gray-300">
                    Title <span class="text-red-500">*</span>
                  </InputLabel>
                  <TextInput
                    v-model="form.title"
                    type="text"
                    id="title"
                    class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                    required
                    placeholder="Enter income title"
                  />
                  <InputError :message="form.errors.title" />
                </div>

                <div class="col-span-1">
                  <InputLabel for="amount" class="text-gray-700 dark:text-gray-300">
                    Amount <span class="text-red-500">*</span>
                  </InputLabel>
                  <div class="mt-1 relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span class="text-gray-500 dark:text-gray-400 sm:text-sm">৳</span>
                    </div>
                    <TextInput
                      v-model="form.amount"
                      type="number"
                      step="0.01"
                      id="amount"
                      class="block w-full pl-7 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                      required
                      placeholder="0.00"
                      min="0"
                    />
                  </div>
                  <InputError :message="form.errors.amount" />
                </div>
              </div>

              <!-- Description -->
              <div>
                <InputLabel for="description" class="text-gray-700 dark:text-gray-300">
                  Description
                </InputLabel>
                <TextArea
                  v-model="form.description"
                  id="description"
                  rows="4"
                  class="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  placeholder="Enter additional details about this income"
                />
                <InputError :message="form.errors.description" />
              </div>

              <!-- Transaction History -->
              <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Transaction History</h3>
                <div class="space-y-3">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Original Amount:</span>
                    <span class="text-gray-900 dark:text-gray-200">
                      {{ formatCurrency(extraIncome.amount) }}
                    </span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Original Bank Account:</span>
                    <span class="text-gray-900 dark:text-gray-200">
                      {{ originalBank?.bank_name }} - {{ originalBank?.account_number }}
                    </span>
                  </div>
                  <div v-if="hasChanges" class="border-t dark:border-gray-600 pt-3">
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Changes Preview:</div>
                    <div class="space-y-2">
                      <div v-if="amountChanged" class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Amount Change:</span>
                        <span :class="getAmountChangeClass">
                          {{ getAmountDifference }}
                        </span>
                      </div>
                      <div v-if="bankChanged" class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">New Bank Balance:</span>
                        <span class="text-green-600 dark:text-green-400">
                          {{ formatCurrency(newBalance) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="flex items-center justify-end space-x-3 pt-4 border-t dark:border-gray-600">
                <Link
                  :href="route('admin.extra-incomes.index')"
                  class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="form.processing || !hasChanges"
                  class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg
                    v-if="form.processing"
                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ form.processing ? 'Saving...' : 'Update Income' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AdminLayout>
  </template>

  <script setup>
  import { ref, computed, onMounted, onUnmounted } from 'vue'
  import { useForm, Link } from '@inertiajs/vue3'
  import AdminLayout from '@/Layouts/AdminLayout.vue'
  import TextInput from '@/Components/TextInput.vue'
  import InputLabel from '@/Components/InputLabel.vue'
  import InputError from '@/Components/InputError.vue'
  import TextArea from '@/Components/TextArea.vue'

  const props = defineProps({
    extraIncome: {
      type: Object,
      required: true
    },
    bankAccounts: {
      type: Array,
      required: true
    }
  })

  const showSuccessAlert = ref(false)

  const form = useForm({
    bank_account_id: props.extraIncome.bank_account_id,
    title: props.extraIncome.title,
    amount: props.extraIncome.amount,
    description: props.extraIncome.description || '',
    date: props.extraIncome.date
  })

  const maxDate = new Date().toISOString().split('T')[0]

  const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-BD', {
      style: 'currency',
      currency: 'BDT',
      minimumFractionDigits: 2
    }).format(amount || 0)
  }

  const originalBank = computed(() => {
    return props.bankAccounts.find(account => account.id === props.extraIncome.bank_account_id)
  })

  const selectedBank = computed(() => {
    return props.bankAccounts.find(account => account.id === form.bank_account_id)
  })

  const hasChanges = computed(() => {
    return form.bank_account_id !== props.extraIncome.bank_account_id ||
      form.title !== props.extraIncome.title ||
      Number(form.amount) !== Number(props.extraIncome.amount) ||
      form.description !== props.extraIncome.description ||
      form.date !== props.extraIncome.date
  })

  const bankChanged = computed(() => {
    return form.bank_account_id !== props.extraIncome.bank_account_id
  })

  const amountChanged = computed(() => {
    return Number(form.amount) !== Number(props.extraIncome.amount)
  })

  const getAmountDifference = computed(() => {
    const diff = Number(form.amount) - Number(props.extraIncome.amount)
    return formatCurrency(Math.abs(diff)) + (diff >= 0 ? ' (Increase)' : ' (Decrease)')
  })

  const getAmountChangeClass = computed(() => {
  const diff = Number(form.amount) - Number(props.extraIncome.amount)
  return {
    'text-green-600 dark:text-green-400': diff >= 0,
    'text-red-600 dark:text-red-400': diff < 0
  }
})

const newBalance = computed(() => {
  if (!selectedBank.value || !form.amount) return 0

  let balance = Number(selectedBank.value.current_balance)

  // If same bank, adjust only the difference
  if (form.bank_account_id === props.extraIncome.bank_account_id) {
    balance += (Number(form.amount) - Number(props.extraIncome.amount))
  } else {
    // If different bank, add full amount to new bank
    balance += Number(form.amount)
  }

  return balance
})

const submit = () => {
  form.put(route('admin.extra-incomes.update', props.extraIncome.id), {
    preserveScroll: true,
    onSuccess: () => {
      showSuccessAlert.value = true
      setTimeout(() => {
        showSuccessAlert.value = false
      }, 5000) // Hide alert after 5 seconds
    }
  })
}

// Add beforeUnload warning if there are unsaved changes
onMounted(() => {
  window.addEventListener('beforeunload', (e) => {
    if (hasChanges.value) {
      e.returnValue = 'You have unsaved changes. Are you sure you want to leave?'
    }
  })
})

onUnmounted(() => {
  window.removeEventListener('beforeunload', () => {})
})
</script>

<style scoped>
/* Add any scoped styles here if needed */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  -moz-appearance: textfield;
}
</style>
