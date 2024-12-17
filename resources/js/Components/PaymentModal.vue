<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    Add Payment
                </h2>
                <button
                    @click="$emit('close')"
                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                >
                    <XIcon class="w-6 h-6" />
                </button>
            </div>

            <form @submit.prevent="submitPayment">
                <div class="space-y-4">
                    <!-- Sale Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Select Invoice
                        </label>
                        <select
                            v-model="form.sale_id"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                            required
                        >
                            <option value="">Select an invoice</option>
                            <option
                                v-for="sale in dueInvoices"
                                :key="sale.id"
                                :value="sale.id"
                            >
                                {{ sale.invoice_no }} - ৳{{ formatNumber(sale.due) }} Due
                            </option>
                        </select>
                    </div>

                    <!-- Payment Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Payment Amount
                        </label>
                        <input
                            type="number"
                            v-model.number="form.amount"
                            min="0.01"
                            step="0.01"
                            :max="selectedInvoiceDue"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                            required
                        />
                        <p
                            v-if="selectedInvoiceDue"
                            class="text-sm text-gray-500 mt-1"
                        >
                            Maximum payable amount: ৳{{ formatNumber(selectedInvoiceDue) }}
                        </p>
                    </div>

                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Payment Method
                        </label>
                        <select
                            v-model="form.payment_method"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                            required
                        >
                            <option value="">Select payment method</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="mobile_banking">Mobile Banking</option>
                        </select>
                    </div>

                    <!-- Bank Account (Conditional) -->
                    <div
                        v-if="['bank', 'card'].includes(form.payment_method)"
                        class="mt-4"
                    >
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Bank Account
                        </label>
                        <select
                            v-model="form.bank_account_id"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                            required
                        >
                            <option value="">Select bank account</option>
                            <option
                                v-for="account in bankAccounts"
                                :key="account.id"
                                :value="account.id"
                            >
                                {{ account.bank_name }} - {{ account.account_number }}
                            </option>
                        </select>
                    </div>

                    <!-- Transaction ID (Optional) -->
                    <div v-if="['bank', 'card', 'mobile_banking'].includes(form.payment_method)">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Transaction ID (Optional)
                        </label>
                        <input
                            type="text"
                            v-model="form.transaction_id"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                        />
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Notes (Optional)
                        </label>
                        <textarea
                            v-model="form.note"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600"
                            rows="3"
                        ></textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-6 flex justify-end space-x-2">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? 'Processing...' : 'Add Payment' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { XIcon } from 'lucide-vue-next'

const props = defineProps({
    customerId: {
        type: Number,
        required: true
    }
})

const emit = defineEmits(['close'])

// Form state
const form = ref({
    sale_id: '',
    amount: null,
    payment_method: '',
    bank_account_id: null,
    transaction_id: '',
    note: ''
})

// Other reactive states
const isSubmitting = ref(false)
const dueInvoices = ref([])
const bankAccounts = ref([])

// Computed properties
const selectedInvoiceDue = computed(() => {
    const selectedSale = dueInvoices.value.find(sale => sale.id === form.value.sale_id)
    return selectedSale ? selectedSale.due : 0
})

// Fetch data on component mount
onMounted(async () => {
    try {
        // Fetch due invoices for this customer
        const invoicesResponse = await axios.get(route('admin.customers.due-invoices', props.customerId))
        dueInvoices.value = invoicesResponse.data

        // Fetch bank accounts
        const bankResponse = await axios.get(route('admin.bank-accounts.index'))
        bankAccounts.value = bankResponse.data
    } catch (error) {
        console.error('Error fetching data:', error)
    }
})

// Submit payment method
const submitPayment = () => {
    isSubmitting.value = true

    router.post(route('admin.customers.add-payment', props.customerId), form.value, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            isSubmitting.value = false
            emit('close')
        },
        onError: () => {
            isSubmitting.value = false
        }
    })
}

// Number formatting utility
const formatNumber = (value) => {
    return new Intl.NumberFormat('en-BD').format(value || 0)
}
</script>
