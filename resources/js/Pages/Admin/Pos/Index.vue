<template>
    <AdminLayout title="Point of Sale">
        <div class="flex flex-col h-screen overflow-hidden bg-gray-50 dark:bg-gray-900">
            <!-- Compact Header -->
            <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 px-4 py-1.5">
                <div class="flex items-center justify-between">
                    <h1 class="text-sm font-semibold text-gray-900 dark:text-white">POS</h1>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ new Date().toLocaleString('en-BD') }}
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex flex-1 min-h-0">
                <!-- Left Panel - Products & Cart (65%) -->
                <div class="flex flex-col w-2/3 min-w-0 bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <!-- Search Section -->
                    <div class="p-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                        <PosSearch
                            :categories="categories"
                            :selected-category="selectedCategory"
                            @search="handleSearch"
                            @filter="handleCategoryFilter"
                            @add-scanned-product="handleScannedProduct"
                        />
                    </div>

                    <!-- Products and Cart Container -->
                    <div class="flex flex-col flex-1 min-h-0">
                        <!-- Product Grid - 45% height -->
                        <div class="p-2 overflow-auto h-5/5">
                            <div class="mb-1">
                                <h2 class="mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Products</h2>
                                <div class="h-px bg-gradient-to-r from-blue-500 to-purple-500"></div>
                            </div>
                            <PosProductGrid
                                :products="displayProducts"
                                :loading="loading"
                                @add-to-cart="addToCart"
                            />
                        </div>

                        <!-- Cart Section - 55% height (INCREASED) -->
                        <div class="border-t border-gray-200 h-3/5 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                            <div class="flex flex-col h-full p-2">
                                <div class="flex items-center justify-between mb-2">
                                    <h2 class="text-xs font-medium text-gray-700 dark:text-gray-300">Cart</h2>
                                    <span class="bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-400 px-1.5 py-0.5 rounded text-xs">
                                        {{ cartItems.length }}
                                    </span>
                                </div>
                                <div class="flex-1 overflow-auto">
                                    <!-- Enhanced Cart Table -->
                                    <div class="bg-white border border-gray-200 rounded dark:bg-gray-800 dark:border-gray-700">
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th class="px-2 py-1 text-xs font-medium text-left text-gray-500 dark:text-gray-400">Item</th>
                                                    <th class="px-2 py-1 text-xs font-medium text-right text-gray-500 dark:text-gray-400">Price</th>
                                                    <th class="px-2 py-1 text-xs font-medium text-center text-gray-500 dark:text-gray-400">Qty</th>
                                                    <th class="px-2 py-1 text-xs font-medium text-right text-gray-500 dark:text-gray-400">Total</th>
                                                    <th class="px-1 py-1"></th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                                <tr v-for="(item, index) in cartItems" :key="index"
                                                    class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    <td class="px-2 py-1.5">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 w-6 h-6 mr-2 bg-gray-100 rounded dark:bg-gray-600">
                                                                <img v-if="item.image" :src="item.image" :alt="item.name"
                                                                     class="object-cover w-full h-full rounded" />
                                                            </div>
                                                            <div class="min-w-0">
                                                                <div class="text-xs font-medium text-gray-900 truncate dark:text-gray-200">
                                                                    {{ item.name }}
                                                                </div>
                                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                                    {{ item.sku }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-2 py-1.5 text-right text-xs text-gray-500 dark:text-gray-400">
                                                        ৳{{ formatNumber(item.unit_price) }}
                                                    </td>
                                                    <td class="px-2 py-1.5">
                                                        <div class="flex items-center justify-center space-x-1">
                                                            <button @click="decrementQuantity(index)"
                                                                class="flex items-center justify-center w-5 h-5 text-gray-500 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                                </svg>
                                                            </button>
                                                            <input type="number" v-model.number="item.quantity"
                                                                class="w-10 text-center border rounded text-xs p-0.5 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                                                min="1" @change="updateCartItemQuantity(index, item.quantity)" />
                                                            <button @click="incrementQuantity(index)"
                                                                class="flex items-center justify-center w-5 h-5 text-gray-500 rounded hover:bg-gray-100 dark:hover:bg-gray-600">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td class="px-2 py-1.5 text-right text-xs font-medium text-gray-900 dark:text-gray-200">
                                                        ৳{{ formatNumber(item.quantity * item.unit_price) }}
                                                    </td>
                                                    <td class="px-1 py-1.5 text-right">
                                                        <button @click="removeCartItem(index)"
                                                            class="text-red-500 hover:text-red-700 p-0.5">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr v-if="cartItems.length === 0">
                                                    <td colspan="5" class="px-4 py-6 text-xs text-center text-gray-500 dark:text-gray-400">
                                                        Cart is empty
                                                    </td>
                                                </tr>
                                            </tbody>
                                            <tfoot v-if="cartItems.length > 0" class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <td colspan="3" class="px-2 py-1 text-xs font-medium text-right text-gray-500 dark:text-gray-400">
                                                        Items: {{ cartItems.length }}
                                                    </td>
                                                    <td class="px-2 py-1 text-xs font-bold text-right text-gray-900 dark:text-gray-200">
                                                        ৳{{ formatNumber(cartSummary.subtotal) }}
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Payment (35%) -->
                <div class="flex flex-col w-1/3 bg-gradient-to-br from-slate-50 to-blue-50 dark:from-gray-800 dark:to-gray-900">
                    <!-- Payment Header -->
                    <div class="p-2 text-white bg-gradient-to-r from-blue-600 to-indigo-600">
                        <h2 class="text-xs font-semibold">Payment</h2>
                    </div>

                    <!-- Payment Content -->
                    <div class="p-2 space-y-2 overflow-auto flex-2">
                        <!-- Customer & Payment Method -->
                        <div class="grid grid-cols-1 gap-2">
                            <div class="p-2 bg-white rounded shadow-sm dark:bg-gray-800">
                                <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Customer</label>
                                <select v-model="selectedCustomer"
                                    class="w-full py-1 text-xs text-gray-900 bg-white border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                                    <option :value="null">Walk-in</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="p-2 bg-white rounded shadow-sm dark:bg-gray-800">
                                <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Payment</label>
                                <select v-model="selectedBankAccount"
                                    class="w-full py-1 text-xs text-gray-900 bg-white border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                                    <option :value="null">Select</option>
                                    <option v-for="account in bankAccounts" :key="account.id" :value="account.id">
                                        {{ account.account_name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="p-2 bg-white rounded shadow-sm dark:bg-gray-800">
                            <h3 class="mb-2 text-xs font-medium text-gray-900 dark:text-white">Summary</h3>

                            <div class="space-y-1 text-xs">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                                    <span class="font-medium">৳{{ formatNumber(cartSummary.subtotal) }}</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Discount</span>
                                    <input type="number" v-model.number="discount"
                                        class="w-16 text-right text-xs border-gray-300 dark:border-gray-600 rounded
                                               bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 p-0.5"
                                        min="0" step="0.01" placeholder="0" />
                                </div>

                                <div class="pt-1 mt-1 border-t">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-gray-900 dark:text-white">Total</span>
                                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">
                                            ৳{{ formatNumber(total) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Amount -->
                        <div class="p-2 border border-green-200 rounded shadow-sm bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 dark:border-green-800">
                            <div class="text-center">
                                <p class="text-xs text-green-800 dark:text-green-400 mb-0.5">Pay Amount</p>
                                <p class="text-sm font-bold text-green-600 dark:text-green-400">
                                    ৳{{ formatNumber(total) }}
                                </p>
                            </div>
                        </div>

                        <!-- Note -->
                        <div class="p-2 bg-white rounded shadow-sm dark:bg-gray-800">
                            <label class="block mb-1 text-xs font-medium text-gray-700 dark:text-gray-300">Note</label>
                            <textarea v-model="note" rows="2"
                                class="w-full p-1 text-xs text-gray-900 bg-white border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:ring-1 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Add note..."></textarea>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="p-2 space-y-1.5 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                        <button @click="resetCart"
                            class="w-full py-1.5 bg-gray-500 hover:bg-gray-600 text-white rounded text-xs font-medium
                                   transition-colors duration-200 flex items-center justify-center">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Reset
                        </button>

                        <button @click="processSale" :disabled="!canProcessSale"
                            class="flex items-center justify-center w-full py-2 text-xs font-semibold text-white transition-all duration-200 rounded shadow bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-500 disabled:cursor-not-allowed">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Complete Sale
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <PosSuccessModal
            v-if="showSuccessModal"
            :sale="lastSale"
            @close="closeSuccessModal"
            @print="printReceipt"
        />
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PosSearch from './components/PosSearch.vue'
import PosProductGrid from './components/PosProductGrid.vue'
import PosSuccessModal from './components/PosSuccessModal.vue'
import { Howl } from 'howler'

const props = defineProps({
    customers: Array,
    bankAccounts: Array,
    categories: Array
})

// State
const searchQuery = ref('')
const products = ref([])
const searchResults = ref([])
const cartItems = ref([])
const selectedCategory = ref(null)
const loading = ref(false)
const showSuccessModal = ref(false)
const lastSale = ref(null)

// Payment state
const selectedCustomer = ref(null)
const selectedBankAccount = ref(1)
const discount = ref(0)
const note = ref('')

// Computed
const displayProducts = computed(() => {
    return searchQuery.value ? searchResults.value : products.value
})

const cartSummary = computed(() => {
    const subtotal = cartItems.value.reduce((sum, item) =>
        sum + (item.quantity * item.unit_price), 0
    )
    return {
        subtotal,
        items: cartItems.value.length
    }
})

const total = computed(() => {
    return Math.max(0, cartSummary.value.subtotal - discount.value)
})

const canProcessSale = computed(() => {
    return cartItems.value.length > 0 && selectedBankAccount.value && total.value > 0
})

// Methods
const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const fetchProducts = async () => {
    loading.value = true
    try {
        const response = await axios.get(route('admin.pos.products'));
        products.value = response.data;
    } catch (error) {
        console.error('Error fetching products:', error);
    } finally {
        loading.value = false;
    }
};

const handleSearch = async (query) => {
    searchQuery.value = query;
    if (!query) {
        searchResults.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(route('admin.pos.search-products'), {
            params: { search: query }
        });
        searchResults.value = response.data;
    } catch (error) {
        console.error('Error searching products:', error);
    } finally {
        loading.value = false;
    }
};

const handleCategoryFilter = async (categoryId) => {
    selectedCategory.value = categoryId;
    loading.value = true;
    try {
        const response = await axios.get(route('admin.pos.products.by.category'), {
            params: { category_id: categoryId }
        });
        products.value = response.data;
        searchQuery.value = '';
    } catch (error) {
        console.error('Error filtering products:', error);
    } finally {
        loading.value = false;
    }
};

const addToCart = (product) => {
    const beepSound = new Howl({ src: ['/sounds/beep.mp3'] })
    beepSound.play()

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
}

const handleScannedProduct = (product) => {
    addToCart(product);
};

const updateCartItemQuantity = (index, quantity) => {
    if (quantity > 0) {
        cartItems.value[index].quantity = quantity
    }
}

const incrementQuantity = (index) => {
    cartItems.value[index].quantity++
}

const decrementQuantity = (index) => {
    if (cartItems.value[index].quantity > 1) {
        cartItems.value[index].quantity--
    }
}

const removeCartItem = (index) => {
    cartItems.value.splice(index, 1)
}

const resetCart = () => {
    cartItems.value = []
    selectedCategory.value = null
    searchQuery.value = ''
    discount.value = 0
    note.value = ''
}

const processSale = () => {
    if (!canProcessSale.value) return

    const saleData = {
        customer_id: selectedCustomer.value,
        items: cartItems.value,
        subtotal: cartSummary.value.subtotal,
        discount: discount.value,
        total: total.value,
        paid: total.value,
        due: 0,
        bank_account_id: selectedBankAccount.value,
        note: note.value
    }

    router.post(route('admin.pos.store'), saleData, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.sale) {
                lastSale.value = page.props.flash.sale;
                new Howl({ src: ['/sounds/success.mp3'] }).play();
                showSuccessModal.value = true;
            }
        }
    });
};

const closeSuccessModal = () => {
    showSuccessModal.value = false
    resetCart()
}

const printReceipt = (saleId) => {
    window.open(route('admin.pos.print-receipt', saleId), '_blank');
};

// Lifecycle
onMounted(() => {
    fetchProducts()
})
</script>

<style scoped>
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    @apply bg-gray-100 dark:bg-gray-800;
}

::-webkit-scrollbar-thumb {
    @apply bg-gray-400 dark:bg-gray-600 rounded-full;
}

::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-500 dark:bg-gray-500;
}
</style>
