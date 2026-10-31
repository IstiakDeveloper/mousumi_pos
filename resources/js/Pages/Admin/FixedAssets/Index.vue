<template>
    <AdminLayout>
        <template #header>
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">Fixed Assets</h2>
        </template>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <p class="text-sm text-gray-500">Purchase value</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatAmount(summary.purchase_value) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ summary.count }} items</p>
        </div>

        <form @submit.prevent="submit" class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Asset name</label>
                <input v-model="form.name" type="text" required class="w-full rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Item</label>
                <input v-model="form.item_name" type="text" required class="w-full rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Purchase date</label>
                <DateInput v-model="form.purchase_date" required class="w-full rounded-lg" />
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Price</label>
                <input v-model="form.purchase_price" type="number" min="0.01" step="0.01" required class="w-full rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Bank account</label>
                <select v-model="form.bank_account_id" required class="w-full rounded-lg">
                    <option value="">Select</option>
                    <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                        {{ bank.account_name }}
                    </option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg" :disabled="form.processing">
                    Save asset
                </button>
            </div>
            <p v-if="form.errors.item_name || form.errors.purchase_price" class="text-red-600 text-sm md:col-span-3">
                {{ form.errors.item_name || form.errors.purchase_price }}
            </p>
        </form>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase">Item</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase">Date</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template v-for="asset in assets" :key="asset.id">
                        <tr v-for="item in asset.items" :key="item.id">
                            <td class="px-4 py-3 text-sm">{{ asset.asset_code }}</td>
                            <td class="px-4 py-3 text-sm">{{ item.item_name }}</td>
                            <td class="px-4 py-3 text-sm">{{ formatDate(item.purchase_date) }}</td>
                            <td class="px-4 py-3 text-sm text-right">{{ formatAmount(item.current_value) }}</td>
                        </tr>
                    </template>
                    <tr v-if="assets.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-gray-500">No fixed assets yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { formatDate } from '@/utils'

const props = defineProps({
    assets: Array,
    bankAccounts: Array,
    nextCode: String,
    filters: Object,
    summary: Object,
})

const form = useForm({
    name: '',
    asset_code: props.nextCode,
    item_name: '',
    purchase_date: '',
    purchase_price: '',
    bank_account_id: props.bankAccounts[0]?.id ?? '',
    item_description: '',
})

const submit = () => {
    form.post(route('admin.fixed-assets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('name', 'item_name', 'purchase_date', 'purchase_price', 'item_description')
            form.asset_code = props.nextCode
        },
    })
}

const formatAmount = (amount) => Number(amount || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})

</script>
