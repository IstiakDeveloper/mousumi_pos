<template>
    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">
                        Fixed Assets Management
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Record, monitor and maintain company fixed assets and equipment
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <PlusIcon class="w-5 h-5 mr-1.5" />
                    Record Fixed Asset
                </button>
            </div>
        </template>

        <div class="py-4 space-y-6">
            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Total Assets Count -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Total Asset Items
                            </p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ summary.count || 0 }} Items
                            </p>
                        </div>
                        <div class="p-3 bg-blue-50 dark:bg-blue-900/30 rounded-xl text-blue-600 dark:text-blue-400">
                            <i class="fas fa-boxes text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Registered company capital assets
                    </div>
                </div>

                <!-- Total Purchase Value -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Total Purchase Cost
                            </p>
                            <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                                {{ formatCurrency(summary.purchase_value) }}
                            </p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400">
                            <i class="fas fa-coins text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Historical purchase value
                    </div>
                </div>

                <!-- Active Assets Value -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Active Assets Value
                            </p>
                            <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ formatCurrency(summary.active_value) }}
                            </p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <i class="fas fa-shield-alt text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Current active asset valuation
                    </div>
                </div>
            </div>

            <!-- Filters Toolbar -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <!-- Search Input -->
                    <div class="lg:col-span-4">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Search asset code, name..."
                                class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                @input="debouncedFilter"
                            />
                        </div>
                    </div>

                    <!-- Bank Account Filter -->
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Payment Account</label>
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
                    <div class="lg:col-span-3">
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
                                title="Reset filters"
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
                                    Asset Code
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Asset Name / Item
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Purchase Date
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Payment Account
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Purchase Value
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="py-3.5 pl-3 pr-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                            <template v-for="asset in assets" :key="asset.id">
                                <tr
                                    v-for="item in asset.items"
                                    :key="item.id"
                                    class="hover:bg-gray-50/80 dark:hover:bg-gray-750 transition-colors"
                                >
                                    <!-- Asset Code -->
                                    <td class="whitespace-nowrap py-3.5 pl-4 pr-3 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-mono text-[11px]">
                                            <i class="fas fa-barcode mr-1 text-[10px]"></i>
                                            {{ asset.asset_code || '—' }}
                                        </span>
                                    </td>

                                    <!-- Asset Name / Item -->
                                    <td class="px-3 py-3.5 text-xs">
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ item.item_name || asset.name }}
                                        </div>
                                        <div v-if="item.description || asset.description" class="text-[11px] text-gray-400 dark:text-gray-500 truncate max-w-xs">
                                            {{ item.description || asset.description }}
                                        </div>
                                    </td>

                                    <!-- Purchase Date -->
                                    <td class="whitespace-nowrap px-3 py-3.5 text-xs text-gray-700 dark:text-gray-300">
                                        <div class="flex items-center gap-1.5">
                                            <i class="far fa-calendar text-gray-400 text-xs"></i>
                                            <span>{{ formatDate(item.purchase_date) }}</span>
                                        </div>
                                    </td>

                                    <!-- Bank Account -->
                                    <td class="whitespace-nowrap px-3 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-university text-gray-400 text-xs"></i>
                                            <span>{{ item.bank_account?.bank_name || '—' }}</span>
                                        </div>
                                    </td>

                                    <!-- Purchase Value -->
                                    <td class="whitespace-nowrap px-3 py-3.5 text-right text-xs font-bold text-gray-900 dark:text-white">
                                        {{ formatCurrency(item.purchase_price) }}
                                    </td>

                                    <!-- Status -->
                                    <td class="whitespace-nowrap px-3 py-3.5 text-center text-xs">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium',
                                                item.status === 'active'
                                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                    : item.status === 'damaged'
                                                    ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                                                    : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                            ]"
                                        >
                                            <span class="w-1.5 h-1.5 mr-1.5 rounded-full" :class="item.status === 'active' ? 'bg-emerald-500' : item.status === 'damaged' ? 'bg-amber-500' : 'bg-red-500'"></span>
                                            {{ item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : 'Active' }}
                                        </span>
                                    </td>

                                    <!-- Actions -->
                                    <td class="whitespace-nowrap py-3.5 pl-3 pr-4 text-center text-xs font-medium">
                                        <div class="flex items-center justify-center gap-2">
                                            <button
                                                @click="editItem(asset, item)"
                                                class="p-1.5 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 rounded hover:bg-indigo-50 dark:hover:bg-gray-700 transition"
                                                title="Edit Asset"
                                            >
                                                <i class="fas fa-edit text-sm"></i>
                                            </button>
                                            <button
                                                @click="confirmDelete(asset, item)"
                                                class="p-1.5 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 rounded hover:bg-red-50 dark:hover:bg-gray-700 transition"
                                                title="Delete Asset"
                                            >
                                                <i class="fas fa-trash-alt text-sm"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <tr v-if="!assets || assets.length === 0">
                                <td colspan="7" class="py-12 text-center">
                                    <div class="max-w-sm mx-auto">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto text-gray-400 text-lg mb-3">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">No fixed assets recorded</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ hasActiveFilters ? 'No fixed assets match the filter criteria.' : 'Click "Record Fixed Asset" to add company assets or equipment.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
                            <i :class="isEditing ? 'fas fa-pen' : 'fas fa-building'" class="text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ isEditing ? 'Edit Fixed Asset' : 'Record Fixed Asset' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Essential capital asset information
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
                    <!-- Asset Name -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Asset / Item Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g. Office Computer, Office Chair, Air Conditioner"
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.name || form.errors.item_name" class="text-xs text-red-600 mt-1">
                            {{ form.errors.name || form.errors.item_name }}
                        </p>
                    </div>

                    <!-- Payment Account (When creating) -->
                    <div v-if="!isEditing">
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

                    <!-- Price -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Purchase Price (৳) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">৳</span>
                            <input
                                v-model="form.purchase_price"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                placeholder="0.00"
                                class="w-full pl-8 pr-3 py-2 text-sm font-semibold rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <p v-if="form.errors.purchase_price" class="text-xs text-red-600 mt-1">
                            {{ form.errors.purchase_price }}
                        </p>
                    </div>

                    <!-- Purchase Date with Super Admin Control -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Purchase Date <span class="text-red-500">*</span>
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
                                v-model="form.purchase_date"
                                required
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- If Non-Super-Admin: Fixed Date Display (Disabled) -->
                        <div v-else class="relative">
                            <DateInput
                                :model-value="form.purchase_date"
                                disabled
                                class="w-full text-sm rounded-lg border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                            />
                            <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 flex items-center gap-1">
                                <i class="fas fa-info-circle text-[10px]"></i> Date is locked to today. Only Super Admin can change date.
                            </p>
                        </div>
                        <p v-if="form.errors.purchase_date" class="text-xs text-red-600 mt-1">
                            {{ form.errors.purchase_date }}
                        </p>
                    </div>

                    <!-- Status (For edit only) -->
                    <div v-if="isEditing">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Status <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.status"
                            required
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="active">Active</option>
                            <option value="disposed">Disposed</option>
                            <option value="damaged">Damaged</option>
                        </select>
                    </div>

                    <!-- Note / Description -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Description / Note
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Optional asset specification or location..."
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
                            {{ isEditing ? 'Update Asset' : 'Save Fixed Asset' }}
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
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Remove Fixed Asset</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Are you sure you want to remove <span class="font-bold text-gray-700 dark:text-gray-200">{{ itemToDelete?.item_name || assetToDelete?.name }}</span>?
                    This will remove the record and reverse the bank transaction.
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
                        @click="deleteAssetConfirmed"
                        :disabled="isDeleting"
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm"
                    >
                        <i v-if="isDeleting" class="fas fa-spinner fa-spin mr-1.5"></i>
                        Confirm Remove
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
import { PlusIcon } from '@heroicons/vue/24/outline'
import { formatDate } from '@/utils'
import DateInput from '@/Components/DateInput.vue'

