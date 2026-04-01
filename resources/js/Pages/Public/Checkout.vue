<template>
    <GuestLayout>
        <Head title="Checkout" />

        <div class="bg-white dark:bg-gray-900">
            <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex items-end justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Checkout</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Select a customer and submit. Your order will be pending until approved.
                        </p>
                    </div>
                    <Link :href="route('home')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">
                        Back to shop
                    </Link>
                </div>

                <div class="mt-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <form @submit.prevent="submit">
                        <label class="block text-sm font-semibold text-gray-900 dark:text-white">Customer</label>
                        <SearchableSelect
                            v-model="form.customer_id"
                            class="mt-2"
                            :options="customerOptions"
                            label-key="label"
                            value-key="id"
                            description-key="description"
                            placeholder="Search & select customer..."
                            :clearable="true"
                        />
                        <div v-if="form.errors.customer_id" class="mt-1 text-sm text-rose-600">{{ form.errors.customer_id }}</div>

                        <label class="mt-6 block text-sm font-semibold text-gray-900 dark:text-white">Note (optional)</label>
                        <textarea v-model="form.note" rows="3"
                            class="mt-2 block w-full rounded-lg border-0 p-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700 sm:text-sm"
                            placeholder="Any note for the manager/admin..." />

                        <div class="mt-6 flex flex-col gap-2 sm:flex-row sm:justify-end">
                            <Link :href="route('home')"
                                class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-900 dark:text-gray-100 dark:ring-gray-700 dark:hover:bg-gray-800">
                                Cancel
                            </Link>
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                                Submit order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'

const props = defineProps({
    customers: Array,
    cartCount: Number,
})

const customerOptions = computed(() =>
    (props.customers || []).map(c => ({
        id: c.id,
        label: `${c.name} (${c.phone})${c.branch_code || c.branch_name ? ` • ${c.branch_name || ''} ${c.branch_code ? `(${c.branch_code})` : ''}` : ''}`,
        description: `Balance: ${c.balance} • Limit: ${c.credit_limit}${c.branch_code || c.branch_name ? ` • Branch: ${c.branch_name || '-'} (${c.branch_code || '-'})` : ''}`,
        metaRight: c.branch_code || '',
        metaRightSub: c.branch_name || '',
    }))
)

const form = useForm({
    customer_id: '',
    note: '',
})

function submit() {
    form.post(route('checkout.submit'))
}
</script>

