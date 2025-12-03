<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import TextArea from '@/Components/TextArea.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import InputError from '@/Components/InputError.vue'
import { ExclamationTriangleIcon, XMarkIcon, PlusIcon, UserIcon } from '@heroicons/vue/24/outline'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    sale: {
        type: Object,
        required: true
    },
    customers: {
        type: Array,
        required: true,
        default: () => []
    },
    bankAccounts: {
        type: Array,
        required: true,
        default: () => []
    }
})

// Initialize form with string values to avoid type errors
const form = useForm({
    items: props.sale.items?.map(item => ({
        ...item,
        quantity: String(item.quantity || 1),
        unit_price: String(item.unit_price || 0),
        subtotal: String(item.subtotal || 0),
        available_stock: item.available_stock || 0, // Track available stock
        max_allowed: item.available_stock || 0 // Maximum quantity allowed
    })) || [],
    customer_id: props.sale.customer_id || '',
    subtotal: String(props.sale.subtotal || 0),
    discount: String(props.sale.discount || 0),
    total: String(props.sale.total || 0),
    paid: String(props.sale.paid || 0),
    note: props.sale.note || '',
    bank_account_id: props.sale.bank_account_id || ''
})

// State management
const searchProduct = ref('')
const searchResults = ref([])
const loading = ref(false)
const showConfirmModal = ref(false)
const selectedCustomer = ref(null)
const debounceTimeout = ref(null)

// Computed properties
const calculateSubtotal = computed(() => {
    return form.items.reduce((total, item) => {
        return total + (parseFloat(item.quantity || 0) * parseFloat(item.unit_price || 0))
    }, 0).toFixed(2)
})

const calculateTotal = computed(() => {
    return (parseFloat(calculateSubtotal.value) - parseFloat(form.discount || 0)).toFixed(2)
})

const calculateDue = computed(() => {
    return (parseFloat(calculateTotal.value) - parseFloat(form.paid || 0)).toFixed(2)
})

const isOverCreditLimit = computed(() => {
    if (!selectedCustomer.value) return false
    const newDue = parseFloat(calculateDue.value)
    return (parseFloat(selectedCustomer.value.balance || 0) + newDue) > parseFloat(selectedCustomer.value.credit_limit || 0)
})

// Watchers
watch(() => form.customer_id, (newVal) => {
    if (newVal) {
        const customer = props.customers.find(c => c.id === parseInt(newVal))
        if (customer) {
            selectedCustomer.value = {
                ...customer,
                balance: parseFloat(customer.balance || 0),
                credit_limit: parseFloat(customer.credit_limit || 0)
            }
        } else {
            selectedCustomer.value = null
        }
    } else {
        selectedCustomer.value = null
    }
}, { immediate: true })

// Methods
function debounceSearch(func, wait) {
    if (debounceTimeout.value) clearTimeout(debounceTimeout.value)
    debounceTimeout.value = setTimeout(func, wait)
}

async function searchProducts() {
    if (searchProduct.value.length < 2) {
        searchResults.value = []
        return
    }

    debounceSearch(async () => {
        loading.value = true
        try {
            const response = await axios.get(route('api.products.search'), {
                params: { query: searchProduct.value }
            })
            searchResults.value = response.data.data || []
        } catch (error) {
            console.error('Error searching products:', error)
            searchResults.value = []
        } finally {
            loading.value = false
        }
    }, 300)
}

function addProduct(product) {
    const existingItem = form.items.find(item => item.product_id === product.id)

    if (existingItem) {
        const newQty = parseFloat(existingItem.quantity) + 1

        // Check stock limit
        if (newQty > existingItem.max_allowed) {
            alert(`⚠️ Only ${existingItem.max_allowed} units available for ${existingItem.product.name}`)
            return
        }

        existingItem.quantity = String(newQty)
        existingItem.subtotal = String((newQty * parseFloat(existingItem.unit_price)).toFixed(2))
    } else {
        const sellingPrice = parseFloat(product.selling_price || 0)
        form.items.push({
            product_id: product.id,
            product: {
                id: product.id,
                name: product.name,
                sku: product.sku,
                selling_price: sellingPrice
            },
            quantity: "1",
            unit_price: String(sellingPrice),
            subtotal: String(sellingPrice.toFixed(2)),
            available_stock: product.current_stock || 0,
            max_allowed: product.current_stock || 0
        })
    }

    searchProduct.value = ''
    searchResults.value = []
    updateTotals()
}