const props = defineProps({
    assets: Array,
    bankAccounts: Array,
    nextCode: String,
    filters: Object,
    summary: {
        type: Object,
        default: () => ({
            count: 0,
            purchase_value: 0,
            active_value: 0
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

// Format Currency
const formatCurrency = (amount) => {
    const num = Number(amount || 0)
    return `৳ ${num.toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`
}

// Modal and state
const showModal = ref(false)
const isEditing = ref(false)
const currentAsset = ref(null)
const currentItem = ref(null)

const showDeleteModal = ref(false)
const assetToDelete = ref(null)
const itemToDelete = ref(null)
const isDeleting = ref(false)

// Filter state
const filterForm = reactive({
    search: props.filters?.search || '',
    bank_id: props.filters?.bank_id || '',
    from_date: props.filters?.from_date || '',
    to_date: props.filters?.to_date || ''
})

const hasActiveFilters = computed(() => {
    return Boolean(
        filterForm.search ||
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
    router.get(route('admin.fixed-assets.index'), filterForm, {
        preserveState: true,
        preserveScroll: true
    })
}

const resetFilters = () => {
    filterForm.search = ''
    filterForm.bank_id = ''
    filterForm.from_date = ''
    filterForm.to_date = ''
    applyFilters()
}

// Clean Form (essential fields only)
const form = useForm({
    name: '',
    item_name: '',
    asset_code: '',
    purchase_price: '',
    bank_account_id: '',
    purchase_date: getTodayDate(),
    status: 'active',
    description: ''
})

const openCreateModal = () => {
    isEditing.value = false
    currentAsset.value = null
    currentItem.value = null
    form.clearErrors()
    form.reset()
    form.name = ''
    form.item_name = ''
    form.asset_code = props.nextCode || ''
    form.purchase_price = ''
    form.bank_account_id = props.bankAccounts[0]?.id || ''
    form.purchase_date = getTodayDate()
    form.status = 'active'
    form.description = ''
    showModal.value = true
}

const editItem = (asset, item) => {
    isEditing.value = true
    currentAsset.value = asset
    currentItem.value = item
    form.clearErrors()

    const rawDate = item.purchase_date ? String(item.purchase_date).substring(0, 10) : getTodayDate()

    form.name = item.item_name || asset.name
    form.item_name = item.item_name || asset.name
    form.asset_code = asset.asset_code || ''
    form.purchase_price = item.purchase_price
    form.bank_account_id = item.bank_account_id || ''
    form.purchase_date = rawDate
    form.status = item.status || 'active'
    form.description = item.description || asset.description || ''
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    form.reset()
    currentAsset.value = null
    currentItem.value = null
}

const submitForm = () => {
    // Sync item_name if empty
    if (!form.item_name) {
        form.item_name = form.name
    }

    if (!isSuperAdmin.value && !isEditing.value) {
        form.purchase_date = getTodayDate()
    }

    if (isEditing.value) {
        form.put(route('admin.fixed-assets.items.update', currentItem.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    } else {
        form.post(route('admin.fixed-assets.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    }
}

const confirmDelete = (asset, item) => {
    assetToDelete.value = asset
    itemToDelete.value = item
    showDeleteModal.value = true
}

const deleteAssetConfirmed = () => {
    if (!itemToDelete.value) return
    isDeleting.value = true

    // If asset has only 1 item, delete the whole asset, else delete the item
    if (assetToDelete.value && (!assetToDelete.value.items || assetToDelete.value.items.length <= 1)) {
        router.delete(route('admin.fixed-assets.destroy', assetToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false
                assetToDelete.value = null
                itemToDelete.value = null
                isDeleting.value = false
            },
            onError: () => {
                isDeleting.value = false
            }
        })
    } else {
        router.delete(route('admin.fixed-assets.items.destroy', itemToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false
                assetToDelete.value = null
                itemToDelete.value = null
                isDeleting.value = false
            },
            onError: () => {
                isDeleting.value = false
            }
        })
    }
}
</script>
