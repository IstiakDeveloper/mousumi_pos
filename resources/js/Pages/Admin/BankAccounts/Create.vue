<template>

    <Head title="Bank Create" />

    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Create Bank Account
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label for="account_name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Account Name
                                </label>
                                <input type="text" id="account_name" v-model="form.account_name"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                    required />
                            </div>

                            <div class="mb-4">
                                <label for="account_number"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Account Number
                                </label>
                                <input type="text" id="account_number" v-model="form.account_number"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                    required />
                            </div>

                            <div class="mb-4">
                                <label for="bank_name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Bank Name
                                </label>
                                <input type="text" id="bank_name" v-model="form.bank_name"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                    required />
                            </div>

                            <div class="mb-4">
                                <label for="branch_name"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Branch Name
                                </label>
                                <input type="text" id="branch_name" v-model="form.branch_name"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md focus:ring-indigo-500 focus:border-indigo-500" />
                            </div>

                            <div class="mb-4">
                                <label for="opening_balance"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Opening Balance
                                </label>
                                <input type="number" id="opening_balance" v-model="form.opening_balance"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                    required />
                            </div>

                            <div class="mb-4">
                                <label for="current_balance"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Current Balance
                                </label>
                                <input type="number" id="current_balance" v-model="form.current_balance"
                                    class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                                    required />
                            </div>

                            <div class="mb-4">
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Status
                                </label>
                                <select id="status" v-model="form.status"
                                    class="mt-1 block w-full py-2 px-3 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-200 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option :value="true">Active</option>
                                    <option :value="false">Inactive</option>
                                </select>
                            </div>

                            <button type="submit"
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Create
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>


<script>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3';

export default {
    components: {
        AdminLayout,
    },
    data() {
        return {
            form: {
                account_name: '',
                account_number: '',
                bank_name: '',
                branch_name: '',
                opening_balance: 0,
                current_balance: 0,
                status: true,
            },
        }
    },
    methods: {
        submit() {
            this.$inertia.post(route('admin.bank-accounts.store'), this.form)
                .then(() => {
                    this.form = {
                        account_name: '',
                        account_number: '',
                        bank_name: '',
                        branch_name: '',
                        opening_balance: 0,
                        current_balance: 0,
                        status: true,
                    }
                })
        },
    },
}
</script>