function removeItem(index) {
    form.items.splice(index, 1)
    updateTotals()
}

function updateQuantity(index, value) {
    const item = form.items[index]
    const quantity = Math.max(1, parseInt(value) || 1)

    // Check stock limit
    if (quantity > item.max_allowed) {
        alert(`⚠️ Only ${item.max_allowed} units available for ${item.product.name}`)
        item.quantity = String(item.max_allowed)
        item.subtotal = String((item.max_allowed * parseFloat(item.unit_price)).toFixed(2))
        updateTotals()
        return
    }

    item.quantity = String(quantity)
    item.subtotal = String((quantity * parseFloat(item.unit_price)).toFixed(2))
    updateTotals()
}

function updateUnitPrice(index, value) {
    const item = form.items[index]
    const unitPrice = Math.max(0, parseFloat(value) || 0)
    item.unit_price = String(unitPrice)
    item.subtotal = String((parseFloat(item.quantity) * unitPrice).toFixed(2))
    updateTotals()
}

function updateTotals() {
    form.subtotal = calculateSubtotal.value
    form.total = calculateTotal.value
    form.due = calculateDue.value
}

function confirmUpdate() {
    if (isOverCreditLimit.value) {
        showConfirmModal.value = true
        return
    }
    submitForm()
}

function submitForm() {
    // Convert string values to numbers for backend
    const formData = {
        ...form.data(),
        items: form.items.map(item => ({
            ...item,
            quantity: parseFloat(item.quantity),
            unit_price: parseFloat(item.unit_price),
            subtotal: parseFloat(item.subtotal)
        })),
        subtotal: parseFloat(form.subtotal),
        discount: parseFloat(form.discount),
        total: parseFloat(form.total),
        paid: parseFloat(form.paid)
    }

    form.transform(() => formData).put(route('admin.sales.update', props.sale.id), {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false
        },
    })
}

// Initialize component
onMounted(() => {
    if (form.customer_id) {
        const customer = props.customers.find(c => c.id === parseInt(form.customer_id))
        if (customer) {
            selectedCustomer.value = {
                ...customer,
                balance: parseFloat(customer.balance || 0),
                credit_limit: parseFloat(customer.credit_limit || 0)
            }
        }
    }
    updateTotals()
})
</script>

