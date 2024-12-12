<template>
    <AdminLayout title="Point of Sale">
        <div class="flex h-[calc(100vh-64px)] dark:bg-gray-900">
            <!-- Left Side - Product Grid & Cart -->
            <div class="w-2/3 flex flex-col p-4 border-r border-gray-200 dark:border-gray-700">
                <!-- Product Grid Section -->
                <div class="flex-1 flex flex-col h-1/2 mb-4">
                    <!-- Search Bar -->
                    <div class="mb-4">
                        <div class="flex space-x-4">
                            <div class="flex-1 relative">
                                <input v-model="searchQuery" type="text"
                                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200"
                                    placeholder="Search products by name, SKU, or scan barcode..."
                                    @keyup="searchProducts" ref="searchInput" />
                                <div class="absolute right-3 top-3 text-gray-400">
                                    <QrCodeIcon class="w-5 h-5" />
                                </div>
                            </div>
                            <!-- Quick Categories -->
                            <div class="flex space-x-2">
                                <button v-for="category in quickCategories" :key="category.id"
                                    @click="filterByCategory(category.id)"
                                    class="px-4 py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300 hover:dark:bg-blue-800">
                                    {{ category.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="mb-4">
                            <div class="flex space-x-2 overflow-x-auto pb-2">
                                <button @click="filterByCategory(null)"
                                    class="px-4 py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300 hover:dark:bg-blue-800 whitespace-nowrap">
                                    All Products
                                </button>
                                <button v-for="category in quickCategories" :key="category.id"
                                    @click="filterByCategory(category.id)"
                                    class="px-4 py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-300 hover:dark:bg-blue-800 whitespace-nowrap">
                                    {{ category.name }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Product Grid -->
                    <div class="flex-1 overflow-auto bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="grid grid-cols-4 gap-4 p-4">
                            <div v-for="product in searchQuery ? searchResults : products" :key="product.id"
                                class="bg-white dark:bg-gray-700 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200">
                                <div class="p-4">
                                    <div
                                        class="w-full aspect-square bg-gray-100 dark:bg-gray-600 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                                        <template v-if="product.name">
                                            <img :src="getImageUrl(product.image)" :alt="product.name"
                                                class="object-cover w-4/5 h-4/5 rounded-lg">
                                        </template>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-200 mb-1">{{
                                        product.name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">SKU: {{ product.sku }}
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <div class="text-lg font-bold text-gray-900 dark:text-gray-200">৳{{
                                            formatNumber(product.selling_price) }}</div>
                                        <div class="text-xs" :class="[
                                            product.stock > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
                                        ]">
                                            Stock: {{ product.stock }}
                                        </div>
                                    </div>
                                    <button @click="addToCart(product)"
                                        class="mt-2 w-full bg-blue-500 hover:bg-blue-600 text-white rounded-lg py-2 text-sm">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Cart Items -->
                <div
                    class="h-1/2 overflow-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Price</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Qty</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Subtotal</th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="(item, index) in cartItems" :key="index"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-200">{{ item.name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ item.sku }}</div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                                    ৳{{ formatNumber(item.unit_price) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button @click="decrementQuantity(index)"
                                            class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <MinusCircleIcon class="w-4 h-4" />
                                        </button>
                                        <input type="number" v-model.number="item.quantity"
                                            class="w-16 text-right border rounded p-1 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                            min="1" @change="updateCart" />
                                        <button @click="incrementQuantity(index)"
                                            class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <PlusCircleIcon class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-gray-200">
                                    ৳{{ formatNumber(item.quantity * item.unit_price) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button @click="removeItem(index)"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        <TrashIcon class="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="cartItems.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <ShoppingCartIcon class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-600" />
                                    Cart is empty
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Side - Payment Details -->
            <div class="w-1/3 flex flex-col p-4 bg-gray-50 dark:bg-gray-800">
                <!-- Quick Actions -->
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <button @click="toggleHoldOrders"
                        class="flex items-center justify-center p-3 bg-yellow-50 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-800">
                        <ClockIcon class="w-5 h-5 mr-2" />
                        Hold Orders
                    </button>
                    <button @click="toggleRecentTransactions"
                        class="flex items-center justify-center p-3 bg-purple-50 dark:bg-purple-900 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-800">
                        <ClockIcon class="w-5 h-5 mr-2" />
                        Recent Sales
                    </button>
                </div>

                <!-- Customer Selection with Quick Add -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer</label>
                        <button @click="showAddCustomer = true"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                            + Add New
                        </button>
                    </div>
                    <select v-model="selectedCustomer"
                        class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                        <option :value="null">Walk-in Customer</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name }} ({{ customer.phone }})
                        </option>
                    </select>
                </div>

                <!-- Cart Summary -->
                <div class="bg-white dark:bg-gray-700 rounded-lg p-4 mb-4">
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Items</span>
                            <span class="font-medium text-gray-900 dark:text-gray-200">{{ cartItems.length }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Subtotal</span>
                            <span class="font-medium text-gray-900 dark:text-gray-200">৳{{
                                formatNumber(cartSummary.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-300">Tax ({{ taxRate }}%)</span>
                            <span class="font-medium text-gray-900 dark:text-gray-200">৳{{ formatNumber(cartSummary.tax)
                                }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-300">Discount</span>
                            <div class="flex items-center space-x-2">
                                <input type="number" v-model.number="discount"
                                    class="w-20 text-right border rounded p-1 dark:bg-gray-600 dark:border-gray-500 dark:text-gray-200"
                                    min="0" step="0.01" @input="updateCart" />
                            </div>
                        </div>
                        <div
                            class="flex justify-between text-lg font-bold pt-3 border-t border-gray-200 dark:border-gray-600">
                            <span class="text-gray-900 dark:text-gray-200">Total</span>
                            <span class="text-blue-600 dark:text-blue-400">৳{{ formatNumber(cartSummary.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Method with Icons -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Payment
                        Method</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button v-for="method in paymentMethods" :key="method.id" @click="paymentMethod = method.id"
                            :class="[
                                'p-3 rounded-lg flex items-center justify-center border-2',
                                paymentMethod === method.id
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-300'
                                    : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                            ]">
                            <component :is="getPaymentIcon(method.id)" class="w-5 h-5 mr-2" />
                            {{ method.name }}
                        </button>
                    </div>
                </div>

                <!-- Dynamic Payment Fields -->
                <div class="space-y-4">
                    <!-- Bank Account Selection -->
                    <div v-if="['bank', 'card'].includes(paymentMethod)">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bank
                            Account</label>
                        <select v-model="selectedBankAccount"
                            class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                            <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                {{ account.account_name }} - {{ account.bank_name }}
                            </option>
                        </select>
                    </div>

                    <!-- Transaction ID -->
                    <div v-if="['bank', 'mobile_banking'].includes(paymentMethod)">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Transaction
                            ID</label>
                        <input v-model="transactionId" type="text"
                            class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200" />
                    </div>

                    <!-- Amount Paid -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount
                            Paid</label>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <button v-for="amount in quickAmounts" :key="amount" @click="setQuickAmount(amount)"
                                class="p-2 text-center border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 dark:border-gray-600 dark:text-gray-200">
                                ৳{{ amount }}
                            </button>
                        </div>
                        <input v-model.number="amountPaid" type="number"
                            class="w-full border rounded-lg p-2 text-right dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                            min="0" step="0.01" @input="calculateChange" />
                    </div>

                    <!-- Change Display -->
                    <div class="p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                        <div class="flex justify-between text-lg">
                            <span class="text-green-700 dark:text-green-300">Change</span>
                            <span class="font-bold text-green-700 dark:text-green-300">৳{{ formatNumber(change)
                                }}</span>
                        </div>
                    </div>

                    <!-- Note -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Note</label>
                        <textarea v-model="note"
                            class="w-full border rounded-lg p-2 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                            rows="2"></textarea>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-auto grid grid-cols-2 gap-4">
                    <button @click="resetCart"
                        class="px-4 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 flex items-center justify-center">
                        <ArrowPathIcon class="w-5 h-5 mr-2" />
                        Reset
                    </button>
                    <button @click="processSale" :disabled="!canProcessSale"
                        class="px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center justify-center dark:bg-green-700 dark:hover:bg-green-600">
                        <CurrencyDollarIcon class="w-5 h-5 mr-2" />
                        Process Sale
                    </button>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <TransitionRoot appear :show="showSuccessModal" as="template">
            <Dialog as="div" class="relative z-10" @close="closeSuccessModal">
                <TransitionChild enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
                    leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <TransitionChild enter="ease-out duration-300" enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100" leave="ease-in duration-200"
                            leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel
                                class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3"
                                    class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">
                                    Sale Completed Successfully!
                                </DialogTitle>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Invoice No: {{ lastSale?.invoice_no }}
                                    </p>
                                </div>

                                <div class="mt-4 flex justify-end space-x-3">
                                    <button @click="printReceipt"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 dark:bg-blue-900 px-4 py-2 text-sm font-medium text-blue-900 dark:text-blue-100 hover:bg-blue-200 dark:hover:bg-blue-800">
                                        <PrinterIcon class="w-4 h-4 mr-2" />
                                        Print Receipt
                                    </button>
                                    <button @click="closeSuccessModal"
                                        class="inline-flex justify-center rounded-md border border-transparent bg-green-100 dark:bg-green-900 px-4 py-2 text-sm font-medium text-green-900 dark:text-green-100 hover:bg-green-200 dark:hover:bg-green-800">
                                        <PlusCircleIcon class="w-4 h-4 mr-2" />
                                        New Sale
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
    QrCodeIcon,
    TrashIcon,
    ArrowPathIcon,
    CurrencyDollarIcon,
    MinusCircleIcon,
    PlusCircleIcon,
    CreditCardIcon,
    BanknotesIcon,
    DevicePhoneMobileIcon,
    PrinterIcon,
    ShoppingCartIcon,
    CubeIcon,
    ClockIcon
} from '@heroicons/vue/24/outline'

import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Howl } from 'howler'
import axios from 'axios'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    customers: Array,
    bankAccounts: Array,
    paymentMethods: Array,
    categories: Array,
})

// Add products state
const products = ref([])
const showAddCustomer = ref(false)
const quickCategories = ref(props.categories || [])
const getImageUrl = (path) => {
    return `/storage/${path}`;
};


const form = useForm({
    customer_id: null,
    items: [],
    subtotal: 0,
    tax: 0,
    discount: 0,
    total: 0,
    paid: 0,
    payment_method: 'cash',
    bank_account_id: null,
    transaction_id: '',
    note: ''
})

// Quick amounts for payment (in BDT)
const quickAmounts = [500, 1000, 2000, 5000]


// Sound effects
const beepSound = new Howl({
    src: ['/sounds/beep.mp3']
})

const successSound = new Howl({
    src: ['/sounds/success.mp3']
})

// State
const searchQuery = ref('')
const searchResults = ref([])
const showSearchResults = ref(false)
const cartItems = ref([])
const selectedCustomer = ref(null)
const paymentMethod = ref('cash')
const selectedBankAccount = ref(null)
const transactionId = ref('')
const amountPaid = ref(0)
const note = ref('')
const showSuccessModal = ref(false)
const lastSale = ref(null)
const searchInput = ref(null)
const taxRate = ref(5) // 5% tax rate
const discount = ref(0)
const change = ref(0)

// Computed Properties
const cartSummary = computed(() => {
    const subtotal = cartItems.value.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)
    const tax = (subtotal * taxRate.value) / 100
    const total = subtotal + tax - discount.value
    return { subtotal, tax, total }
})

const canProcessSale = computed(() => {
    return cartItems.value.length > 0 && amountPaid.value >= cartSummary.value.total
})

// Methods
const getPaymentIcon = (methodId) => {
    const icons = {
        cash: CurrencyDollarIcon,
        card: CreditCardIcon,
        bank: BanknotesIcon,
        mobile_banking: DevicePhoneMobileIcon
    }
    return icons[methodId]
}

// Fetch products
const fetchProducts = async () => {
    try {
        const response = await axios.get('/admin/pos/products')
        products.value = response.data
    } catch (error) {
        console.error('Error fetching products:', error)
    }
}

const searchProducts = async () => {
    if (!searchQuery.value) {
        showSearchResults.value = false
        return
    }

    try {
        const response = await axios.get('/admin/pos/search-products', {
            params: { search: searchQuery.value }
        })
        searchResults.value = response.data
        showSearchResults.value = true
    } catch (error) {
        console.error('Error searching products:', error)
    }
}

const filterByCategory = async (categoryId) => {
    try {
        const response = await axios.get('/admin/pos/products-by-category', {
            params: { category_id: categoryId }
        });
        products.value = response.data;
        searchQuery.value = ''; // Clear search when filtering by category
        showSearchResults.value = false;
    } catch (error) {
        console.error('Error filtering products:', error);
    }
}

const toggleHoldOrders = () => {
    // Implement hold orders functionality
    console.log('Hold orders clicked')
}

const toggleRecentTransactions = () => {
    // Implement recent transactions functionality
    console.log('Recent transactions clicked')
}

const addToCart = (product) => {
    const existingItem = cartItems.value.find(item => item.product_id === product.id)

    if (existingItem) {
        existingItem.quantity++
    } else {
        cartItems.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            unit_price: Number(product.selling_price),
            quantity: 1
        })
    }

    beepSound.play()
    searchQuery.value = ''
    showSearchResults.value = false
    updateCart()
}

// Format number for BDT currency
const formatNumber = (value) => {
    const num = Number(value);
    return isNaN(num) ? '0.00' : num.toLocaleString('bn-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

const removeItem = (index) => {
    cartItems.value.splice(index, 1)
    updateCart()
}

const incrementQuantity = (index) => {
    cartItems.value[index].quantity++
    updateCart()
}

const decrementQuantity = (index) => {
    if (cartItems.value[index].quantity > 1) {
        cartItems.value[index].quantity--
        updateCart()
    }
}

const updateCart = () => {
    calculateChange()
}

const calculateChange = () => {
    change.value = Math.max(0, amountPaid.value - cartSummary.value.total)
}

const setQuickAmount = (amount) => {
    amountPaid.value = amount
    calculateChange()
}

const resetCart = () => {
    cartItems.value = []
    selectedCustomer.value = null
    paymentMethod.value = 'cash'
    selectedBankAccount.value = null
    transactionId.value = ''
    amountPaid.value = 0
    note.value = ''
    discount.value = 0
    change.value = 0
    searchInput.value?.focus()
}

const processSale = () => {
    form.customer_id = selectedCustomer.value
    form.items = cartItems.value
    form.subtotal = cartSummary.value.subtotal
    form.tax = cartSummary.value.tax
    form.discount = discount.value
    form.total = cartSummary.value.total
    form.paid = amountPaid.value
    form.payment_method = paymentMethod.value
    form.bank_account_id = selectedBankAccount.value
    form.transaction_id = transactionId.value
    form.note = note.value

    form.post(route('admin.pos.store'), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.sale) {
                lastSale.value = page.props.flash.sale
            }
            successSound.play()
            showSuccessModal.value = true
        },
        onError: (errors) => {
            console.error('Error processing sale:', errors)
        }
    })
}

const closeSuccessModal = () => {
    showSuccessModal.value = false
    resetCart()
}

const printReceipt = () => {
    if (lastSale.value && lastSale.value.id) {
        const url = `/admin/pos/print-receipt/${lastSale.value.id}`;
        window.open(url, '_blank');
    }
}

// Lifecycle Hooks
onMounted(() => {
    fetchProducts()
    searchInput.value?.focus()

    // Barcode scanner logic
    let barcodeBuffer = ''
    let lastKeyTime = 0
    const BARCODE_DELAY = 20

    document.addEventListener('keydown', (e) => {
        const currentTime = new Date().getTime()

        if (currentTime - lastKeyTime > BARCODE_DELAY) {
            barcodeBuffer = ''
        }

        if (e.key === 'Enter' && barcodeBuffer) {
            searchQuery.value = barcodeBuffer
            searchProducts()
            barcodeBuffer = ''
        } else {
            barcodeBuffer += e.key
        }

        lastKeyTime = currentTime
    })
})
</script>

<style>
.dark {
    color-scheme: dark;
}
</style>
