<template>
    <GuestLayout>
        <Head title="Cart" />

        <div class="bg-white dark:bg-gray-900">
            <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Cart</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Review items before checkout.</p>
                    </div>
                    <Link :href="route('home')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                        Continue shopping
                    </Link>
                </div>

                <div v-if="items.length" class="mt-8 space-y-4">
                    <div v-for="item in localItems" :key="item.product.id"
                        class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="h-16 w-16 overflow-hidden rounded-xl bg-gray-100 dark:bg-gray-700">
                                <img v-if="item.product.image_url || item.product.image" :src="item.product.image_url || `/storage/${item.product.image}`" class="h-full w-full object-cover" />
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ item.product.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ item.product.sku }}</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(item.product.selling_price) }}</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-3 sm:justify-end">
                            <input type="number" min="1" max="999" v-model.number="item.quantity"
                                class="w-24 rounded-lg border-0 py-2 text-center text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700" />
                            <p class="w-32 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                {{ formatCurrency(item.quantity * item.product.selling_price) }}
                            </p>
                            <button type="button" @click="remove(item.product.id)"
                                class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500">
                                Remove
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Subtotal</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(computedSubtotal) }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="update"
                                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600">
                                Update cart
                            </button>
                            <Link :href="route('checkout')"
                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                Checkout
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-10 rounded-2xl border border-gray-200 bg-white p-10 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <p class="text-gray-700 dark:text-gray-300">Your cart is empty.</p>
                    <Link :href="route('home')"
                        class="mt-4 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        Browse products
                    </Link>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { formatCurrency } from '@/utils'

const props = defineProps({
    items: Array,
    subtotal: Number,
    cartCount: Number,
})

const localItems = ref(props.items.map(i => ({ ...i })))

const computedSubtotal = computed(() =>
    localItems.value.reduce((sum, i) => sum + (i.quantity * i.product.selling_price), 0)
)

function update() {
    router.post(route('cart.update'), {
        items: localItems.value.map(i => ({ product_id: i.product.id, quantity: i.quantity })),
    }, { preserveScroll: true })
}

function remove(productId) {
    router.post(route('cart.remove'), { product_id: productId }, { preserveScroll: true })
}
</script>

