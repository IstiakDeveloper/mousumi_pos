<template>
    <div class="flex flex-col min-h-screen max-h-screen p-4 space-y-3">
        <!-- Top Section: Customer and Bank Selection -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>
                <select v-model="selectedCustomer" class="w-full h-9 border rounded-lg px-2 text-sm dark:bg-gray-700">
                    <option :value="null">Walk-in Customer</option>
                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                        {{ customer.name }} ({{ customer.phone }}) - ৳{{ formatNumber(customer.credit_limit) }}
                    </option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Bank Account</label>
                <select v-model="selectedBankAccount" class="w-full h-9 border rounded-lg px-2 text-sm dark:bg-gray-700">
                    <option :value="null">Select Bank Account</option>
                    <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                        {{ account.account_name }} - {{ account.bank_name }} (৳{{ formatNumber(account.current_balance) }})
                    </option>
                </select>
            </div>
        </div>

        <!-- Middle Section: Cart Summary and Payment -->
        <div class="grid grid-cols-2 gap-4 flex-grow">
            <!-- Cart Summary -->
            <div class="bg-white dark:bg-gray-700 rounded-lg p-3 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Items</span>
                    <span class="font-medium">{{ cartSummary.items }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-medium">৳{{ formatNumber(cartSummary.subtotal) }}</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">Discount</span>
                    <input type="number" v-model.number="discount"
                           class="w-20 h-8 text-right border rounded" min="0" step="0.01"
                           @input="calculateTotal" />
                </div>
                <div class="flex justify-between text-base font-bold pt-2 border-t">
                    <span>Total</span>
                    <span class="text-blue-600">৳{{ formatNumber(total) }}</span>
                </div>
            </div>

            <!-- Payment Section -->
            <div class="space-y-2">
                <div>
                    <label class="text-sm font-medium">Amount Paid</label>
                    <div class="grid grid-cols-4 gap-1 mb-2">
                        <button v-for="amount in quickAmounts" :key="amount"
                                @click="setQuickAmount(amount)"
                                class="p-1 text-sm border rounded hover:bg-gray-50">
                            ৳{{ amount }}
                        </button>
                    </div>
                    <input type="number" v-model.number="amountPaid"
                           class="w-full h-9 border rounded-lg px-2 text-right" min="0" step="0.01" />
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/50 rounded-lg p-2">
                    <div class="flex justify-between">
                        <span class="text-yellow-700">Due</span>
                        <span class="font-bold text-yellow-700">৳{{ formatNumber(dueAmount) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Section -->
        <div class="space-y-2">
            <textarea v-model="note" placeholder="Note"
                      class="w-full h-16 border rounded-lg p-2 text-sm resize-none"></textarea>
            <div class="grid grid-cols-2 gap-4">
                <button @click="$emit('reset-cart')"
                        class="h-10 bg-gray-500 text-white rounded-lg hover:bg-gray-600 flex items-center justify-center">
                    <ArrowPathIcon class="w-4 h-4 mr-2" />Reset
                </button>
                <button @click="processSale" :disabled="!canProcessSale"
                        class="h-10 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 flex items-center justify-center">
                    <BanknotesIcon class="w-4 h-4 mr-2" />Process Sale
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
// Script remains the same
import { ref, computed } from 'vue'
import * as LucideIcons from 'lucide-vue-next'

const ArrowPathIcon = LucideIcons.ArrowPath
const BanknotesIcon = LucideIcons.Banknotes

const props = defineProps({
    customers: { type: Array, required: true },
    bankAccounts: { type: Array, required: true },
    cartItems: { type: Array, required: true },
    cartSummary: { type: Object, required: true }
})

const emit = defineEmits(['process-sale', 'reset-cart'])

const selectedCustomer = ref(null)
const selectedBankAccount = ref(null)
const amountPaid = ref(0)
const discount = ref(0)
const note = ref('')
const quickAmounts = [500, 1000, 2000, 5000]

const total = computed(() => props.cartSummary.subtotal - discount.value)
const dueAmount = computed(() => Math.max(0, total.value - amountPaid.value))
const canProcessSale = computed(() => {
    if (!props.cartItems.length || !selectedBankAccount.value) return false
    if (dueAmount.value > 0 && selectedCustomer.value) {
        const customer = props.customers.find(c => c.id === selectedCustomer.value)
        if (!customer) return false
        const newBalance = customer.balance + dueAmount.value
        if (newBalance > customer.credit_limit) return false
    }
    return true
})

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
    emit('process-sale', {
        customer_id: selectedCustomer.value,
        items: props.cartItems,
        subtotal: props.cartSummary.subtotal,
        discount: discount.value,
        total: total.value,
        paid: amountPaid.value,
        due: dueAmount.value,
        bank_account_id: selectedBankAccount.value,
        note: note.value
    })
}
</script>
