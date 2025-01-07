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
import { ExclamationTriangleIcon, XMarkIcon } from '@heroicons/vue/24/outline'
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

// Initialize form with default values
const form = useForm({
    items: props.sale.items || [],
    customer_id: props.sale.customer_id || '',
    subtotal: props.sale.subtotal || 0,
    discount: props.sale.discount || 0,
    total: props.sale.total || 0,
    paid: props.sale.paid || 0,
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
        return total + (parseFloat(item.quantity) * parseFloat(item.unit_price))
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
        existingItem.quantity = parseFloat(existingItem.quantity) + 1
        existingItem.subtotal = (existingItem.quantity * parseFloat(existingItem.unit_price)).toFixed(2)
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
            quantity: 1,
            unit_price: sellingPrice,
            subtotal: sellingPrice.toFixed(2)
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
    item.quantity = Math.max(1, parseInt(value) || 1)
    item.subtotal = (item.quantity * parseFloat(item.unit_price)).toFixed(2)
    updateTotals()
}

function updateUnitPrice(index, value) {
    const item = form.items[index]
    item.unit_price = Math.max(0, parseFloat(value) || 0)
    item.subtotal = (parseFloat(item.quantity) * item.unit_price).toFixed(2)
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
    form.put(route('admin.sales.update', props.sale.id), {
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
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Sale - {{ sale.invoice_no }}
                </h2>
                <div class="flex items-center space-x-4">
                    <Link :href="route('admin.sales.show', sale.id)"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
                        Back to Sale
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <form @submit.prevent="confirmUpdate" class="p-6 space-y-6">
                        <!-- Customer Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="customer" value="Customer" />
                                <SelectInput
                                    id="customer"
                                    v-model="form.customer_id"
                                    class="mt-1 block w-full"
                                    required
                                >
                                    <option value="">Select Customer</option>
                                    <option
                                        v-for="customer in customers"
                                        :key="customer.id"
                                        :value="customer.id"
                                    >
                                        {{ customer.name }} - {{ customer.phone || 'No phone' }}
                                    </option>
                                </SelectInput>
                                <InputError :message="form.errors.customer_id" class="mt-2" />
                            </div>

                            <div v-if="selectedCustomer" class="bg-gray-50 p-4 rounded-lg">
                                <h3 class="font-medium text-gray-900">Customer Details</h3>
                                <dl class="mt-2 text-sm text-gray-600 grid grid-cols-2 gap-2">
                                    <dt>Name:</dt>
                                    <dd>{{ selectedCustomer.name }}</dd>
                                    <dt>Phone:</dt>
                                    <dd>{{ selectedCustomer.phone || 'N/A' }}</dd>
                                    <dt>Balance:</dt>
                                    <dd>{{ Number(selectedCustomer.balance).toFixed(2) }}</dd>
                                    <dt>Credit Limit:</dt>
                                    <dd>{{ Number(selectedCustomer.credit_limit).toFixed(2) }}</dd>
                                </dl>
                            </div>
                        </div>

                        <!-- Product Search -->
                        <div>
                            <InputLabel for="product_search" value="Search Products" />
                            <div class="relative">
                                <TextInput
                                    id="product_search"
                                    v-model="searchProduct"
                                    @input="searchProducts"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Type at least 2 characters to search products..."
                                    autocomplete="off"
                                />
                                <div v-if="loading" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                    <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>

                            <div v-if="searchResults.length > 0" class="mt-2 border rounded-md shadow-sm max-h-60 overflow-y-auto">
                                <div
                                    v-for="product in searchResults"
                                    :key="product.id"
                                    class="p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0 flex justify-between items-center"
                                    @click="addProduct(product)"
                                >
                                    <div>
                                        <div class="font-medium">{{ product.name }}</div>
                                        <div class="text-sm text-gray-600">SKU: {{ product.sku || 'N/A' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-medium">{{ Number(product.selling_price).toFixed(2) }}</div>
                                        <div class="text-sm text-gray-600">Stock: {{ product.stock || 0 }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else-if="searchProduct.length >= 2 && !loading" class="mt-2 p-4 text-center text-gray-500">
                                No products found
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in form.items" :key="index">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ item.product.name }}</div>
                                            <div class="text-sm text-gray-500">SKU: {{ item.product.sku }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <TextInput
                                                type="number"
                                                v-model="item.quantity"
                                                @input="updateQuantity(index, $event.target.value)"
                                                class="w-24 text-right"
                                                min="1"
                                            />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <TextInput
                                                type="number"
                                                v-model="item.unit_price"
                                                @input="updateUnitPrice(index, $event.target.value)"
                                                class="w-32 text-right"
                                                min="0"
                                                step="0.01"
                                            />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            {{ Number(item.subtotal).toFixed(2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <button
                                                type="button"
                                                class="text-red-600 hover:text-red-900"
                                                @click="removeItem(index)"
                                            >
                                            <XMarkIcon class="w-5 h-5" />
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="form.items.length === 0">
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            No items added yet. Search for products above to add them.
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-right font-medium">Subtotal:</td>
                                        <td class="px-6 py-4 text-right font-medium">{{ calculateSubtotal }}</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-right font-medium">
                                            Discount:
                                        </td>
                                        <td class="px-6 py-4">
                                            <TextInput
                                                type="number"
                                                v-model="form.discount"
                                                @input="updateTotals"
                                                class="w-32 text-right"
                                                min="0"
                                                step="0.01"
                                            />
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-right font-medium">Total:</td>
                                        <td class="px-6 py-4 text-right font-medium">{{ calculateTotal }}</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-right font-medium">
                                            Amount Paid:
                                        </td>
                                        <td class="px-6 py-4">
                                            <TextInput
                                                type="number"
                                                v-model="form.paid"
                                                @input="updateTotals"
                                                class="w-32 text-right"
                                                min="0"
                                                step="0.01"
                                            />
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-right font-medium">Due:</td>
                                        <td class="px-6 py-4 text-right font-medium">{{ calculateDue }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Bank Account Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="bank_account" value="Bank Account" />
                                <SelectInput
                                    id="bank_account"
                                    v-model="form.bank_account_id"
                                    class="mt-1 block w-full"
                                >
                                    <option value="">Select Bank Account</option>
                                    <option
                                        v-for="account in bankAccounts"
                                        :key="account.id"
                                        :value="account.id"
                                    >
                                        {{ account.bank_name }} - {{ account.account_number }}
                                    </option>
                                </SelectInput>
                                <InputError :message="form.errors.bank_account_id" class="mt-2" />
                            </div>

                            <!-- Note -->
                            <div>
                                <InputLabel for="note" value="Note" />
                                <TextArea
                                    id="note"
                                    v-model="form.note"
                                    class="mt-1 block w-full"
                                    rows="3"
                                />
                                <InputError :message="form.errors.note" class="mt-2" />
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4">
                            <Link
                                :href="route('admin.sales.show', sale.id)"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton
                                type="submit"
                                :class="{ 'opacity-25': form.processing }"
                                :disabled="form.processing"
                            >
                                Update Sale
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Credit Limit Warning Modal -->
        <TransitionRoot appear :show="showConfirmModal" as="template">
            <Dialog as="div" @close="showConfirmModal = false" class="relative z-10">
                <TransitionChild
                    as="template"
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <TransitionChild
                            as="template"
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                                    Credit Limit Warning
                                </DialogTitle>

                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">
                                        This sale will exceed the customer's credit limit. Are you sure you want to proceed?
                                    </p>
                                </div>

                                <div class="mt-4 flex justify-end space-x-4">
                                    <SecondaryButton @click="showConfirmModal = false">
                                        Cancel
                                    </SecondaryButton>
                                    <DangerButton @click="submitForm">
                                        Proceed Anyway
                                    </DangerButton>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AdminLayout>
</template>
