<template>
    <div class="flex flex-col h-full">
        <!-- Customer Selection -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Customer
            </label>
            <select v-model="selectedCustomer"
                class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                <option :value="null">Walk-in Customer</option>
                <option v-for="customer in customers"
                    :key="customer.id"
                    :value="customer.id">
                    {{ customer.name }} ({{ customer.phone }})
                    - Credit Limit: ৳{{ formatNumber(customer.credit_limit) }}
                </option>
            </select>
        </div>

        <!-- Cart Summary -->
        <div class="bg-white dark:bg-gray-700 rounded-lg p-4 mb-4">
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">Items</span>
                    <span class="font-medium text-gray-900 dark:text-gray-200">
                        {{ cartSummary.items }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                    <span class="font-medium text-gray-900 dark:text-gray-200">
                        ৳{{ formatNumber(cartSummary.subtotal) }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-300">Discount</span>
                    <div class="flex items-center space-x-2">
                        <input type="number"
                            v-model.number="discount"
                            class="w-20 text-right border rounded p-1 dark:bg-gray-600 dark:border-gray-500 dark:text-gray-200"
                            min="0"
                            step="0.01"
                            @input="calculateTotal" />
                    </div>
                </div>
                <div class="flex justify-between text-lg font-bold pt-3 border-t border-gray-200 dark:border-gray-600">
                    <span class="text-gray-900 dark:text-gray-200">Total</span>
                    <span class="text-blue-600 dark:text-blue-400">
                        ৳{{ formatNumber(total) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Bank Account Selection -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Bank Account
            </label>
            <select v-model="selectedBankAccount"
                class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                <option :value="null">Select Bank Account</option>
                <option v-for="account in bankAccounts"
                    :key="account.id"
                    :value="account.id">
                    {{ account.account_name }} - {{ account.bank_name }}
                    (Balance: ৳{{ formatNumber(account.current_balance) }})
                </option>
            </select>
        </div>

        <!-- Amount Paid -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Amount Paid
            </label>
            <div class="grid grid-cols-2 gap-2 mb-2">
                <button v-for="amount in quickAmounts"
                    :key="amount"
                    @click="setQuickAmount(amount)"
                    class="p-2 text-center border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                    ৳{{ amount }}
                </button>
            </div>
            <input type="number"
                v-model.number="amountPaid"
                class="w-full border rounded-lg p-2 text-right dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                min="0"
                step="0.01" />
        </div>

        <!-- Due Amount Display -->
        <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/50 rounded-lg">
            <div class="flex justify-between text-lg">
                <span class="text-yellow-700 dark:text-yellow-300">Due Amount</span>
                <span class="font-bold text-yellow-700 dark:text-yellow-300">
                    ৳{{ formatNumber(dueAmount) }}
                </span>
            </div>
        </div>

        <!-- Note -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Note
            </label>
            <textarea v-model="note"
                class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                rows="2"></textarea>
        </div>

        <!-- Action Buttons -->
        <div class="mt-auto grid grid-cols-2 gap-4">
            <button @click="$emit('reset-cart')"
                class="px-4 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 flex items-center justify-center">
                <ArrowPathIcon class="w-5 h-5 mr-2" />
                Reset
            </button>
            <button @click="processSale"
                :disabled="!canProcessSale"
                class="px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center justify-center dark:bg-green-700 dark:hover:bg-green-600">
                <BanknotesIcon class="w-5 h-5 mr-2" />
                Process Sale
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
const selectedBankAccount = ref(null)
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
    return Number(value).toLocaleString('bn-BD', {
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
