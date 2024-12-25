# Payment.vue
<template>
    <div class="grid grid-cols-12 gap-4 h-full p-4 bg-gray-50 dark:bg-gray-800">
        <!-- Top Row - Customer & Bank Selection (Full Width) -->
        <div class="col-span-12 grid grid-cols-2 gap-4">
            <!-- Customer Selection -->
            <div class="bg-white dark:bg-gray-700 rounded-lg p-3">
                <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Customer</label>
                <select v-model="selectedCustomer"
                    class="mt-1 w-full border rounded-lg p-2 text-sm dark:bg-gray-600 dark:border-gray-500">
                    <option :value="null">Walk-in Customer</option>
                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                        {{ customer.name }} ({{ customer.phone }})
                    </option>
                </select>
            </div>

            <!-- Bank Account -->
            <div class="bg-white dark:bg-gray-700 rounded-lg p-3">
                <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Bank Account</label>
                <select v-model="selectedBankAccount"
                    class="mt-1 w-full border rounded-lg p-2 text-sm dark:bg-gray-600 dark:border-gray-500">
                    <option :value="null">Select Account</option>
                    <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                        {{ account.account_name }} - ৳{{ formatNumber(account.current_balance) }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Middle Section - Cart Summary & Payments -->
        <div class="col-span-12 grid grid-cols-12 gap-4">
            <!-- Left Side - Cart Summary -->
            <div class="col-span-7 bg-white dark:bg-gray-700 rounded-lg p-4">
                <div class="space-y-2">
                    <div class="flex justify-between border-b pb-2 dark:border-gray-600">
                        <span class="text-gray-600 dark:text-gray-300">Items</span>
                        <span class="font-medium">{{ cartSummary.items }}</span>
                    </div>
                    <div class="flex justify-between border-b pb-2 dark:border-gray-600">
                        <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                        <span class="font-medium">৳{{ formatNumber(cartSummary.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-300">Discount</span>
                        <input type="number" v-model.number="discount"
                            class="w-24 text-right border rounded p-1 text-sm dark:bg-gray-600 dark:border-gray-500"
                            min="0" step="0.01" @input="calculateTotal" />
                    </div>
                    <div class="flex justify-between text-lg font-bold pt-2 border-t dark:border-gray-600">
                        <span>Total</span>
                        <span class="text-blue-600 dark:text-blue-400">৳{{ formatNumber(total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Right Side - Quick Payment -->
            <div class="col-span-5 bg-white dark:bg-gray-700 rounded-lg p-4">
                <div class="grid grid-cols-2 gap-2">
                    <button v-for="amount in quickAmounts" :key="amount"
                        @click="setQuickAmount(amount)"
                        class="p-2 text-sm border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                        ৳{{ amount }}
                    </button>
                </div>
                <div class="mt-3">
                    <input type="number" v-model.number="amountPaid"
                        class="w-full border rounded-lg p-2 text-right text-lg dark:bg-gray-600 dark:border-gray-500"
                        min="0" step="0.01" />
                </div>
            </div>
        </div>

        <!-- Bottom Section - Due Amount, Note & Actions -->
        <div class="col-span-12 grid grid-cols-12 gap-4">
            <!-- Due Amount -->
            <div class="col-span-4 bg-yellow-50 dark:bg-yellow-900/50 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-yellow-700 dark:text-yellow-300">Due</span>
                    <span class="font-bold text-lg text-yellow-700 dark:text-yellow-300">
                        ৳{{ formatNumber(dueAmount) }}
                    </span>
                </div>
            </div>

            <!-- Note -->
            <div class="col-span-8">
                <textarea v-model="note" rows="2"
                    class="w-full border rounded-lg p-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                    placeholder="Add note (optional)"></textarea>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="col-span-12 grid grid-cols-2 gap-4">
            <button @click="$emit('reset-cart')"
                class="p-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 flex items-center justify-center">
                <ArrowPathIcon class="w-5 h-5 mr-2" />
                Reset
            </button>
            <button @click="processSale"
                :disabled="!canProcessSale"
                class="p-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400
                       disabled:cursor-not-allowed flex items-center justify-center">
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
