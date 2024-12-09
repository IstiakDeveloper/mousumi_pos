<template>
    <AdminLayout>
        <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">
                Add Stock
            </h1>
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Product Dropdown -->
                <div>
                    <label
                        for="product_id"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Product
                    </label>
                    <select
                        v-model="form.product_id"
                        id="product_id"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    >
                        <option value="" disabled>Select a product</option>
                        <option
                            v-for="product in products"
                            :key="product.id"
                            :value="product.id"
                        >
                            {{ product.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.product_id" class="text-sm text-red-600 mt-1">
                        {{ form.errors.product_id }}
                    </p>
                </div>

                <!-- Quantity Input -->
                <div>
                    <label
                        for="quantity"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        Quantity
                    </label>
                    <input
                        type="number"
                        id="quantity"
                        v-model="form.quantity"
                        placeholder="Enter stock quantity"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    />
                    <p v-if="form.errors.quantity" class="text-sm text-red-600 mt-1">
                        {{ form.errors.quantity }}
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 transition"
                    >
                        Save
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

export default {
    props: {
        products: {
            type: Array,
            required: true,
        },
    },
    components: {
        AdminLayout,
    },
    setup() {
        const form = useForm({
            product_id: '',
            quantity: 0,
        });

        const submit = () => {
            form.post(route('admin.product-stocks.store'));
        };

        return { form, submit };
    },
};
</script>
