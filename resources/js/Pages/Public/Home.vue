<template>
    <GuestLayout>
        <Head title="Shop" />

        <template #nav-right>
            <Link :href="route('login')"
                class="inline-flex items-center rounded-full bg-white px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-800">
                Login
            </Link>
            <button type="button" @click="cartOpen = true"
                class="relative inline-flex items-center rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600">
                Cart
                <span v-if="cartCount"
                    class="ml-2 inline-flex min-w-6 justify-center rounded-full bg-indigo-600 px-2 py-0.5 text-xs font-bold text-white">
                    {{ cartCount }}
                </span>
            </button>
        </template>

        <div class="mx-auto max-w-7xl">
            <!-- Toast -->
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                <div v-if="toast"
                    class="fixed right-4 top-20 z-[60] max-w-sm rounded-xl bg-gray-900 px-4 py-3 text-sm font-medium text-white shadow-lg">
                    {{ toast }}
                </div>
            </transition>

            <!-- Hero + Search -->
            <div class="rounded-3xl bg-white/70 p-6 shadow-sm ring-1 ring-gray-100 backdrop-blur dark:bg-gray-900/40 dark:ring-gray-800 sm:p-10">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-4xl">
                            Shop
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-400">
                            Add items to your cart and submit an order. Orders stay pending until approved by admin/manager.
                        </p>
                    </div>
                    <div class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto">
                        <input v-model="q" type="text" placeholder="Search products..."
                            class="w-full rounded-xl border-0 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 sm:w-96" />
                        <select v-model="categoryId"
                            class="w-full rounded-xl border-0 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700 sm:w-60">
                            <option value="">All categories</option>
                            <option v-for="c in categories" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Small responsive cards -->
            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-6">
                        <div v-for="p in products.data" :key="p.id"
                        class="group overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
                        <div class="aspect-square bg-gray-100 dark:bg-gray-800">
                            <img v-if="p.image_url || p.image" :src="p.image_url || `/storage/${p.image}`" :alt="p.name"
                                class="h-full w-full object-cover transition group-hover:scale-[1.02]" />
                            <div v-else class="flex h-full w-full items-center justify-center text-xs text-gray-400">
                                No image
                            </div>
                        </div>
                        <div class="p-3">
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">SKU: {{ p.sku }}</p>
                            <h3 class="mt-1 line-clamp-2 text-sm font-semibold text-gray-900 dark:text-white">
                                {{ p.name }}
                            </h3>
                            <div class="mt-2 flex items-center justify-between gap-2">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ formatCurrency(p.selling_price) }}</p>
                                <button type="button" @click="add(p)"
                                    :disabled="(p.available_quantity ?? 0) <= 0"
                                    class="inline-flex items-center justify-center rounded-xl px-3 py-1.5 text-xs font-semibold text-white"
                                    :class="(p.available_quantity ?? 0) > 0 ? 'bg-indigo-600 hover:bg-indigo-500' : 'bg-gray-400 cursor-not-allowed'">
                                    {{ (p.available_quantity ?? 0) > 0 ? 'Add' : 'Out' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-10 flex justify-center" v-if="products.links?.length">
                    <Pagination :links="products.links" />
                </div>
        </div>

        <!-- Cart drawer -->
        <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="cartOpen" class="fixed inset-0 z-50 bg-black/40" @click="cartOpen = false" />
        </transition>

        <transition enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-x-full" enter-to-class="translate-x-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-x-0" leave-to-class="translate-x-full">
            <aside v-if="cartOpen"
                class="fixed right-0 top-0 z-[55] h-full w-full max-w-md bg-white shadow-2xl dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Your cart</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ cartCount }} items</p>
                    </div>
                    <button class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                        @click="cartOpen = false">
                        ✕
                    </button>
                </div>

                <div class="h-[calc(100%-170px)] overflow-auto px-5 py-4">
                    <div v-if="localCartItems.length" class="space-y-3">
                        <div v-for="it in localCartItems" :key="it.product.id"
                            class="flex items-center gap-3 rounded-2xl border border-gray-200 p-3 dark:border-gray-800">
                            <div class="h-14 w-14 overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-800">
                                <img v-if="it.product.image_url || it.product.image" :src="it.product.image_url || `/storage/${it.product.image}`" class="h-full w-full object-cover" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ it.product.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatCurrency(it.product.selling_price) }}</p>
                            </div>
                            <div class="flex items-center gap-1">
                                <button class="h-8 w-8 rounded-lg bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
                                    @click="setQty(it.product.id, it.quantity - 1)">-</button>
                                <input type="number" min="1" :max="it.product.available_quantity || 1" v-model.number="it.quantity"
                                    class="w-14 rounded-lg border-0 py-1 text-center text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 dark:bg-gray-800 dark:text-gray-100 dark:ring-gray-700" />
                                <button class="h-8 w-8 rounded-lg bg-gray-100 text-gray-900 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
                                    @click="setQty(it.product.id, it.quantity + 1)">+</button>
                            </div>
                            <button class="ml-1 rounded-lg p-2 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20"
                                @click="remove(it.product.id)">
                                Remove
                            </button>
                        </div>
                    </div>
                    <div v-else class="rounded-2xl border border-gray-200 p-6 text-center text-sm text-gray-600 dark:border-gray-800 dark:text-gray-400">
                        Cart is empty.
                    </div>
                </div>

                <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ formatCurrency(cartSubtotal) }}</span>
                    </div>
                    <div class="mt-3 flex gap-2">
                        <button type="button" @click="updateCart"
                            class="flex-1 rounded-xl bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600">
                            Update
                        </button>
                        <Link :href="route('checkout')" class="flex-1 rounded-xl bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-indigo-500">
                            Submit
                        </Link>
                    </div>
                </div>
            </aside>
        </transition>
    </GuestLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import { formatCurrency } from '@/utils'

