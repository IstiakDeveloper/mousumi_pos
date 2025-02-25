<template>
    <div class="grid grid-cols-12 gap-3 h-full p-4 bg-gray-50 dark:bg-gray-800">
        <!-- Top Row - Customer & Bank Selection -->
        <div class="col-span-12 grid grid-cols-2 gap-3">
            <!-- Customer Selection -->
            <div class="bg-white dark:bg-gray-700 rounded-lg shadow-sm p-3">
                <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Customer</label>
                <select v-model="selectedCustomer"
                    class="w-full rounded-lg border-gray-200 dark:border-gray-600 bg-transparent
                           dark:bg-gray-600 text-gray-900 dark:text-gray-100 text-sm py-2
                           focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                    <option :value="null">Walk-in Customer</option>
                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                        {{ customer.name }} ({{ customer.phone }})
                    </option>
                </select>
            </div>

            <!-- Bank Account -->
            <div class="bg-white dark:bg-gray-700 rounded-lg shadow-sm p-3">
                <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">Bank Account</label>
                <select v-model="selectedBankAccount"
                    class="w-full rounded-lg border-gray-200 dark:border-gray-600 bg-transparent
                           dark:bg-gray-600 text-gray-900 dark:text-gray-100 text-sm py-2
                           focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                    <option :value="null">Select Account</option>
                    <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                        {{ account.account_name }} - ৳{{ formatNumber(account.current_balance) }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Middle Section - Cart Summary -->
        <div class="col-span-12 bg-white dark:bg-gray-700 rounded-lg shadow-sm p-4">
            <div class="space-y-3">
                <div class="flex justify-between pb-2 border-b dark:border-gray-600">
                    <span class="text-gray-600 dark:text-gray-300">Items</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ cartSummary.items }}</span>
                </div>
                <div class="flex justify-between pb-2 border-b dark:border-gray-600">
                    <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">৳{{ formatNumber(cartSummary.subtotal) }}</span>
                </div>
                <!-- Discount Input -->
                <div class="flex justify-between pb-2 border-b dark:border-gray-600">
                    <span class="text-gray-600 dark:text-gray-300">Discount</span>
                    <div class="flex items-center">
                        <input
                            type="number"
                            v-model.number="discount"
                            class="w-24 text-right border-gray-200 dark:border-gray-600 rounded-md text-sm p-1
                                  bg-transparent dark:bg-gray-600 text-gray-900 dark:text-gray-100
                                  focus:ring-1 focus:ring-blue-500 focus:border-transparent"
                            min="0"
                            step="0.01"
                        />
                        <span class="ml-1 font-medium text-gray-900 dark:text-gray-100">৳</span>
                    </div>
                </div>
                <div class="flex justify-between text-base font-bold pt-1">
                    <span class="text-gray-900 dark:text-gray-100">Total</span>
                    <span class="text-blue-600 dark:text-blue-400">৳{{ formatNumber(total) }}</span>
                </div>
            </div>
        </div>

        <!-- Amount Paid Input -->
        <div class="col-span-12">
            <input type="number" v-model.number="amountPaid"
                class="w-full border-gray-200 dark:border-gray-600 rounded-lg p-3 text-right text-lg
                       bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                       focus:ring-1 focus:ring-blue-500 focus:border-transparent"
                min="0" step="0.01"
                placeholder="Enter amount paid..."
            />
        </div>

        <!-- Due Amount & Note -->
        <div class="col-span-12 grid grid-cols-12 gap-3">
            <!-- Due Amount -->
            <div class="col-span-4 bg-yellow-50 dark:bg-yellow-900/50 rounded-lg shadow-sm p-3">
                <div class="flex justify-between items-center">
                    <span class="text-yellow-800 dark:text-yellow-300 text-sm">Due</span>
                    <span class="text-lg font-bold text-yellow-800 dark:text-yellow-300">
                        ৳{{ formatNumber(dueAmount) }}
                    </span>
                </div>
            </div>

            <!-- Note -->
            <div class="col-span-8">
                <textarea v-model="note" rows="2"
                    class="w-full rounded-lg border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700
                           text-gray-900 dark:text-gray-100 p-2 text-sm
                           focus:ring-1 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Add note (optional)"></textarea>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="col-span-12 grid grid-cols-2 gap-3">
            <!-- Reset Button -->
            <button @click="$emit('reset-cart')"
                class="flex items-center justify-center px-4 py-2 bg-gray-100 hover:bg-gray-200
                       dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200
                       rounded-lg transition-colors duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset
            </button>

            <!-- Process Sale Button -->
            <button @click="processSale"
                :disabled="!canProcessSale"
                class="flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700
                       text-white rounded-lg transition-colors duration-150
                       disabled:bg-gray-400 disabled:cursor-not-allowed">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Process
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import * as LucideIcons from 'lucide-vue-next'

const ArrowPathIcon = LucideIcons.ArrowPath
const BanknotesIcon = LucideIcons.Banknotes

const props = defineProps({
    customers: {
        type: Array,
        required: true
    },
    bankAccounts: {
        type: Array,
        required: true
    },
    cartItems: {
        type: Array,
        required: true
    },
    cartSummary: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['process-sale', 'reset-cart'])

// State
const selectedCustomer = ref(null)
const selectedBankAccount = ref(1)
const amountPaid = ref(0)
const discount = ref(0)
const note = ref('')

// Quick amounts for payment (in BDT)
const quickAmounts = [500, 1000, 2000, 5000]

// Computed
const total = computed(() => {
    return props.cartSummary.subtotal - discount.value
})

const dueAmount = computed(() => {
    return Math.max(0, total.value - amountPaid.value)
})

const canProcessSale = computed(() => {
    if (!props.cartItems.length || !selectedBankAccount.value) return false

    // Check customer credit limit if sale has due amount
    if (dueAmount.value > 0 && selectedCustomer.value) {
        const customer = props.customers.find(c => c.id === selectedCustomer.value)
        if (!customer) return false

        const newBalance = customer.balance + dueAmount.value
        if (newBalance > customer.credit_limit) return false
    }

    return true
})

// Methods
const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const calculateTotal = () => {
    if (amountPaid.value > total.value) {
        amountPaid.value = total.value
    }
}

const setQuickAmount = (amount) => {
    amountPaid.value = amount
}

const processSale = () => {
    if (!canProcessSale.value) return

    const saleData = {
        customer_id: selectedCustomer.value,
        items: props.cartItems,
        subtotal: props.cartSummary.subtotal,
        discount: discount.value,
        total: total.value,
        paid: amountPaid.value,
        due: dueAmount.value,
        bank_account_id: selectedBankAccount.value,
        note: note.value
    }

    emit('process-sale', saleData)
}
</script>
