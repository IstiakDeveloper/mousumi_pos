<script setup>
import { ref, reactive } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

// Define props for parent categories and any existing category data
const props = defineProps({
  parentCategories: {
    type: Array,
    default: () => []
  }
})

// Create form using Inertia's useForm for built-in validation and error handling
const form = useForm({
  name: '',
  slug: '',
  parent_id: null,
  description: '',
  status: true,
  image: null
})

// Automatically generate slug when name changes
const generateSlug = () => {
  form.slug = form.name
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_-]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

// Handle image upload
const imagePreview = ref(null)
const handleImageUpload = (event) => {
  const file = event.target.files[0]
  form.image = file

  // Create image preview
  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

// Remove image
const removeImage = () => {
  form.image = null
  imagePreview.value = null
  // Reset file input
  const fileInput = document.getElementById('image-upload')
  if (fileInput) fileInput.value = ''
}

// Submit form
const submitForm = () => {
  form.post(route('admin.categories.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Optional: Show success message or redirect
    },
    onError: (errors) => {
      // Errors are automatically handled by Inertia
      console.error(errors)
    }
  })
}
</script>

<template>
  <AdminLayout>
    <Head title="Create Category" />

    <div class="container mx-auto px-4 py-6">
      <div class="max-w-2xl mx-auto bg-white dark:bg-gray-900 shadow-md rounded-lg overflow-hidden">
        <div class="p-6">
          <h1 class="text-2xl font-bold mb-6 text-gray-800 dark:text-white">
            Create New Category
          </h1>

          <form @submit.prevent="submitForm" class="space-y-6">
            <div>
              <label
                for="name"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                Category Name
              </label>
              <input
                id="name"
                v-model="form.name"
                @input="generateSlug"
                type="text"
                class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-700"
                :class="form.errors.name ? 'border-red-500' : 'border-gray-300'"
                required
              />
              <p
                v-if="form.errors.name"
                class="text-red-500 text-sm mt-1"
              >
                {{ form.errors.name }}
              </p>
            </div>

            <div>
              <label
                for="slug"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                Slug
              </label>
              <input
                id="slug"
                v-model="form.slug"
                type="text"
                class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-700"
                :class="form.errors.slug ? 'border-red-500' : 'border-gray-300'"
                required
              />
              <p
                v-if="form.errors.slug"
                class="text-red-500 text-sm mt-1"
              >
                {{ form.errors.slug }}
              </p>
            </div>

            <div>
              <label
                for="parent_id"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                Parent Category
              </label>
              <select
                id="parent_id"
                v-model="form.parent_id"
                class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-700"
                :class="form.errors.parent_id ? 'border-red-500' : 'border-gray-300'"
              >
                <option :value="null">Select Parent Category (Optional)</option>
                <option
                  v-for="category in parentCategories"
                  :key="category.id"
                  :value="category.id"
                >
                  {{ category.name }}
                </option>
              </select>
              <p
                v-if="form.errors.parent_id"
                class="text-red-500 text-sm mt-1"
              >
                {{ form.errors.parent_id }}
              </p>
            </div>

            <div>
              <label
                for="description"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                Description
              </label>
              <textarea
                id="description"
                v-model="form.description"
                class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-700"
                :class="form.errors.description ? 'border-red-500' : 'border-gray-300'"
                rows="4"
              ></textarea>
              <p
                v-if="form.errors.description"
                class="text-red-500 text-sm mt-1"
              >
                {{ form.errors.description }}
              </p>
            </div>

            <div>
              <label
                for="image-upload"
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                Category Image
              </label>
              <div class="flex items-center space-x-4">
                <input
                  id="image-upload"
                  type="file"
                  @change="handleImageUpload"
                  accept="image/*"
                  class="hidden"
                />
                <label
                  for="image-upload"
                  class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 cursor-pointer"
                >
                  Choose Image
                </label>

                <div v-if="imagePreview" class="relative">
                  <img
                    :src="imagePreview"
                    alt="Image Preview"
                    class="w-24 h-24 object-cover rounded-md"
                  />
                  <button
                    @click="removeImage"
                    class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1 -m-2"
                  >
                    <i class="fas fa-times text-xs"></i>
                  </button>
                </div>
              </div>
              <p
                v-if="form.errors.image"
                class="text-red-500 text-sm mt-1"
              >
                {{ form.errors.image }}
              </p>
            </div>

            <div>
              <label
                for="status"
                class="flex items-center text-sm font-medium text-gray-700 dark:text-gray-300"
              >
                <input
                  id="status"
                  v-model="form.status"
                  type="checkbox"
                  class="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                Active Category
              </label>
            </div>

            <div class="flex justify-end space-x-4">
              <Link
                :href="route('admin.categories.index')"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
              >
                Cancel
              </Link>
              <button
                type="submit"
                :disabled="form.processing"
                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 disabled:opacity-50"
              >
                {{ form.processing ? 'Creating...' : 'Create Category' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
