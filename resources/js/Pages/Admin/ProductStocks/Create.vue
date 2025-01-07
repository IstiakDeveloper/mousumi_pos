<template>

    <Head title="Stock Create" />
    <AdminLayout>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                Add Stock
            </h1>
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Product Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Product
                    </label>
                    <select v-model="selectedProduct"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                        <option value="">Select Product</option>
                        <option v-for="product in products" :key="product.id" :value="product">
                            {{ product.name }} (Current Stock: {{ product.current_stock }})
                        </option>
                    </select>
                </div>

                <!-- Current Stock Info -->
                <div v-if="selectedProduct" class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Current Stock:</span>
                            <span class="ml-2 font-medium">{{ selectedProduct.current_stock }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Last Unit Cost:</span>
                            <span class="ml-2 font-medium">৳{{ formatNumber(selectedProduct.last_unit_cost) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Quantity and Cost -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Quantity
                        </label>
                        <input type="number" v-model="form.quantity" min="1"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600"
                            @input="calculateUnitCost" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Total Cost
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">৳</span>
                            </div>
                            <input type="number" v-model="form.total_cost" min="0" step="0.01"
                                class="block w-full pl-7 rounded-md border-gray-300 dark:border-gray-600"
                                @input="calculateUnitCost" />
                        </div>
                    </div>
                </div>

                <!-- Unit Cost Display -->
                <div v-if="unitCost > 0" class="bg-blue-50 dark:bg-blue-900/50 p-4 rounded-lg">
                    <p class="text-blue-700 dark:text-blue-300">
                        Unit Cost: ৳{{ formatNumber(unitCost) }}
                    </p>
                </div>


                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Bank Account
                    </label>
                    <select v-model="form.bank_account_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600">
                        <option value="">Select Bank Account</option>
                        <option v-for="account in bankAccounts" :key="account.id" :value="account.id"
                            :disabled="account.current_balance < form.total_cost">
                            {{ account.account_name }} ({{ account.bank_name }}) - Balance: ৳{{
                                formatNumber(account.current_balance) }}
                        </option>
                    </select>
                </div>

                <!-- Bank Balance Warning -->
                <div v-if="selectedBank && form.total_cost > selectedBank.current_balance"
                    class="mt-2 text-red-600 text-sm">
                    Insufficient balance in selected bank account
                </div>

                <!-- Note -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Note
                    </label>
                    <textarea v-model="form.note" rows="2"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                        Save Stock Entry
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    products: Array,
    bankAccounts: Array

});

const selectedProduct = ref(null);
const form = useForm({
    product_id: '',
    quantity: '',
    total_cost: '',
    note: '',
    bank_account_id: ''
});


const selectedBank = computed(() => {
    return props.bankAccounts.find(account => account.id === form.bank_account_id);
});

const unitCost = computed(() => {
    if (!form.quantity || !form.total_cost) return 0;
    return form.total_cost / form.quantity;
});

const calculateUnitCost = () => {
    if (selectedProduct.value) {
        form.product_id = selectedProduct.value.id;
    }
};

watch(selectedProduct, (newProduct) => {
    if (newProduct) {
        form.product_id = newProduct.id;
    }
});

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
