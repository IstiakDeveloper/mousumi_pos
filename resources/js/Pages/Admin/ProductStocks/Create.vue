<template>

    <Head title="Stock Create" />
    <AdminLayout>
        <div class="min-h-screen py-6 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 px-8 py-6">
                        <h1 class="text-2xl font-bold text-white">
                            Add New Stock
                        </h1>
                        <p class="mt-1 text-sm text-blue-100">
                            Add inventory to your product stock
                        </p>
                    </div>

                    <!-- Form -->
                    <div class="p-8">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Product Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Product <span class="text-red-500">*</span>
                    </label>
                    <SearchableSelect
                        v-model="form.product_id"
                        :options="productsForSelect"
                        label-key="name"
                        value-key="id"
                        description-key="stockInfo"
                        placeholder="Search and select a product..."
                        :show-badge="!!selectedProductData"
                        :badge-text="selectedProductData?.stock_badge"
                        @change="onProductChange"
                    />
                    <p v-if="!form.product_id" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Type to search by product name or SKU
                    </p>
                </div>

                <!-- Current Stock Info -->
                <div v-if="selectedProductData" class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 p-5 rounded-xl border border-blue-200 dark:border-blue-800">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 dark:bg-blue-800 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">Current Stock</span>
                                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ selectedProductData.current_stock }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">Last Unit Cost</span>
                                <p class="text-lg font-bold text-gray-900 dark:text-gray-100">৳{{ formatNumber(selectedProductData.last_unit_cost) }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-purple-100 dark:bg-purple-800 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                            <div>
                                <span class="text-xs text-gray-600 dark:text-gray-400">SKU</span>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ selectedProductData.sku }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quantity, Cost and Date -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Quantity <span class="text-red-500">*</span>
                        </label>
                        <input type="number" v-model="form.quantity" min="1" step="1" required
                            placeholder="Enter quantity"
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Total Cost <span class="text-red-500">*</span>
                        </label>
                        <div class="relative rounded-lg shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-gray-500 sm:text-sm">৳</span>
                            </div>
                            <input type="number" v-model="form.total_cost" min="0.01" step="0.01" required
                                placeholder="0.00"
                                class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 pl-8 focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Stock Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" v-model="form.date" :max="new Date().toISOString().split('T')[0]" required
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <svg class="inline w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            Can be backdated
                        </p>
                    </div>
                </div>

                <!-- Unit Cost Display -->
                <div v-if="unitCost > 0" class="bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 border-l-4 border-emerald-500 p-4 rounded-lg shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-emerald-800 dark:text-emerald-300 font-medium">Calculated Unit Cost</p>
                            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">৳{{ formatNumber(unitCost) }}</p>
                        </div>
                    </div>
                </div>


                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Bank Account <span class="text-red-500">*</span>
                    </label>
                    <SearchableSelect
                        v-model="form.bank_account_id"
                        :options="bankAccountsForSelect"
                        label-key="account_name"
                        value-key="id"
                        description-key="bankInfo"
                        placeholder="Search and select a bank account..."
                        :show-badge="!!selectedBank"
                        :badge-text="selectedBank ? `৳${formatNumber(selectedBank.current_balance)}` : ''"
                        @change="onBankChange"
                    />
                    <p v-if="!form.bank_account_id" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Select an account with sufficient balance
                    </p>
                </div>

                <!-- Bank Balance Warning -->
                <div v-if="selectedBank && form.total_cost > selectedBank.current_balance"
                    class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-bold text-red-800 dark:text-red-300">Insufficient Balance</h4>
                            <p class="text-xs text-red-700 dark:text-red-400 mt-1">
                                The selected bank account has a balance of ৳{{ formatNumber(selectedBank.current_balance) }},
                                but you need ৳{{ formatNumber(form.total_cost) }}.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Note -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Note (Optional)
                    </label>
                    <textarea v-model="form.note" rows="3"
                        placeholder="Add any additional notes about this stock entry..."
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Link :href="route('admin.product-stocks.index')"
                        class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors">
                        Cancel
                    </Link>
                    <button type="submit" :disabled="form.processing || (selectedBank && form.total_cost > selectedBank.current_balance)"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-medium shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center gap-2">
                        <svg v-if="form.processing" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ form.processing ? 'Saving...' : 'Save Stock Entry' }}</span>
                    </button>
                </div>
            </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    products: Array,
    bankAccounts: Array
});

const form = useForm({
    product_id: '',
    quantity: '',
    total_cost: '',
    date: new Date().toISOString().split('T')[0], // Today's date
    note: '',
    bank_account_id: ''
});

// Transform products for searchable select
const productsForSelect = computed(() => {
    return props.products.map(product => ({
        ...product,
        stockInfo: `SKU: ${product.sku} | Stock: ${product.current_stock} | Last Cost: ৳${formatNumber(product.last_unit_cost)}`,
        stock_badge: `Stock: ${product.current_stock}`
    }));
});

// Transform bank accounts for searchable select
const bankAccountsForSelect = computed(() => {
    return props.bankAccounts.map(account => ({
        ...account,
        bankInfo: `${account.bank_name} | Account: ${account.account_number} | Balance: ৳${formatNumber(account.current_balance)}`
    }));
});

const selectedProductData = computed(() => {
    return props.products.find(p => p.id === form.product_id);
});

const selectedBank = computed(() => {
    return props.bankAccounts.find(account => account.id === form.bank_account_id);
});

const unitCost = computed(() => {
    if (!form.quantity || !form.total_cost) return 0;
    return form.total_cost / form.quantity;
});

const onProductChange = (product) => {
    if (product) {
        form.product_id = product.id;
    }
};

const onBankChange = (account) => {
    if (account) {
        form.bank_account_id = account.id;
    }
};

const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
};

const submit = () => {
    form.post(route('admin.product-stocks.store'));
};

</script>
