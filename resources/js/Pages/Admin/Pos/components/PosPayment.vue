<template>
    <div
        class="flex flex-col w-full bg-gradient-to-br from-slate-50 to-blue-50 dark:from-gray-800 dark:to-gray-900">
        <!-- Payment Header -->
        <div class="flex items-center justify-between p-2 text-white bg-gradient-to-r from-blue-600 to-indigo-600">
            <div>
                <h2 class="text-xs font-semibold">Payment Terminal</h2>
                <p class="text-xs text-blue-100 opacity-90">Complete Transaction</p>
            </div>
            <div class="px-2 py-1 rounded bg-white/20">
                <span class="text-xs font-medium">Session Active</span>
            </div>
        </div>

        <!-- Payment Content -->
        <div class="flex-1 overflow-auto">
            <!-- Customer Section -->
            <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-white rounded shadow-sm dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Customer</label>
                        <button class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            + New
                        </button>
                    </div>
                    <select v-model="selectedCustomer"
                        class="w-full py-1 text-xs text-gray-900 bg-white border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                        <option :value="null">Walk-in Customer</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name }} - {{ customer.phone }}
                        </option>
                    </select>
                    <div v-if="selectedCustomer" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Balance: ৳{{ formatNumber(getCustomerBalance()) }} |
                        Credit: ৳{{ formatNumber(getCustomerCredit()) }}
                    </div>
                </div>
            </div>

            <!-- Payment Method Section -->
            <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-white rounded shadow-sm dark:bg-gray-800">
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Payment
                        Method</label>
                    <select v-model="selectedBankAccount"
                        class="w-full py-1 text-xs text-gray-900 bg-white border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                        <option :value="null">Select Payment Method</option>
                        <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                            {{ account.account_name }}
                        </option>
                    </select>
                    <div v-if="selectedBankAccount" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Available: ৳{{ formatNumber(getBankBalance()) }}
                    </div>
                </div>
            </div>

            <!-- Order Summary Section -->
            <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-white rounded shadow-sm dark:bg-gray-800">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-medium text-gray-900 dark:text-white">Order Summary</h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ cartSummary.items }} items</span>
                    </div>

                    <div class="space-y-1.5">
                        <!-- Subtotal -->
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Subtotal</span>
                            <span class="text-xs font-medium text-gray-900 dark:text-gray-100">
                                ৳{{ formatNumber(cartSummary.subtotal) }}
                            </span>
                        </div>

                        <!-- Discount -->
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Discount</span>
                            <div class="flex items-center space-x-1">
                                <select v-model="discountType"
                                    class="text-xs border-gray-300 dark:border-gray-600 rounded py-0.5 px-1">
                                    <option value="fixed">৳</option>
                                    <option value="percent">%</option>
                                </select>
                                <input type="number" v-model.number="discountValue" class="w-16 text-right text-xs border-gray-300 dark:border-gray-600 rounded
                                           bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 p-0.5" min="0"
                                    :step="discountType === 'percent' ? '0.1' : '0.01'"
                                    :max="discountType === 'percent' ? '100' : cartSummary.subtotal" placeholder="0" />
                            </div>
                        </div>

                        <!-- Tax (if applicable) -->
                        <div class="flex items-center justify-between" v-if="taxRate > 0">
                            <span class="text-xs text-gray-600 dark:text-gray-400">Tax ({{ taxRate }}%)</span>
                            <span class="text-xs font-medium text-gray-900 dark:text-gray-100">
                                ৳{{ formatNumber(taxAmount) }}
                            </span>
                        </div>

                        <!-- Total -->
                        <div class="border-t pt-1.5 mt-1.5">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">Total Amount</span>
                                <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                    ৳{{ formatNumber(total) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Amount Section -->
            <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                <div
                    class="p-2 border border-green-200 rounded shadow-sm bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 dark:border-green-800">
                    <div class="text-center">
                        <p class="mb-1 text-xs font-medium text-green-800 dark:text-green-400">Amount to Collect</p>
                        <div class="p-2 mb-2 bg-white rounded dark:bg-gray-800">
                            <p class="text-lg font-bold text-green-600 dark:text-green-400">
                                ৳{{ formatNumber(total) }}
                            </p>
                        </div>
                        <p class="text-xs text-green-600 dark:text-green-500">No Credit Sales Allowed</p>
                    </div>
                </div>
            </div>

            <!-- Quick Payment Buttons -->
            <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                <div class="p-2 bg-white rounded shadow-sm dark:bg-gray-800">
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Quick Payment</label>
                    <div class="grid grid-cols-2 gap-1">
                        <button v-for="amount in quickAmounts" :key="amount" @click="setQuickAmount(amount)"
                            :disabled="amount < total"
                            class="px-2 py-1 text-xs transition-colors border rounded hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="amount >= total ? 'border-blue-300 text-blue-600 hover:border-blue-400' : 'border-gray-300 text-gray-500'">
                            ৳{{ formatNumber(amount) }}
                        </button>
                    </div>
                    <div v-if="changeAmount > 0"
                        class="mt-2 p-1.5 bg-yellow-50 dark:bg-yellow-900/20 rounded border border-yellow-200 dark:border-yellow-800">
                        <div class="text-xs text-center text-yellow-800 dark:text-yellow-400">
                            <span class="font-medium">Change: ৳{{ formatNumber(changeAmount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Note -->
            <div class="p-2">
                <div class="p-2 bg-white rounded shadow-sm dark:bg-gray-800">
                    <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Transaction
                        Note</label>
                    <textarea v-model="note" rows="2"
                        class="w-full p-1 text-xs text-gray-900 bg-white border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Add transaction details, special instructions, etc..."></textarea>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="p-2 space-y-1.5 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <!-- Sale Info -->
            <div class="flex items-center justify-between px-1 text-xs text-gray-600 dark:text-gray-400">
                <span>Invoice: #{{ generateInvoiceNo() }}</span>
                <span>{{ new Date().toLocaleDateString('en-BD') }}</span>
            </div>

            <!-- Reset Button -->
            <button @click="confirmReset" class="w-full py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded text-xs font-medium
                       transition-colors duration-200 flex items-center justify-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Clear Transaction
            </button>

            <!-- Hold Transaction Button -->
            <button @click="holdTransaction" :disabled="!canHoldTransaction" class="w-full py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded text-xs font-medium
                       transition-colors duration-200 flex items-center justify-center
                       disabled:bg-gray-400 disabled:cursor-not-allowed">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.232 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                Hold Transaction
            </button>

            <!-- Process Sale Button -->
            <button @click="processSale" :disabled="!canProcessSale" class="w-full py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700
                       text-white rounded text-xs font-bold transition-all duration-200
                       disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed
                       flex items-center justify-center shadow-md">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                COMPLETE SALE - ৳{{ formatNumber(total) }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

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

const emit = defineEmits(['process-sale', 'reset-cart', 'hold-transaction'])

// State
const selectedCustomer = ref(null)
const selectedBankAccount = ref(1)
const discountType = ref('fixed') // 'fixed' or 'percent'
const discountValue = ref(0)
const taxRate = ref(0) // Tax percentage if applicable
const note = ref('')
const quickPaymentAmount = ref(0)

// Quick payment amounts (in BDT)
const quickAmounts = [100, 500, 1000, 2000, 5000, 10000]

// Computed
const discountAmount = computed(() => {
    if (discountType.value === 'percent') {
        return (props.cartSummary.subtotal * discountValue.value) / 100
    }
    return discountValue.value || 0
})

const taxAmount = computed(() => {
    const afterDiscount = props.cartSummary.subtotal - discountAmount.value
    return (afterDiscount * taxRate.value) / 100
})

const total = computed(() => {
    return Math.max(0, props.cartSummary.subtotal - discountAmount.value + taxAmount.value)
})

const changeAmount = computed(() => {
    return Math.max(0, quickPaymentAmount.value - total.value)
})

const canProcessSale = computed(() => {
    return props.cartItems.length > 0 && selectedBankAccount.value && total.value > 0
})

const canHoldTransaction = computed(() => {
    return props.cartItems.length > 0
})

// Methods
const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const getCustomerBalance = () => {
    if (!selectedCustomer.value) return 0
    const customer = props.customers.find(c => c.id === selectedCustomer.value)
    return customer ? customer.balance : 0
}

const getCustomerCredit = () => {
    if (!selectedCustomer.value) return 0
    const customer = props.customers.find(c => c.id === selectedCustomer.value)
    return customer ? customer.credit_limit : 0
}

const getBankBalance = () => {
    if (!selectedBankAccount.value) return 0
    const account = props.bankAccounts.find(a => a.id === selectedBankAccount.value)
    return account ? account.current_balance : 0
}

const generateInvoiceNo = () => {
    const today = new Date()
    const year = today.getFullYear().toString().slice(-2)
    const month = (today.getMonth() + 1).toString().padStart(2, '0')
    const day = today.getDate().toString().padStart(2, '0')
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0')
    return `${year}${month}${day}${random}`
}

const setQuickAmount = (amount) => {
    quickPaymentAmount.value = amount
}

const confirmReset = () => {
    if (props.cartItems.length > 0) {
        if (confirm('Are you sure you want to clear this transaction?')) {
            resetTransaction()
        }
    } else {
        resetTransaction()
    }
}

const resetTransaction = () => {
    selectedCustomer.value = null
    discountValue.value = 0
    note.value = ''
    quickPaymentAmount.value = 0
    emit('reset-cart')
}

const holdTransaction = () => {
    // Implement hold transaction logic
    const transactionData = {
        customer_id: selectedCustomer.value,
        items: props.cartItems,
        discount_type: discountType.value,
        discount_value: discountValue.value,
        note: note.value,
        held_at: new Date().toISOString()
    }

    emit('hold-transaction', transactionData)
}

const processSale = () => {
    if (!canProcessSale.value) return

    const saleData = {
        customer_id: selectedCustomer.value,
        items: props.cartItems,
        subtotal: props.cartSummary.subtotal,
        discount_type: discountType.value,
        discount_amount: discountAmount.value,
        tax_rate: taxRate.value,
        tax_amount: taxAmount.value,
        total: total.value,
        paid: total.value, // Always equals total (no credit)
        due: 0, // Always 0
        change: changeAmount.value,
        bank_account_id: selectedBankAccount.value,
        note: note.value,
        invoice_no: generateInvoiceNo()
    }

    emit('process-sale', saleData)
}

// Watch for discount type changes
watch(discountType, () => {
    discountValue.value = 0
})
</script>
