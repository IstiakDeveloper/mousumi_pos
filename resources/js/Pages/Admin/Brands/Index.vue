<template>
    <Head title="Brands" />
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Brands</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Manage your product brands
                            </p>
                        </div>
                        <PrimaryButton @click="openModal" class="flex items-center">
                            <PlusIcon class="h-5 w-5 mr-2" />
                            Add Brand
                        </PrimaryButton>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                <!-- Search and Filter Section -->
                <div class="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow">
                    <div class="p-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <!-- Search Input -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                                </div>
                                <input v-model="search" type="text"
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Search brands..." @input="debouncedSearch">
                            </div>

                            <!-- Status Filter -->
                            <select v-model="status"
                                class="block w-full pl-3 pr-10 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-base focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white"
                                @change="filterStatus">
                                <option value="">All Status</option>
                                <option :value="1">Active</option>
                                <option :value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Brands Table -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Brand
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="brand in brands.data" :key="brand.id"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img v-if="brand.logo" :src="getImageUrl(brand.logo)"
                                                    class="h-10 w-10 rounded-full object-cover" :alt="brand.name">
                                                <div v-else
                                                    class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                                    <Squares2X2Icon class="h-6 w-6 text-gray-400" />
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ brand.name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            brand.status
                                                ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                                                : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
                                        ]">
                                            {{ brand.status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="editBrand(brand)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                            <PencilIcon class="h-5 w-5" />
                                        </button>
                                        <button @click="deleteBrand(brand)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            <TrashIcon class="h-5 w-5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div
                        class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                        <Pagination :links="brands.links" />
                    </div>
                </div>
            </main>

            <!-- Brand Modal -->
            <Modal :show="showModal" @close="closeModal" maxWidth="md">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ editing ? 'Edit Brand' : 'Add Brand' }}
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                            <XMarkIcon class="h-6 w-6" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required
                                placeholder="Enter brand name" />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <div>
                            <InputLabel for="logo" value="Logo" />
                            <div class="mt-1 flex items-center">
                                <div v-if="imagePreview || form.logo" class="mr-3">
                                    <img :src="imagePreview || getImageUrl(form.logo)"
                                        class="h-12 w-12 rounded-full object-cover">
                                </div>
                                <label
                                    class="relative cursor-pointer bg-white dark:bg-gray-800 py-2 px-3 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>Upload a file</span>
                                    <input type="file" class="sr-only" @change="updateLogo" accept="image/*">
                                </label>
                            </div>
                            <InputError :message="form.errors.logo" class="mt-1" />
                        </div>

                        <div class="flex items-center">
                            <InputLabel for="status" value="Status" class="mr-3" />
                            <button type="button" :class="[
                                form.status ? 'bg-indigo-600' : 'bg-gray-200',
                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2'
                            ]" @click="form.status = !form.status">
                                <span :class="[
                                    form.status ? 'translate-x-5' : 'translate-x-0',
                                    'pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out'
                                ]" />
                            </button>
                        </div>

                        <!-- Complete the form actions in template -->
                        <div class="mt-6 flex justify-end space-x-3">
                            <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                            <PrimaryButton :disabled="form.processing">
                                {{ editing ? 'Update' : 'Create' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </Modal>

            <!-- Add Confirm Dialog -->
            <ConfirmDialog v-model:show="showConfirmDialog" title="Delete Brand" :message="confirmMessage"
                confirmText="Delete" cancelText="Cancel" @confirm="confirmDelete" />
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    PlusIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    TrashIcon,
    XMarkIcon,
    Squares2X2Icon
} from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';

const props = defineProps({
    brands: Object,
    filters: Object
});

// Refs for state management
const showModal = ref(false);
const editing = ref(false);
const search = ref(props.filters.search);
const status = ref(props.filters.status);
const imagePreview = ref(null);
const showConfirmDialog = ref(false);
const confirmMessage = ref('');
const brandToDelete = ref(null);

// Form handling
const form = useForm({
    name: '',
    logo: null,
    status: true
});

// Image URL helper
const getImageUrl = (path) => {
    if (!path) return null;
    return `${usePage().props.appUrl}/storage/${path}`;
};

// Modal handlers
const openModal = () => {
    editing.value = false;
    form.reset();
    form.clearErrors();
    imagePreview.value = null;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
    editing.value = false;
    imagePreview.value = null;
};

// Logo upload handler
const updateLogo = (e) => {
    const file = e.target.files[0];
    if (file) {
        // Validate file size (max 1MB)
        if (file.size > 1024 * 1024) {
            alert('File size must be less than 1MB');
            e.target.value = '';
            return;
        }

        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select an image file');
            e.target.value = '';
            return;
        }

        imagePreview.value = URL.createObjectURL(file);
        form.logo = file;
    }
};

// Form submission
const submit = () => {
    if (editing.value) {
        form.put(route('admin.brands.update', editing.value), {
            onSuccess: () => {
                closeModal();
                imagePreview.value = null;
            },
            preserveScroll: true,
            preserveState: true,
            forceFormData: true,
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
    } else {
        form.post(route('admin.brands.store'), {
            onSuccess: () => {
                closeModal();
                imagePreview.value = null;
            },
            preserveScroll: true,
            preserveState: true,
            forceFormData: true
        });
    }
};

// Brand editing
const editBrand = (brand) => {
    editing.value = brand.id;
    form.name = brand.name;
    form.status = Boolean(brand.status);
    form.logo = brand.logo;
    showModal.value = true;
};

// Brand deletion
const deleteBrand = (brand) => {
    brandToDelete.value = brand;
    confirmMessage.value = `Are you sure you want to delete "${brand.name}"? This action cannot be undone.`;
    showConfirmDialog.value = true;
};

const confirmDelete = () => {
    if (brandToDelete.value) {
        router.delete(route('admin.brands.destroy', brandToDelete.value.id), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showConfirmDialog.value = false;
                brandToDelete.value = null;
            }
        });
    }
};

// Search and filter handlers
const debouncedSearch = debounce(() => {
    router.get(
        route('admin.brands.index'),
        { search: search.value, status: status.value },
        { preserveState: true, preserveScroll: true }
    );
}, 300);

const filterStatus = () => {
    router.get(
        route('admin.brands.index'),
        { search: search.value, status: status.value },
        { preserveState: true, preserveScroll: true }
    );
};

// Watch for filter changes
watch(() => props.filters, (newFilters) => {
    search.value = newFilters.search;
    status.value = newFilters.status;
}, { deep: true });
</script>
