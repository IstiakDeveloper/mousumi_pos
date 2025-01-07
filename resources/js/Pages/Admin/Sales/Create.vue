<template>
    <Head title="Sale Create" />
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Create Sale
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Customer</label>
                                <select v-model="form.customer_id"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Customer</option>
                                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                                        {{ customer.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Items</label>
                                <div v-for="(item, index) in form.items" :key="index" class="mt-2">
                                    <select v-model="item.product_id"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select Product</option>
                                        <option v-for="product in products" :key="product.id" :value="product.id">
                                            {{ product.name }}
                                        </option>
                                    </select>
                                    <input v-model="item.quantity" type="number"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Quantity">
                                    <input v-model="item.unit_price" type="number"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Unit Price">
                                </div>
                                <button @click.prevent="addItem" type="button"
                                    class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-md">Add Item</button>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Tax</label>
                                <input v-model="form.tax" type="number"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Tax">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Discount</label>
                                <input v-model="form.discount" type="number"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Discount">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Payments</label>
                                <div v-for="(payment, index) in form.payments" :key="index" class="mt-2">
                                    <input v-model="payment.amount" type="number"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Amount">
                                    <select v-model="payment.payment_method"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Select Payment Method</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="bank">Bank</option>
                                        <option value="mobile_banking">Mobile Banking</option>
                                    </select>
                                    <input v-model="payment.transaction_id" type="text"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Transaction ID">
                                    <textarea v-model="payment.note"
                                        class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Note"></textarea>
                                </div>
                                <button @click.prevent="addPayment" type="button"
                                    class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-md">Add Payment</button>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Note</label>
                                <textarea v-model="form.note"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Note"></textarea>
                            </div>

                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Create</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>

import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

export default {
    components: {
        AdminLayout,
    },
    props: {
        customers: Array,
        products: Array,
    },
    data() {
        return {
            form: {
                customer_id: '',
                items: [],
                tax: 0,
                discount: 0,
                payments: [],
                note: '',
            },
        }
    },
    methods: {
        addItem() {
            this.form.items.push({
                product_id: '',
                quantity: 1,
                unit_price: 0,
            })
        },
        addPayment() {
            this.form.payments.push({
                amount: 0,
                payment_method: '',
                transaction_id: '',
                note: '',
            })
        },
        submit() {
            this.$inertia.post(route('admin.sales.store'), this.form)
        },
    },
}
</script>
