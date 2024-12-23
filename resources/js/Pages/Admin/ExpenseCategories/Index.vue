<template>
    <AdminLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
                    Expense Categories
                </h2>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                >
                    <PlusIcon class="w-5 h-5 mr-2" />
                    Add Category
                </button>
            </div>
        </template>

        <!-- Categories Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Description</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="category in categories.data" :key="category.id">
                        <td class="px-6 py-4 whitespace-nowrap">{{ category.name }}</td>
                        <td class="px-6 py-4">{{ category.description }}</td>
                        <td class="px-6 py-4 text-center">
                            <span
                                :class="[
                                    'px-2 py-1 text-xs rounded-full',
                                    category.status
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                ]"
                            >
                                {{ category.status ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="editCategory(category)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                Edit
                            </button>
                            <button @click="deleteCategory(category)" class="text-red-600 hover:text-red-900">
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <Pagination :links="categories.links" />
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium mb-4">
                    {{ isEditing ? 'Edit Category' : 'Create Category' }}
                </h3>
                <form @submit.prevent="submitForm">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Name</label>
                            <input type="text" v-model="form.name" class="w-full rounded-lg" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full rounded-lg"></textarea>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" v-model="form.status" id="status" class="rounded">
                            <label for="status" class="ml-2 text-sm font-medium">Active</label>
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
    categories: Object
})

const showModal = ref(false)
const isEditing = ref(false)
const currentCategory = ref(null)

const form = reactive({
    name: '',
    description: '',
    status: true
})

const openCreateModal = () => {
    isEditing.value = false
    resetForm()
    showModal.value = true
}

const editCategory = (category) => {
    isEditing.value = true
    currentCategory.value = category
    Object.assign(form, {
        name: category.name,
        description: category.description,
        status: category.status
    })
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    resetForm()
}

const resetForm = () => {
    Object.assign(form, {
        name: '',
        description: '',
        status: true
    })
    currentCategory.value = null
}

const submitForm = () => {
    if (isEditing.value) {
        router.post(route('admin.expense-categories.update', currentCategory.value.id), {
            ...form,
            _method: 'PUT'
        }, {
            onSuccess: () => closeModal()
        })
    } else {
        router.post(route('admin.expense-categories.store'), form, {
            onSuccess: () => closeModal()
        })
    }
}

const deleteCategory = (category) => {
    if (confirm('Are you sure you want to delete this category?')) {
        router.delete(route('admin.expense-categories.destroy', category.id))
    }
}
</script>
