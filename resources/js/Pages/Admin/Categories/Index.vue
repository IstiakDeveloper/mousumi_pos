<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

// Define props
const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    filters: {
        type: Object,
        default: () => ({})
    }
})

// Search functionality
const searchForm = ref({
    search: props.filters.search || ''
})

// Debounce search to prevent excessive API calls
const search = () => {
    router.get('/admin/categories', { search: searchForm.value.search }, {
        preserveState: true,
        preserveScroll: true
    })
}

// Delete category handler
const deleteCategory = (id) => {
    router.delete(`/admin/categories/${id}`)
}

// Confirmation dialog state
const showConfirmDialog = ref(false)
const categoryToDelete = ref(null)

// Open delete confirmation dialog
const confirmDelete = (category) => {
    categoryToDelete.value = category
    showConfirmDialog.value = true
}

// Confirm delete action
const handleConfirmDelete = () => {
    if (categoryToDelete.value) {
        deleteCategory(categoryToDelete.value.id)
        showConfirmDialog.value = false
        categoryToDelete.value = null
    }
}
</script>

<template>
    <AdminLayout>

        <Head title="Categories" />

        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold dark:text-white">
                    Categories Management
                </h1>
                <Link :href="route('admin.categories.create')"
                    class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 flex items-center transition-colors duration-300">
                <i class="fas fa-plus mr-2"></i> Add New Category
                </Link>
            </div>

            <div class="mb-4">
                <input v-model="searchForm.search" @input="search" placeholder="Search categories..."
                    class="w-full px-3 py-2 border rounded dark:bg-gray-800 dark:text-white dark:border-gray-700 transition-colors duration-300" />
            </div>

            <div class="bg-white dark:bg-gray-900 shadow-md rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-800 border-b dark:border-gray-700">
                            <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Name</th>
                            <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Slug</th>
                            <th class="px-4 py-3 text-left text-gray-600 dark:text-gray-300">Parent</th>
                            <th class="px-4 py-3 text-center text-gray-600 dark:text-gray-300">Status</th>
                            <th class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="category in categories.data" :key="category.id"
                            class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-300">
                            <td class="px-4 py-3 dark:text-white">{{ category.name }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ category.slug }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ category.parent?.name || 'No Parent' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="[
                                    'px-2 py-1 rounded text-xs font-medium',
                                    category.status
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
                                ]">
                                    {{ category.status ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right space-x-2">
                                <Link :href="route('admin.categories.edit', category.id)"
                                    class="inline-flex items-center justify-center px-2 py-1 text-sm text-white bg-blue-500 rounded hover:bg-blue-600 dark:bg-blue-700 dark:hover:bg-blue-600 transition-colors duration-300">
                                <i class="fas fa-edit"></i>
                                </Link>
                                <button @click="confirmDelete(category)"
                                    class="inline-flex items-center justify-center px-2 py-1 text-sm text-white bg-red-500 rounded hover:bg-red-600 dark:bg-red-700 dark:hover:bg-red-600 transition-colors duration-300">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="categories.links" class="p-4 flex justify-between items-center bg-gray-100 dark:bg-gray-800">
                    <div class="flex space-x-2">
                        <Link v-for="link in categories.links" :key="link.label" :href="link.url" :class="[
                            'px-3 py-1 rounded text-sm transition-colors duration-300',
                            link.active
                                ? 'bg-blue-500 text-white'
                                : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600'
                        ]">
                        <span v-html="link.label"></span>
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirm Dialog Component -->
        <ConfirmDialog
  v-model:show="showConfirmDialog"
  title="Delete Category"
  :message="'Are you sure you want to delete the category ' + categoryToDelete?.name + '?'"
  confirm-text="Delete"
  cancel-text="Cancel"
  @confirm="handleConfirmDelete"
/>
    </AdminLayout>
</template>
