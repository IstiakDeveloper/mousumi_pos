<template>
    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">
                        Expense Categories
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Organize and classify operational expenses for clear financial reporting
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <PlusIcon class="w-5 h-5 mr-1.5" />
                    Add Category
                </button>
            </div>
        </template>

        <div class="py-4 space-y-6">
            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <!-- Total Categories -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Total Categories
                            </p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ summary.total || 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400">
                            <i class="fas fa-folder text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Classification groups
                    </div>
                </div>

                <!-- Active Categories -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Active Categories
                            </p>
                            <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ summary.active || 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Available for recording expenses
                    </div>
                </div>

                <!-- Inactive Categories -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Inactive Categories
                            </p>
                            <p class="mt-2 text-2xl font-bold text-gray-600 dark:text-gray-300">
                                {{ summary.inactive || 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl text-gray-500 dark:text-gray-400">
                            <i class="fas fa-archive text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Disabled or archived groups
                    </div>
                </div>
            </div>

            <!-- Filters Toolbar -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <!-- Search Input -->
                    <div class="lg:col-span-8">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Search category name or description..."
                                class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                @input="debouncedFilter"
                            />
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div class="lg:col-span-4">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Status</label>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="filterForm.status"
                                @change="applyFilters"
                                class="w-full py-2 px-3 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
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
                                    Category Name
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Description / Remarks
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Expenses Count
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
                            <tr
                                v-for="category in categories.data"
                                :key="category.id"
                                class="hover:bg-gray-50/80 dark:hover:bg-gray-750 transition-colors"
                            >
                                <!-- Name -->
                                <td class="whitespace-nowrap py-3.5 pl-4 pr-3 text-xs font-semibold text-gray-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-xs">
                                            <i class="fas fa-folder"></i>
                                        </div>
                                        <span>{{ category.name }}</span>
                                    </div>
                                </td>

                                <!-- Description -->
                                <td class="px-3 py-3.5 text-xs text-gray-600 dark:text-gray-300 max-w-sm truncate">
                                    {{ category.description || '—' }}
                                </td>

                                <!-- Expenses Count -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-center text-xs">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ category.expenses_count || 0 }} entries
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-center text-xs">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium',
                                            category.status
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300'
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full" :class="category.status ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        {{ category.status ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="whitespace-nowrap py-3.5 pl-3 pr-4 text-center text-xs font-medium">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            @click="editCategory(category)"
                                            class="p-1.5 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 rounded hover:bg-indigo-50 dark:hover:bg-gray-700 transition"
                                            title="Edit Category"
                                        >
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                        <button
                                            @click="confirmDelete(category)"
                                            class="p-1.5 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 rounded hover:bg-red-50 dark:hover:bg-gray-700 transition"
                                            title="Delete Category"
                                        >
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!categories.data || categories.data.length === 0">
                                <td colspan="5" class="py-12 text-center">
                                    <div class="max-w-sm mx-auto">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto text-gray-400 text-lg mb-3">
                                            <i class="fas fa-folder-open"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">No categories found</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ hasActiveFilters ? 'No categories match the filter criteria.' : 'Click "Add Category" to organize your business expenses.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="categories.links && categories.links.length > 3" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                    <Pagination :links="categories.links" />
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
                            <i :class="isEditing ? 'fas fa-pen' : 'fas fa-folder-plus'" class="text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ isEditing ? 'Edit Expense Category' : 'Create Expense Category' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Category classification details
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
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g. Office Rent, Utilities, Entertainment"
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Description / Remarks
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Optional note about this expense category..."
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <p v-if="form.errors.description" class="text-xs text-red-600 mt-1">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Status Toggle -->
                    <div class="pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                v-model="form.status"
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700"
                            />
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                Active Category (Enable for expense entry)
                            </span>
                        </label>
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
                            {{ isEditing ? 'Update Category' : 'Save Category' }}
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
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Delete Category</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Are you sure you want to delete <span class="font-bold text-gray-700 dark:text-gray-200">{{ categoryToDelete?.name }}</span>?
                    Categories in use by expenses cannot be deleted.
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
                        @click="deleteCategoryConfirmed"
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
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    categories: Object,
    filters: Object,
    summary: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0
        })
    }
})

// Modal states
const showModal = ref(false)
const isEditing = ref(false)
const currentCategory = ref(null)

const showDeleteModal = ref(false)
const categoryToDelete = ref(null)
const isDeleting = ref(false)

// Filters
const filterForm = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status ?? ''
})

const hasActiveFilters = computed(() => {
    return Boolean(filterForm.search || (filterForm.status !== '' && filterForm.status !== null))
})

let debounceTimer = null
const debouncedFilter = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        applyFilters()
    }, 350)
}

const applyFilters = () => {
    router.get(route('admin.expense-categories.index'), filterForm, {
        preserveState: true,
        preserveScroll: true
    })
}

const resetFilters = () => {
    filterForm.search = ''
    filterForm.status = ''
    applyFilters()
}

// Clean Form
const form = useForm({
    name: '',
    description: '',
    status: true
})

const openCreateModal = () => {
    isEditing.value = false
    currentCategory.value = null
    form.clearErrors()
    form.reset()
    form.name = ''
    form.description = ''
    form.status = true
    showModal.value = true
}

const editCategory = (category) => {
    isEditing.value = true
    currentCategory.value = category
    form.clearErrors()
    form.name = category.name
    form.description = category.description || ''
    form.status = Boolean(category.status)
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    form.reset()
    currentCategory.value = null
}

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.expense-categories.update', currentCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    } else {
        form.post(route('admin.expense-categories.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    }
}

const confirmDelete = (category) => {
    categoryToDelete.value = category
    showDeleteModal.value = true
}

const deleteCategoryConfirmed = () => {
    if (!categoryToDelete.value) return
    isDeleting.value = true

    router.delete(route('admin.expense-categories.destroy', categoryToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            categoryToDelete.value = null
            isDeleting.value = false
        },
        onError: () => {
            isDeleting.value = false
        }
    })
}
</script>
