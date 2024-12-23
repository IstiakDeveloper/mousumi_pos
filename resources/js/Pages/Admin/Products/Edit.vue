<template>
    <Head title="Product Edit" />
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Product</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Update product information
                            </p>
                        </div>
                        <Link :href="route('admin.products.index')"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        <ArrowLeftIcon class="h-5 w-5 mr-2" />
                        Back to Products
                        </Link>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                    <form @submit.prevent="submit" class="space-y-8 divide-y divide-gray-200 dark:divide-gray-700"
                        enctype="multipart/form-data">
                        <!-- Product Information -->
                        <div class="space-y-8 divide-y divide-gray-200 dark:divide-gray-700 sm:space-y-5 p-8">
                            <!-- Basic Information -->
                            <div class="space-y-6 sm:space-y-5">
                                <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start">
                                    <InputLabel for="name" value="Product Name" class="sm:mt-px sm:pt-2" />
                                    <div class="mt-1 sm:mt-0 sm:col-span-2">
                                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full"
                                            required />
                                        <InputError :message="form.errors.name" class="mt-2" />
                                    </div>
                                </div>

                                <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-start">
                                    <InputLabel for="sku" value="SKU" class="sm:mt-px sm:pt-2" />
                                    <div class="mt-1 sm:mt-0 sm:col-span-2">
                                        <TextInput id="sku" v-model="form.sku" type="text" class="mt-1 block w-full"
                                            required />
                                        <InputError :message="form.errors.sku" class="mt-2" />
                                    </div>
                                </div>
                                <!-- Barcode -->
                                <div class="sm:col-span-3">
                                    <InputLabel for="barcode" value="Barcode" />
                                    <TextInput id="barcode" v-model="form.barcode" type="text"
                                        class="mt-1 block w-full" />
                                    <InputError :message="form.errors.barcode" class="mt-2" />
                                </div>

                                <!-- Category -->
                                <div class="sm:col-span-3">
                                    <InputLabel for="category_id" value="Category" required />
                                    <select id="category_id" v-model="form.category_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Category</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.category_id" class="mt-2" />
                                </div>

                                <!-- Brand -->
                                <div class="sm:col-span-3">
                                    <InputLabel for="brand_id" value="Brand" />
                                    <select id="brand_id" v-model="form.brand_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select Brand</option>
                                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                            {{ brand.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.brand_id" class="mt-2" />
                                </div>

                                <!-- Unit -->
                                <div class="sm:col-span-3">
                                    <InputLabel for="unit_id" value="Unit" required />
                                    <select id="unit_id" v-model="form.unit_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        required>
                                        <option value="">Select Unit</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                            {{ unit.name }} ({{ unit.short_name }})
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.unit_id" class="mt-2" />
                                </div>
                            </div>

                            <!-- Product Images -->
                            <div class="pt-8">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Product Images</h3>
                                </div>
                                <div class="mt-4">
                                    <!-- Current Images -->
                                    <div v-if="product.images?.length"
                                        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                        <div v-for="image in product.images" :key="image.id" class="relative group">
                                            <img :src="getImageUrl(image.image)" class="h-24 w-24 rounded object-cover"
                                                :alt="product.name">
                                            <button type="button" @click="deleteImage(image.id)"
                                                class="absolute -top-2 -right-2 hidden group-hover:flex h-6 w-6 items-center justify-center rounded-full bg-red-600 text-white">
                                                <XMarkIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Upload New Images -->
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Add New Images
                                        </label>
                                        <div
                                            class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md">
                                            <div class="space-y-1 text-center">
                                                <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
                                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                                    <label
                                                        class="relative cursor-pointer rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500">
                                                        <span>Upload files</span>
                                                        <input type="file" class="sr-only" multiple
                                                            @change="onImagesSelected">
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="pt-5 px-8 py-4">
                            <div class="flex justify-end gap-3">
                                <Link :href="route('admin.products.index')"
                                    class="inline-flex justify-center py-2 px-4 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                Cancel
                                </Link>
                                <PrimaryButton :disabled="form.processing">
                                    Update Product
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ArrowLeftIcon,
    PhotoIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    product: Object,
    categories: Array,
    brands: Array,
    units: Array,
});

const form = useForm({
    _method: 'PUT',
    name: props.product.name,
    sku: props.product.sku,
    barcode: props.product.barcode || '',
    category_id: props.product.category_id,
    brand_id: props.product.brand_id || '',
    unit_id: props.product.unit_id,
    cost_price: props.product.cost_price,
    selling_price: props.product.selling_price,
    alert_quantity: props.product.alert_quantity,
    description: props.product.description || '',
    specifications: props.product.specifications || {},
    images: [],
    status: props.product.status,
});

const onImagesSelected = (event) => {
    const files = Array.from(event.target.files).filter(file => {
        if (file.size > 2 * 1024 * 1024) {
            alert('File too large. Maximum size is 2MB.');
            return false;
        }
        return true;
    });

    form.images = files;
};

const deleteImage = (imageId) => {
    if (confirm('Are you sure you want to delete this image?')) {
        router.delete(route('admin.products.deleteImage', imageId), {
            preserveScroll: true,
            preserveState: true,
        });
    }
};

const getImageUrl = (path) => {
    return `/storage/${path}`;
};

const submit = () => {
    form.post(route('admin.products.update', props.product.id), {
        preserveScroll: true,
    });
};
</script>