const props = defineProps({
    products: Object,
    categories: Array,
    query: Object,
    cartCount: Number,
    cart: Object,
})

const q = ref(props.query?.q || '')
const categoryId = ref(props.query?.category_id ? String(props.query.category_id) : '')
const cartOpen = ref(false)
const toast = ref('')
let searchTimer = null

const page = usePage()
watch(
    () => page.props.flash?.success,
    (val) => {
        if (!val) return
        toast.value = val
        setTimeout(() => (toast.value = ''), 2500)
    },
    { immediate: true }
)

const localCartItems = ref((props.cart?.items || []).map(i => ({ ...i })))
watch(
    () => props.cart?.items,
    (items) => {
        localCartItems.value = (items || []).map(i => ({ ...i }))
    }
)

const cartSubtotal = computed(() =>
    localCartItems.value.reduce((sum, it) => sum + (it.quantity * it.product.selling_price), 0)
)

watch(q, (val) => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get(route('home'), { q: val || '', category_id: categoryId.value || '' }, { preserveScroll: true, preserveState: true })
    }, 350)
})

watch(categoryId, () => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => {
        router.get(route('home'), { q: q.value || '', category_id: categoryId.value || '' }, { preserveScroll: true, preserveState: true })
    }, 150)
})

function add(product) {
    if ((product.available_quantity ?? 0) <= 0) {
        toast.value = 'Out of stock.'
        setTimeout(() => (toast.value = ''), 2500)
        return
    }
    router.post(route('cart.add'), { product_id: product.id, quantity: 1 }, { preserveScroll: true, preserveState: true })
    cartOpen.value = true
}

function setQty(productId, qty) {
    const it = localCartItems.value.find(x => x.product.id === productId)
    if (!it) return
    const max = Math.max(1, Number(it.product.available_quantity || 1))
    it.quantity = Math.max(1, Math.min(max, qty))
}

function updateCart() {
    router.post(route('cart.update'), {
        items: localCartItems.value.map(i => ({ product_id: i.product.id, quantity: i.quantity })),
    }, { preserveScroll: true, preserveState: true })
}

function remove(productId) {
    router.post(route('cart.remove'), { product_id: productId }, { preserveScroll: true, preserveState: true })
}
</script>