<template>
    <AdminLayout :title="`Edit Sale - ${sale.invoice_no}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Edit Sale</h2>
                    <p class="text-sm text-gray-600">Invoice: {{ sale.invoice_no }}</p>
                </div>
                <Link :href="route('admin.sales.show', sale.id)"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                ← Back to Sale
                </Link>
            </div>
        </template>

        <div class="px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="bg-white border border-gray-200 shadow-sm rounded-xl">
                <form @submit.prevent="confirmUpdate" class="p-6 space-y-8">

                    <!-- Customer Selection -->
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div class="space-y-2">
                            <InputLabel for="customer" value="Customer *" class="text-sm font-semibold text-gray-700" />
                            <SelectInput id="customer" v-model="form.customer_id"
                                class="block w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                required>
                                <option value="">Choose a customer</option>
                                <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                    {{ customer.name }} {{ customer.phone ? `- ${customer.phone}` : '' }}
                                </option>
                            </SelectInput>
                            <InputError :message="form.errors.customer_id" />
                        </div>

                        <div v-if="selectedCustomer"
                            class="p-4 border border-blue-200 rounded-lg bg-gradient-to-r from-blue-50 to-indigo-50">
                            <h3 class="flex items-center mb-3 font-semibold text-gray-900">
                                <span class="w-2 h-2 mr-2 bg-blue-500 rounded-full"></span>
                                Customer Info
                            </h3>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-gray-600">Name:</span>
                                    <span class="ml-1 font-medium text-gray-900">{{ selectedCustomer.name }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Phone:</span>
                                    <span class="ml-1 font-medium text-gray-900">{{ selectedCustomer.phone || 'N/A'
                                        }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Balance:</span>
                                    <span class="ml-1 font-medium text-gray-900">৳{{
                                        Number(selectedCustomer.balance).toFixed(2)
                                        }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Credit Limit:</span>
                                    <span class="ml-1 font-medium text-gray-900">৳{{
                                        Number(selectedCustomer.credit_limit).toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Product Search -->
                    <div class="space-y-4">
                        <InputLabel for="product_search" value="Add Products"
                            class="text-sm font-semibold text-gray-700" />
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <UserIcon class="w-5 h-5 text-gray-400" />
                            </div>
                            <TextInput id="product_search" v-model="searchProduct" @input="searchProducts" type="text"
                                class="block w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Search products by name or SKU (min 2 characters)..." autocomplete="off" />
                            <div v-if="loading" class="absolute transform -translate-y-1/2 right-3 top-1/2">
                                <div class="w-5 h-5 border-b-2 border-blue-500 rounded-full animate-spin"></div>
                            </div>
                        </div>

                        <!-- Search Results -->
                        <div v-if="searchResults.length > 0"
                            class="overflow-y-auto border border-gray-200 rounded-lg shadow-sm max-h-60">
                            <div v-for="product in searchResults" :key="product.id"
                                class="flex items-center justify-between p-4 transition-colors border-b cursor-pointer hover:bg-gray-50 last:border-b-0"
                                @click="addProduct(product)">
                                <div>
                                    <div class="font-medium text-gray-900">{{ product.name }}</div>
                                    <div class="text-sm text-gray-500">SKU: {{ product.sku || 'N/A' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-green-600">৳{{
                                        Number(product.selling_price).toFixed(2) }}
                                    </div>
                                    <div class="text-sm text-gray-500">Stock: {{ product.stock || 0 }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-else-if="searchProduct.length >= 2 && !loading"
                            class="p-4 text-center text-gray-500 border border-gray-200 rounded-lg">
                            No products found matching "{{ searchProduct }}"
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Sale Items</h3>

                        <div class="overflow-hidden border border-gray-200 rounded-lg">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                                Product</th>
                                            <th
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                                Quantity</th>
                                            <th
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                                Unit Price</th>
                                            <th
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">
                                                Subtotal</th>
                                            <th
                                                class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">
                                                Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ item.product.name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    SKU: {{ item.product.sku }} |
                                                    <span :class="item.max_allowed > 10 ? 'text-green-600' : 'text-orange-600'">
                                                        Available: {{ item.max_allowed }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <TextInput type="number" :model-value="item.quantity"
                                                    @input="updateQuantity(index, $event.target.value)"
                                                    class="w-20 text-center border-gray-300 rounded"
                                                    min="1" :max="item.max_allowed" />
                                                <div class="text-xs text-gray-500 mt-1">
                                                    Max: {{ item.max_allowed }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <TextInput type="number" :model-value="item.unit_price"
                                                    @input="updateUnitPrice(index, $event.target.value)"
                                                    class="w-24 text-center border-gray-300 rounded" min="0"
                                                    step="0.01" />
                                            </td>
                                            <td class="px-6 py-4 text-sm font-semibold text-right text-gray-900">
                                                ৳{{ Number(item.subtotal).toFixed(2) }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <button type="button"
                                                    class="p-1 text-red-600 transition-colors rounded hover:text-red-800 hover:bg-red-50"
                                                    @click="removeItem(index)">
                                                    <XMarkIcon class="w-5 h-5" />
                                                </button>
                                            </td>
                                        </tr>

                                        <tr v-if="form.items.length === 0">
                                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                                <div class="flex flex-col items-center">
                                                    <PlusIcon class="w-8 h-8 mb-2 text-gray-400" />
                                                    <p>No items added yet. Search for products above to add them.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Totals Section -->
                        <div class="p-6 border border-gray-200 rounded-lg bg-gray-50">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Left side - Calculations -->
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">Subtotal:</span>
                                        <span class="text-lg font-semibold text-gray-900">৳{{ calculateSubtotal
                                            }}</span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <label class="text-sm font-medium text-gray-700">Discount:</label>
                                        <TextInput type="number" v-model="form.discount" @input="updateTotals"
                                            class="w-32 text-right border-gray-300 rounded" min="0" step="0.01" />
                                    </div>

                                    <div class="flex items-center justify-between pt-4 border-t">
                                        <span class="text-lg font-bold text-gray-900">Total:</span>
                                        <span class="text-xl font-bold text-blue-600">৳{{ calculateTotal }}</span>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <label class="text-sm font-medium text-gray-700">Amount Paid:</label>
                                        <TextInput type="number" v-model="form.paid" @input="updateTotals"
                                            class="w-32 text-right border-gray-300 rounded" min="0" step="0.01" />
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-medium text-gray-700">Due:</span>
                                        <span class="text-lg font-semibold"
                                            :class="parseFloat(calculateDue) > 0 ? 'text-red-600' : 'text-green-600'">
                                            ৳{{ calculateDue }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Right side - Additional Info -->
                                <div class="space-y-4">
                                    <div>
                                        <InputLabel for="bank_account" value="Bank Account"
                                            class="text-sm font-medium text-gray-700" />
                                        <SelectInput id="bank_account" v-model="form.bank_account_id"
                                            class="block w-full mt-1 border-gray-300 rounded">
                                            <option value="">Select Bank Account</option>
                                            <option v-for="account in bankAccounts" :key="account.id"
                                                :value="account.id">
                                                {{ account.bank_name }} - {{ account.account_number }}
                                            </option>
                                        </SelectInput>
                                        <InputError :message="form.errors.bank_account_id" />
                                    </div>

                                    <div>
                                        <InputLabel for="note" value="Note" class="text-sm font-medium text-gray-700" />
                                        <TextArea id="note" v-model="form.note"
                                            class="block w-full mt-1 border-gray-300 rounded" rows="3"
                                            placeholder="Add any notes about this sale..." />
                                        <InputError :message="form.errors.note" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex justify-end pt-6 space-x-3 border-t border-gray-200">
                        <Link :href="route('admin.sales.show', sale.id)"
                            class="inline-flex items-center px-6 py-2 text-sm font-medium text-gray-700 transition-colors bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50">
                        Cancel
                        </Link>
                        <PrimaryButton type="submit" :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            :disabled="form.processing || form.items.length === 0"
                            class="inline-flex items-center px-6 py-2 font-medium text-white transition-colors bg-blue-600 rounded-lg shadow-sm hover:bg-blue-700">
                            <span v-if="form.processing" class="mr-2">
                                <div class="w-4 h-4 border-b-2 border-white rounded-full animate-spin"></div>
                            </span>
                            {{ form.processing ? 'Updating...' : 'Update Sale' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>

        <!-- Credit Limit Warning Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="px-4 pt-5 pb-4 bg-white sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-yellow-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                <ExclamationTriangleIcon class="w-6 h-6 text-yellow-600" />
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                    Credit Limit Warning
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        This sale will exceed the customer's credit limit. The customer's current
                                        balance plus
                                        this due amount will exceed their allowed credit limit. Are you sure you want to
                                        proceed?
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 py-3 bg-gray-50 sm:px-6 sm:flex sm:flex-row-reverse">
                        <DangerButton @click="submitForm"
                            class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white border border-transparent rounded-md shadow-sm sm:ml-3 sm:w-auto sm:text-sm">
                            Proceed Anyway
                        </DangerButton>
                        <SecondaryButton @click="showConfirmModal = false"
                            class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
