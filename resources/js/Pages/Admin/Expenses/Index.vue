<template>
    <AdminLayout>


        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center justify-between bg-blue-100 p-4 rounded-lg">
                    <div>
                        <h3 class="text-lg font-semibold text-blue-800">Total Expenses</h3>
                        <p class="text-2xl font-bold text-blue-900">{{ formatAmount(summary.totalExpenses - summary.fixedAssetExpenses) }} </p>
                    </div>
                    <IconCurrencyDollar class="w-12 h-12 text-blue-600" />
                </div>
                <div class="flex items-center justify-between bg-green-100 p-4 rounded-lg">
                    <div>
                        <h3 class="text-lg font-semibold text-green-800">Fixed Asset Expenses</h3>
                        <p class="text-2xl font-bold text-green-900">{{ formatAmount(summary.fixedAssetExpenses) }}</p>
                    </div>
                    <IconBuildingFactory2 class="w-12 h-12 text-green-600" />
                </div>
            </div>
        </div>

        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Expenses
                </h2>
                <button @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Add Expense
                </button>
            </div>
        </template>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Category</label>
                    <select v-model="filters.category_id" @change="getExpenses" class="w-full rounded-lg">
                        <option value="">All Categories</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Bank Account</label>
                    <select v-model="filters.bank_id" @change="getExpenses" class="w-full rounded-lg">
                        <option value="">All Banks</option>
                        <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                            {{ bank.bank_name }} - {{ bank.account_number }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">From Date</label>
                    <input type="date" v-model="filters.from_date" @change="getExpenses" class="w-full rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">To Date</label>
                    <input type="date" v-model="filters.to_date" @change="getExpenses" class="w-full rounded-lg">
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Category</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Bank
                            Account
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                            Description
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Amount
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="expense in expenses.data" :key="expense.id">
                        <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(expense.date) }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ expense.category.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ expense.bank_account.bank_name }} - {{ expense.bank_account.account_number }}
                        </td>
                        <td class="px-6 py-4">{{ expense.description }}</td>
                        <td class="px-6 py-4 text-right font-medium">{{ formatAmount(expense.amount) }}</td>
                        <td class="px-6 py-4 text-right">
                            <button @click="editExpense(expense)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                Edit
                            </button>
                            <button @click="deleteExpense(expense)" class="text-red-600 hover:text-red-900">
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <Pagination :links="expenses.links" />
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium mb-4">
                    {{ isEditing ? 'Edit Expense' : 'Create Expense' }}
                </h3>
                <form @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Category</label>
                            <select v-model="form.expense_category_id" class="w-full rounded-lg">
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Bank Account</label>
                            <select v-model="form.bank_account_id" class="w-full rounded-lg">
                                <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                                    {{ bank.bank_name }} - {{ bank.account_number }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Amount</label>
                            <input type="number" v-model="form.amount" step="0.01" class="w-full rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full rounded-lg"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Reference No</label>
                            <input type="text" v-model="form.reference_no" class="w-full rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Date</label>
                            <input type="date" v-model="form.date" class="w-full rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Attachment</label>
                            <input type="file" @change="handleFileUpload" class="w-full">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 border rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            {{ isEditing ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'


const props = defineProps({
    expenses: Object,
    categories: Array,
    bankAccounts: Array,
    filters: Object,
    summary: {
        type: Object,
        default: () => ({
            totalExpenses: 0,
            fixedAssetExpenses: 0
        })
    }
})

const showModal = ref(false)
const isEditing = ref(false)
const currentExpense = ref(null)

const filters = reactive({
    expense_category_id: props.categories.length > 0 ? props.categories[0].id : '',
    bank_id: props.bankAccounts.length > 0 ? props.bankAccounts[0].id : '',
    from_date: props.filters.from_date || '',
    to_date: props.filters.to_date || ''
})

const form = reactive({
    expense_category_id: '',
    bank_account_id: '',
    amount: '',
    description: '',
    reference_no: '',
    date: '',
    attachment: null
})

const getExpenses = () => {
    router.get(route('admin.expenses.index'), filters, {
        preserveState: true,
        preserveScroll: true
    })
}

const openCreateModal = () => {
    isEditing.value = false
    resetForm()
    showModal.value = true
}

const editExpense = (expense) => {
    isEditing.value = true
    currentExpense.value = expense
    Object.assign(form, {
        expense_category_id: expense.expense_category_id,
        bank_account_id: expense.bank_account_id,
        amount: expense.amount,
        description: expense.description,
        reference_no: expense.reference_no,
        date: expense.date
    })
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    resetForm()
}

const resetForm = () => {
    Object.assign(form, {
        expense_category_id: props.categories.length > 0 ? props.categories[0].id : '',
        bank_account_id: props.bankAccounts.length > 0 ? props.bankAccounts[0].id : '',
        amount: '',
        description: '',
        reference_no: '',
        date: '',
        attachment: null
    })
    currentExpense.value = null
}

const handleFileUpload = (event) => {
    form.attachment = event.target.files[0]
}

const submitForm = () => {
    if (isEditing.value) {
        router.post(route('admin.expenses.update', currentExpense.value.id), {
            ...form,
            _method: 'PUT'
        }, {
            onSuccess: () => closeModal()
        })
    } else {
        router.post(route('admin.expenses.store'), form, {
            onSuccess: () => closeModal()
        })
    }
}

const deleteExpense = (expense) => {
    if (confirm('Are you sure you want to delete this expense?')) {
        router.delete(route('admin.expenses.destroy', expense.id))
    }
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const formatAmount = (amount) => {
    const number = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(amount)
    return `৳ ${number}`
}
</script>
