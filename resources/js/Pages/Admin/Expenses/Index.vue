<template>
    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">
                        Expense Management
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Record and monitor business expenses with real-time balance tracking
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <PlusIcon class="w-5 h-5 mr-1.5" />
                    Record Expense
                </button>
            </div>
        </template>

        <div class="py-4 space-y-6">
            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Expenses -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Total Expenses
                            </p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(summary.totalExpenses) }}
                            </p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-xl text-red-600 dark:text-red-400">
                            <i class="fas fa-wallet text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        All-time business expenses
                    </div>
                </div>

                <!-- This Month -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                This Month
                            </p>
                            <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                {{ formatCurrency(summary.thisMonthExpenses) }}
                            </p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400">
                            <i class="fas fa-calendar-alt text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Current month's spending
                    </div>
                </div>

                <!-- Today -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Today
                            </p>
                            <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                                {{ formatCurrency(summary.todayExpenses) }}
                            </p>
                        </div>
                        <div class="p-3 bg-amber-50 dark:bg-amber-900/30 rounded-xl text-amber-600 dark:text-amber-400">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Spent today
                    </div>
                </div>

                <!-- Total Records / Filtered Total -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                {{ hasActiveFilters ? 'Filtered Total' : 'Total Entries' }}
                            </p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ hasActiveFilters ? formatCurrency(summary.filteredExpensesTotal) : `${summary.totalCount || 0} Records` }}
                            </p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <i class="fas fa-file-invoice text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ hasActiveFilters ? `${expenses.total || 0} matching entries` : 'Recorded transactions' }}
                    </div>
                </div>
            </div>

            <!-- Search & Filters Toolbar -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <!-- Search Input -->
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Search note, amount..."
                                class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                @input="debouncedFilter"
                            />
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Category</label>
                        <select
                            v-model="filterForm.category_id"
                            @change="applyFilters"
                            class="w-full py-2 px-3 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Categories</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Bank Account Filter -->
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Bank Account</label>
                        <select
                            v-model="filterForm.bank_id"
                            @change="applyFilters"
                            class="w-full py-2 px-3 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Accounts</option>
                            <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                                {{ bank.bank_name }} - {{ bank.account_number }}
                            </option>
                        </select>
                    </div>

                    <!-- From Date -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">From Date</label>
                        <DateInput
                            v-model="filterForm.from_date"
                            placeholder="From Date"
                            @change="applyFilters"
                            class="w-full py-1.5 px-3 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <!-- To Date -->
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">To Date</label>
                        <div class="flex items-center gap-2">
                            <DateInput
                                v-model="filterForm.to_date"
                                placeholder="To Date"
                                @change="applyFilters"
                                class="w-full py-1.5 px-3 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <button
                                v-if="hasActiveFilters"
                                @click="resetFilters"
                                title="Reset all filters"
                                class="p-2 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left">
                        <thead class="bg-gray-50/75 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Category
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Payment Account
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Description / Note
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th scope="col" class="py-3.5 pl-3 pr-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                            <tr
                                v-for="expense in expenses.data"
                                :key="expense.id"
                                class="hover:bg-gray-50/80 dark:hover:bg-gray-750 transition-colors"
                            >
                                <!-- Date -->
                                <td class="whitespace-nowrap py-3.5 pl-4 pr-3 text-xs font-medium text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-1.5">
                                        <i class="far fa-calendar text-gray-400 text-xs"></i>
                                        <span>{{ formatDate(expense.date) }}</span>
                                    </div>
                                </td>

                                <!-- Category -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                        <i class="fas fa-tag mr-1 text-[10px]"></i>
                                        {{ expense.category?.name || 'Uncategorized' }}
                                    </span>
                                </td>

                                <!-- Payment Account -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                    <div class="flex items-center gap-1.5">
                                        <i class="fas fa-university text-gray-400 text-xs"></i>
                                        <div>
                                            <span class="font-medium text-gray-800 dark:text-gray-200">
                                                {{ expense.bank_account?.bank_name }}
                                            </span>
                                            <span class="text-gray-400 text-[11px] block">
                                                {{ expense.bank_account?.account_number }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Description -->
                                <td class="px-3 py-3.5 text-xs text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                    {{ expense.description || '—' }}
                                </td>

                                <!-- Amount -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-right text-xs font-bold text-gray-900 dark:text-white">
                                    {{ formatCurrency(expense.amount) }}
                                </td>

                                <!-- Actions -->
                                <td class="whitespace-nowrap py-3.5 pl-3 pr-4 text-center text-xs font-medium">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            @click="editExpense(expense)"
                                            class="p-1.5 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 rounded hover:bg-indigo-50 dark:hover:bg-gray-700 transition"
                                            title="Edit Expense"
                                        >
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                        <button
                                            @click="confirmDelete(expense)"
                                            class="p-1.5 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 rounded hover:bg-red-50 dark:hover:bg-gray-700 transition"
                                            title="Delete Expense"
                                        >
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!expenses.data || expenses.data.length === 0">
                                <td colspan="6" class="py-12 text-center">
                                    <div class="max-w-sm mx-auto">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto text-gray-400 text-lg mb-3">
                                            <i class="fas fa-receipt"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">No expenses recorded</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ hasActiveFilters ? 'No expenses match the current filter criteria.' : 'Click "Record Expense" to create your first operational expense.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="expenses.links && expenses.links.length > 3" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                    <Pagination :links="expenses.links" />
                </div>
            </div>
        </div>

        <!-- Clean Create / Edit Modal (Essential fields only) -->
        <Modal :show="showModal" @close="closeModal" max-width="md">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                            <i :class="isEditing ? 'fas fa-pen' : 'fas fa-plus'" class="text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ isEditing ? 'Edit Expense' : 'Record New Expense' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Essential expense transaction information
                            </p>
                        </div>
                    </div>
                    <button
                        @click="closeModal"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 p-1.5 rounded-lg"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <!-- Category Selection -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Expense Category <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.expense_category_id"
                            required
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="" disabled>Select category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.expense_category_id" class="text-xs text-red-600 mt-1">
                            {{ form.errors.expense_category_id }}
                        </p>
                    </div>

                    <!-- Payment Account -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Payment Bank / Account <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.bank_account_id"
                            required
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="" disabled>Select bank account</option>
                            <option v-for="bank in bankAccounts" :key="bank.id" :value="bank.id">
                                {{ bank.bank_name }} - {{ bank.account_number }} (Balance: {{ formatCurrency(bank.current_balance) }})
                            </option>
                        </select>
                        <p v-if="form.errors.bank_account_id" class="text-xs text-red-600 mt-1">
                            {{ form.errors.bank_account_id }}
                        </p>
                    </div>

                    <!-- Amount -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Amount (৳) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">৳</span>
                            <input
                                v-model="form.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                placeholder="0.00"
                                class="w-full pl-8 pr-3 py-2 text-sm font-semibold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-red-600 mt-1">
                            {{ form.errors.amount }}
                        </p>
                    </div>

                    <!-- Date Field with Super Admin Control -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Date <span class="text-red-500">*</span>
                            </label>
                            <!-- Role Badge -->
                            <span
                                v-if="isSuperAdmin"
                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                            >
                                <i class="fas fa-crown text-[9px] mr-1 text-amber-500"></i> Super Admin (Editable)
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"
                            >
                                <i class="fas fa-lock text-[9px] mr-1 text-gray-400"></i> Fixed to Today
                            </span>
                        </div>

                        <!-- If Super Admin: Editable Date Picker -->
                        <div v-if="isSuperAdmin">
                            <DateInput
                                v-model="form.date"
                                required
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- If Non-Super-Admin: Fixed Date Display (Disabled) -->
                        <div v-else class="relative">
                            <DateInput
                                :model-value="form.date"
                                disabled
                                class="w-full text-sm rounded-lg border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                            />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-1">
                                <i class="fas fa-info-circle text-[10px]"></i> Date is locked to today. Only Super Admin can change date.
                            </p>
                        </div>
                        <p v-if="form.errors.date" class="text-xs text-red-600 mt-1">
                            {{ form.errors.date }}
                        </p>
                    </div>

                    <!-- Note / Description (Short essential remark) -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Description / Purpose
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Optional note about this expense..."
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <p v-if="form.errors.description" class="text-xs text-red-600 mt-1">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-650 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 rounded-lg shadow-sm transition"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin mr-1.5"></i>
                            {{ isEditing ? 'Update Expense' : 'Save Expense' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="sm">
            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-lg"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Delete Expense</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Are you sure you want to delete this expense of
                    <span class="font-bold text-gray-700 dark:text-gray-200">{{ formatCurrency(expenseToDelete?.amount) }}</span>?
                    This will adjust the account balance.
                </p>
                <div class="mt-5 flex justify-center gap-3">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="deleteExpenseConfirmed"
                        :disabled="isDeleting"
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm"
                    >
                        <i v-if="isDeleting" class="fas fa-spinner fa-spin mr-1.5"></i>
                        Confirm Delete
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { formatDate } from '@/utils'
import DateInput from '@/Components/DateInput.vue'

const props = defineProps({
    expenses: Object,
    categories: Array,
    bankAccounts: Array,
    filters: Object,
    summary: {
        type: Object,
        default: () => ({
            totalExpenses: 0,
            todayExpenses: 0,
            thisMonthExpenses: 0,
            totalCount: 0,
            filteredExpensesTotal: 0
        })
    }
})

const page = usePage()

// Super Admin Check
const isSuperAdmin = computed(() => {
    const auth = page.props.auth || {}
    const user = auth.user || {}
    const roleName = (user.role?.name || '').toLowerCase()
    const roleSlug = (user.role?.slug || '').toLowerCase()
    const userName = (user.name || '').toLowerCase()

    return Boolean(
        auth.is_super_admin ||
        user.is_super_admin ||
        roleSlug === 'super-admin' ||
        roleName.includes('super') ||
        userName.includes('super admin') ||
        user.id === 1
    )
})

// Current local date (YYYY-MM-DD)
const getTodayDate = () => {
    const today = new Date()
    const year = today.getFullYear()
    const month = String(today.getMonth() + 1).padStart(2, '0')
    const day = String(today.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

// Format Currency in Bangladeshi Taka
const formatCurrency = (amount) => {
    const num = Number(amount || 0)
    return `৳ ${num.toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`
}

// Modal and editing states
const showModal = ref(false)
const isEditing = ref(false)
const currentExpense = ref(null)

const showDeleteModal = ref(false)
const expenseToDelete = ref(null)
const isDeleting = ref(false)

// Filter state
const filterForm = reactive({
    search: props.filters?.search || '',
    category_id: props.filters?.category_id || '',
    bank_id: props.filters?.bank_id || '',
    from_date: props.filters?.from_date || '',
    to_date: props.filters?.to_date || ''
})

const hasActiveFilters = computed(() => {
    return Boolean(
        filterForm.search ||
        filterForm.category_id ||
        filterForm.bank_id ||
        filterForm.from_date ||
        filterForm.to_date
    )
})

let debounceTimer = null
const debouncedFilter = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        applyFilters()
    }, 350)
}

const applyFilters = () => {
    router.get(route('admin.expenses.index'), filterForm, {
        preserveState: true,
        preserveScroll: true
    })
}

const resetFilters = () => {
    filterForm.search = ''
    filterForm.category_id = ''
    filterForm.bank_id = ''
    filterForm.from_date = ''
    filterForm.to_date = ''
    applyFilters()
}

// Essential form fields only
const form = useForm({
    expense_category_id: '',
    bank_account_id: '',
    amount: '',
    description: '',
    date: getTodayDate()
})

const openCreateModal = () => {
    isEditing.value = false
    currentExpense.value = null
    form.clearErrors()
    form.reset()
    form.expense_category_id = props.categories.length > 0 ? props.categories[0].id : ''
    form.bank_account_id = props.bankAccounts.length > 0 ? props.bankAccounts[0].id : ''
    form.amount = ''
    form.description = ''
    form.date = getTodayDate()
    showModal.value = true
}

const editExpense = (expense) => {
    isEditing.value = true
    currentExpense.value = expense
    form.clearErrors()

    const rawDate = expense.date ? String(expense.date).substring(0, 10) : getTodayDate()

    form.expense_category_id = expense.expense_category_id
    form.bank_account_id = expense.bank_account_id
    form.amount = expense.amount
    form.description = expense.description || ''
    // If super admin, allow date to be edited; if not super admin, keep the original expense date
    form.date = rawDate
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    form.reset()
    currentExpense.value = null
}

const submitForm = () => {
    // If not super admin, ensure date is today's date for new expenses
    if (!isSuperAdmin.value && !isEditing.value) {
        form.date = getTodayDate()
    }

    if (isEditing.value) {
        form.put(route('admin.expenses.update', currentExpense.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    } else {
        form.post(route('admin.expenses.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    }
}

const confirmDelete = (expense) => {
    expenseToDelete.value = expense
    showDeleteModal.value = true
}

const deleteExpenseConfirmed = () => {
    if (!expenseToDelete.value) return
    isDeleting.value = true

    router.delete(route('admin.expenses.destroy', expenseToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            expenseToDelete.value = null
            isDeleting.value = false
        },
        onError: () => {
            isDeleting.value = false
        }
    })
}
</script>
