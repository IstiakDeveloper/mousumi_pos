<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center border-b border-gray-200 pb-4">
                <h2 class="text-2xl font-bold text-gray-900">Receipt & Payment Statement</h2>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <input type="date" v-model="filters.start_date" @change="filter"
                            class="form-input rounded-md border-gray-300 shadow-sm" />
                        <span class="text-gray-500">to</span>
                        <input type="date" v-model="filters.end_date" @change="filter"
                            class="form-input rounded-md border-gray-300 shadow-sm" />
                    </div>
                    <button @click="printReport"
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Print
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6 px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Receipt</h3>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="text-left px-4 py-2">Description</th>
                                <th class="text-right px-4 py-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 font-medium">Opening Cash On the Bank</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.opening_cash_on_bank || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">Sale Collection</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.sale_collection || 0) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-medium">Extra Income</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.extra_income || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">Fund Receive</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">
                                    {{ formatCurrency(receipt?.fund_receive || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-2">Total Receipt</td>
                                <td class="px-4 py-2 text-right text-green-600">
                                    {{ formatCurrency(receipt?.total || 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <div class="bg-gray-100 px-4 py-3 border-b">
                        <h3 class="text-lg font-semibold text-gray-800">Payment</h3>
                    </div>
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="text-left px-4 py-2">Description</th>
                                <th class="text-right px-4 py-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="px-4 py-2 font-medium">Purchase</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.purchase || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">Fund Refund</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.fund_refund || 0) }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-4 py-2 font-medium">Expenses</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.expenses || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-4 py-2 font-medium">Closing Cash at Bank</td>
                                <td class="px-4 py-2 text-right text-red-600 font-semibold">
                                    {{ formatCurrency(payment?.closing_cash_at_bank || 0) }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td class="px-4 py-2">Total Payment</td>
                                <td class="px-4 py-2 text-right text-red-600">
                                    {{ formatCurrency(payment?.total || 0) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script>
import { defineComponent } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { formatCurrency } from '@/Utils';

export default defineComponent({
    components: { AdminLayout },
    props: {
        filters: {
            type: Object,
            default: () => ({ start_date: null, end_date: null })
        },
        receipt: {
            type: Object,
            default: () => ({
                opening_cash_on_bank: 0,
                sale_collection: 0,
                extra_income: 0,
                fund_receive: 0,
                total: 0
            })
        },
        payment: {
            type: Object,
            default: () => ({
                purchase: 0,
                fund_refund: 0,
                expenses: 0,
                closing_cash_at_bank: 0,
                total: 0
            })
        },
    },
    methods: {
        formatCurrency,
        filter() {
            router.get(route('admin.reports.receipt-payment'), {
                start_date: this.filters.start_date,
                end_date: this.filters.end_date,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        },
        printReport() {
            window.print();
        },
    }
})
</script>
