<template>
    <AdminLayout>
        <Head title="Pending Orders" />

        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <Alert
                    :show="activeAlert.show"
                    :type="activeAlert.type"
                    :title="activeAlert.title"
                    :message="activeAlert.message"
                    @close="clearFlash"
                />

                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Orders</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Pending orders can be approved (creates a Sale) or rejected.
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <div class="inline-flex rounded-xl bg-white p-1 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                            <button type="button" @click="setStatus('pending')" :class="tabClass('pending')">Pending</button>
                            <button type="button" @click="setStatus('approved')" :class="tabClass('approved')">Approved</button>
                            <button type="button" @click="setStatus('rejected')" :class="tabClass('rejected')">Rejected</button>
                        </div>
                        <div class="relative w-full sm:w-72">
                            <input v-model="q" @keydown.enter.prevent="applyFilters" type="text"
                                placeholder="Search order/customer..."
                                class="block w-full rounded-xl border-0 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700" />
                        </div>
                        <button type="button" @click="applyFilters" :disabled="isLoading"
                            class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                            <span v-if="isLoading && loadingAction === 'filter'" class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                Applying...
                            </span>
                            <span v-else>Apply</span>
                        </button>
                    </div>
                </div>

                <!-- Compact list for Approved / Rejected -->
                <div v-if="pendingSales.data.length && filters.status !== 'pending'" class="mt-8">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/40">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Order</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Customer</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        {{ filters.status === 'approved' ? 'Approved at' : 'Rejected at' }}
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Total</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="o in pendingSales.data" :key="o.id" class="hover:bg-gray-50/60 dark:hover:bg-white/5">
                                    <td class="px-4 py-3">
                                        <div class="text-[13px] font-semibold text-gray-900 dark:text-white">{{ o.public_order_no }}</div>
                                        <div class="text-[12px] text-gray-500 dark:text-gray-400">{{ o.date }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="o.customer" class="text-[13px] text-gray-900 dark:text-white">
                                            {{ o.customer.name }}
                                        </div>
                                        <div v-if="o.customer" class="text-[12px] text-gray-500 dark:text-gray-400">{{ o.customer.phone }}</div>
                                        <div v-else class="text-[13px] text-gray-500 dark:text-gray-400">—</div>
                                    </td>
                                    <td class="px-4 py-3 text-[13px] text-gray-700 dark:text-gray-300">
                                        {{ filters.status === 'approved' ? (o.approved_at || '—') : (o.rejected_at || '—') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-[13px] font-semibold text-gray-900 dark:text-white">
                                        {{ formatCurrency(o.total) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="inline-flex items-center justify-end gap-2">
                                            <button
                                                v-if="filters.status === 'approved' && o.sale_id"
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                                @click="printReceipt(o.sale_id)"
                                            >
                                                <i class="fas fa-print text-[11px]"></i>
                                                <span class="hidden sm:inline">Receipt</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Detailed cards for Pending -->
                <div v-else-if="pendingSales.data.length" class="mt-8 space-y-4">
                    <div v-for="o in pendingSales.data" :key="o.id"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ o.public_order_no }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ o.date }}</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">Customer:</span>
                                    <span v-if="o.customer">{{ o.customer.name }} ({{ o.customer.phone }})</span>
                                    <span v-else>—</span>
                                </p>
                                <p v-if="o.note" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">Note:</span> {{ o.note }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3 lg:flex-col lg:items-end">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white lg:text-right">
                                    Total: {{ formatCurrency(o.total) }}
                                </div>
                                <template v-if="filters.status === 'pending'">
                                    <div class="inline-flex items-center gap-2 whitespace-nowrap">
                                        <button type="button" @click="approve(o.id)" :disabled="isLoading"
                                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-60">
                                            <span v-if="isLoading && loadingAction === 'approve' && loadingId === o.id" class="inline-flex items-center gap-2">
                                                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                                </svg>
                                                <span class="hidden sm:inline">Approving...</span>
                                            </span>
                                            <span v-else class="inline-flex items-center gap-2">
                                                <i class="fas fa-check text-[11px]"></i>
                                                <span class="hidden sm:inline">Approve</span>
                                            </span>
                                        </button>

                                        <button type="button" @click="reject(o.id)" :disabled="isLoading"
                                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500 disabled:opacity-60">
                                            <span v-if="isLoading && loadingAction === 'reject' && loadingId === o.id" class="inline-flex items-center gap-2">
                                                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                                </svg>
                                                <span class="hidden sm:inline">Rejecting...</span>
                                            </span>
                                            <span v-else class="inline-flex items-center gap-2">
                                                <i class="fas fa-ban text-[11px]"></i>
                                                <span class="hidden sm:inline">Reject</span>
                                            </span>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-[700px] w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900/40">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 dark:text-gray-300">Product</th>
                                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Qty</th>
                                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Unit</th>
                                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Subtotal</th>
                                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700 dark:text-gray-300">Order actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(it, idx) in o.items" :key="idx">
                                        <td class="px-3 py-2 text-sm text-gray-900 dark:text-gray-100">
                                            {{ it.product.name }} <span class="text-xs text-gray-500">({{ it.product.sku }})</span>
                                        </td>
                                        <td class="px-3 py-2 text-right text-sm text-gray-700 dark:text-gray-300">
                                            <input
                                                v-if="filters.status === 'pending'"
                                                type="number"
                                                min="1"
                                                max="999"
                                                :value="it.quantity"
                                                class="w-20 rounded-lg border-0 py-1 text-right text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700"
                                                @change="updateItem(o.id, it.id, $event.target.value)"
                                            />
                                            <span v-else>{{ it.quantity }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-right text-sm text-gray-700 dark:text-gray-300">{{ formatCurrency(it.unit_price) }}</td>
                                        <td class="px-3 py-2 text-right text-sm font-medium text-gray-900 dark:text-gray-100">{{ formatCurrency(it.subtotal) }}</td>
                                        <td class="px-3 py-2 text-right">
                                            <div v-if="filters.status === 'pending'" class="inline-flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    class="text-sm font-semibold text-rose-600 hover:text-rose-500"
                                                    @click="removeItem(o.id, it.id)"
                                                >
                                                    Remove
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="filters.status === 'pending'" class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                                <input v-model="addSearch[o.id]" type="text" placeholder="Search product to add..."
                                    class="w-full sm:w-80 rounded-xl border-0 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700"
                                    @input="searchProducts(o.id)"
                                />
                                <div v-if="searchResults[o.id]?.length" class="relative w-full sm:w-80">
                                    <div class="absolute z-10 mt-2 w-full rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900">
                                        <button v-for="p in searchResults[o.id]" :key="p.id" type="button"
                                            class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                                            @click="addProductToOrder(o.id, p)"
                                        >
                                            {{ p.name }} ({{ p.sku }}) — {{ formatCurrency(p.selling_price) }} — Stock: {{ p.stock }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-10 rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-gray-700 dark:text-gray-300">No orders found.</p>
                </div>

                <div class="mt-8">
                    <Pagination :links="pendingSales.links" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import Alert from '@/Components/Alert.vue'
import { formatCurrency } from '@/utils'

const props = defineProps({
    pendingSales: Object,
    filters: Object,
})

const page = usePage()
const addSearch = ref({})
const searchResults = ref({})
let searchTimers = {}
const flashSuccess = computed(() => page.props.flash?.success || '')
const flashError = computed(() => page.props.flash?.error || '')
const pageError = computed(() => page.props.errors?.error || '')

const localSuccess = ref('')
const localError = ref('')
let localTimer = null
let dismissTimer = null
const dismissed = ref(false)
const isLoading = ref(false)
const loadingAction = ref('')
const loadingId = ref(null)

const q = ref(props.filters?.q || '')

const activeAlert = computed(() => {
    // Show only ONE message at a time (highest priority first)
    const errorMessage = localError.value || pageError.value || flashError.value
    if (errorMessage) {
        return { show: !dismissed.value, type: 'error', title: 'Error', message: errorMessage }
    }
    const successMessage = localSuccess.value || flashSuccess.value
    if (successMessage) {
        return { show: !dismissed.value, type: 'success', title: 'Success', message: successMessage }
    }
    return { show: false, type: 'info', title: '', message: '' }
})

function clearFlash() {
    dismissed.value = true
    localSuccess.value = ''
    localError.value = ''
    if (localTimer) {
        clearTimeout(localTimer)
        localTimer = null
    }
    if (dismissTimer) {
        clearTimeout(dismissTimer)
        dismissTimer = null
    }
}

// Auto-dismiss after 2 seconds for any message (including flash/page errors)
watch(
    () => [localError.value, pageError.value, flashError.value, localSuccess.value, flashSuccess.value],
    () => {
        dismissed.value = false
        if (dismissTimer) clearTimeout(dismissTimer)
        const hasMessage =
            !!(localError.value || pageError.value || flashError.value || localSuccess.value || flashSuccess.value)
        if (hasMessage) {
            dismissTimer = setTimeout(() => {
                dismissed.value = true
            }, 2000)
        }
    },
    { immediate: true }
)

function tabClass(status) {
    const active = (props.filters?.status || 'pending') === status
    return [
        'px-3 py-2 text-sm font-semibold rounded-lg transition',
        active
            ? 'bg-indigo-600 text-white shadow-sm'
            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700',
    ]
}

function setStatus(status) {
    router.get(route('admin.pending-sales.index'), { status, q: q.value }, { preserveScroll: true, preserveState: true })
}

function applyFilters() {
    isLoading.value = true
    loadingAction.value = 'filter'
    loadingId.value = null
    router.get(route('admin.pending-sales.index'), { status: props.filters?.status || 'pending', q: q.value }, { preserveScroll: true, preserveState: true })
    setTimeout(() => { isLoading.value = false; loadingAction.value = ''; }, 500)
}

function approve(id) {
    isLoading.value = true
    loadingAction.value = 'approve'
    loadingId.value = id
    router.post(
        route('admin.pending-sales.approve', id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                localSuccess.value = 'Approved successfully.'
                if (localTimer) clearTimeout(localTimer)
                localTimer = setTimeout(() => (localSuccess.value = ''), 8000)
                isLoading.value = false
                loadingAction.value = ''
                loadingId.value = null
                router.reload({ preserveScroll: true, preserveState: true })
            },
            onError: (errors) => {
                localError.value = errors?.error || 'Approve failed.'
                if (localTimer) clearTimeout(localTimer)
                localTimer = setTimeout(() => (localError.value = ''), 12000)
                isLoading.value = false
                loadingAction.value = ''
                loadingId.value = null
            },
        }
    )
}

function reject(id) {
    if (!confirm('Reject this order?')) return
    isLoading.value = true
    loadingAction.value = 'reject'
    loadingId.value = id
    router.post(
        route('admin.pending-sales.reject', id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                localSuccess.value = 'Rejected.'
                if (localTimer) clearTimeout(localTimer)
                localTimer = setTimeout(() => (localSuccess.value = ''), 8000)
                isLoading.value = false
                loadingAction.value = ''
                loadingId.value = null
                router.reload({ preserveScroll: true, preserveState: true })
            },
            onError: (errors) => {
                localError.value = errors?.error || 'Reject failed.'
                if (localTimer) clearTimeout(localTimer)
                localTimer = setTimeout(() => (localError.value = ''), 12000)
                isLoading.value = false
                loadingAction.value = ''
                loadingId.value = null
            },
        }
    )
}

function printReceipt(saleId) {
    window.open(route('admin.sales.print-receipt', saleId), '_blank')
}

function updateItem(orderId, itemId, qty) {
    router.post(route('admin.pending-sales.items.update', { pendingSale: orderId, item: itemId }), { quantity: Number(qty) }, { preserveScroll: true })
}

function removeItem(orderId, itemId) {
    if (!confirm('Remove this item from the order?')) return
    router.post(route('admin.pending-sales.items.remove', { pendingSale: orderId, item: itemId }), {}, { preserveScroll: true })
}

function searchProducts(orderId) {
    const term = (addSearch.value[orderId] || '').trim()
    if (!term) {
        searchResults.value[orderId] = []
        return
    }
    if (searchTimers[orderId]) clearTimeout(searchTimers[orderId])
    searchTimers[orderId] = setTimeout(async () => {
        const res = await fetch(`/admin/pos/search-products?search=${encodeURIComponent(term)}`)
        const data = await res.json()
        searchResults.value[orderId] = Array.isArray(data) ? data : []
    }, 250)
}

function addProductToOrder(orderId, product) {
    // default add 1 qty
    router.post(route('admin.pending-sales.items.add', { pendingSale: orderId }), { product_id: product.id, quantity: 1 }, { preserveScroll: true })
    addSearch.value[orderId] = ''
    searchResults.value[orderId] = []
}
</script>

