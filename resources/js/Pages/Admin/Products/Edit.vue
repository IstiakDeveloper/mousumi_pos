<template>

    <Head title="Edit Product" />
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Product</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Update product details and information
                            </p>
                        </div>
                        <Link :href="route('admin.products.index')"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 dark:focus:ring-offset-gray-800">
                        <ArrowLeftIcon class="h-5 w-5 mr-2" />
                        Back to Products
                        </Link>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="py-8">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <form @submit.prevent="submit" class="space-y-8">
                        <!-- Basic Information Card -->
                        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="md:grid md:grid-cols-3 md:gap-6">
                                    <div class="md:col-span-1">
                                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                            Basic Information
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            Product identification and categorization details.
                                        </p>
                                    </div>
                                    <div class="mt-5 md:mt-0 md:col-span-2 space-y-6">
                                        <!-- Name & SKU -->
                                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-6">
                                            <div class="sm:col-span-4">
                                                <InputLabel for="name" value="Product Name" required />
                                                <TextInput id="name" v-model="form.name" type="text"
                                                    class="mt-1 block w-full" placeholder="Enter product name"
                                                    required />
                                                <InputError :message="form.errors.name" class="mt-2" />
                                            </div>

                                            <div class="sm:col-span-2">
                                                <InputLabel for="sku" value="SKU" required />
                                                <div class="mt-1 flex rounded-md shadow-sm">
                                                    <TextInput id="sku" v-model="form.sku" type="text"
                                                        class="block w-full" required />
                                                    <button type="button"
                                                        class="relative -ml-px inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 bg-gray-50 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600"
                                                        @click="generateSku">
                                                        <ArrowPathIcon class="h-5 w-5" />
                                                    </button>
                                                </div>
                                                <InputError :message="form.errors.sku" class="mt-2" />
                                            </div>

                                            <!-- Category & Brand -->
                                            <div class="sm:col-span-3">
                                                <InputLabel for="category_id" value="Category" required />
                                                <select id="category_id" v-model="form.category_id"
                                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300"
                                                    required>
                                                    <option value="">Select Category</option>
                                                    <option v-for="category in categories" :key="category.id"
                                                        :value="category.id">
                                                        {{ category.name }}
                                                    </option>
                                                </select>
                                                <InputError :message="form.errors.category_id" class="mt-2" />
                                            </div>

                                            <div class="sm:col-span-3">
                                                <InputLabel for="brand_id" value="Brand" />
                                                <select id="brand_id" v-model="form.brand_id"
                                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                                                    <option value="">Select Brand</option>
                                                    <option v-for="brand in brands" :key="brand.id" :value="brand.id">
                                                        {{ brand.name }}
                                                    </option>
                                                </select>
                                                <InputError :message="form.errors.brand_id" class="mt-2" />
                                            </div>

                                            <!-- Barcode & Unit -->
                                            <div class="sm:col-span-3">
                                                <InputLabel for="barcode" value="Barcode" required />
                                                <div class="mt-1 flex rounded-md shadow-sm">
                                                    <TextInput id="barcode" v-model="form.barcode" type="text"
                                                        class="block w-full" required />
                                                    <button type="button"
                                                        class="relative -ml-px inline-flex items-center px-3 py-2 rounded-r-md border border-gray-300 bg-gray-50 text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600"
                                                        @click="generateBarcode">
                                                        <ArrowPathIcon class="h-5 w-5" />
                                                    </button>
                                                </div>
                                                <InputError :message="form.errors.barcode" class="mt-2" />
                                            </div>

                                            <div class="sm:col-span-3">
                                                <InputLabel for="unit_id" value="Unit" required />
                                                <select id="unit_id" v-model="form.unit_id"
                                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300"
                                                    required>
                                                    <option value="">Select Unit</option>
                                                    <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                                        {{ unit.name }} ({{ unit.short_name }})
                                                    </option>
                                                </select>
                                                <InputError :message="form.errors.unit_id" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Card -->
                        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="md:grid md:grid-cols-3 md:gap-6">
                                    <div class="md:col-span-1">
                                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                            Pricing Details
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            Set your product's selling price and inventory information.
                                        </p>
                                    </div>
                                    <div class="mt-5 md:mt-0 md:col-span-2">
                                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                            <div>
                                                <InputLabel for="selling_price" value="Selling Price" required />
                                                <div class="relative mt-1">
                                                    <div
                                                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                        <span
                                                            class="text-gray-500 sm:text-sm dark:text-gray-400">৳</span>
                                                    </div>
                                                    <input id="selling_price" v-model="form.selling_price" type="number"
                                                        step="0.01" min="0" required placeholder="0.00"
                                                        class="block w-full rounded-md border-gray-300 pl-7 pr-12 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300">
                                                    <div
                                                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                                        <span
                                                            class="text-gray-500 sm:text-sm dark:text-gray-400">BDT</span>
                                                    </div>
                                                </div>
                                                <InputError :message="form.errors.selling_price" class="mt-2" />
                                            </div>

                                            <div>
                                                <InputLabel for="alert_quantity" value="Alert Quantity" required />
                                                <TextInput id="alert_quantity" v-model="form.alert_quantity"
                                                    type="number" min="0" class="mt-1 block w-full" required />
                                                <InputError :message="form.errors.alert_quantity" class="mt-2" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="md:grid md:grid-cols-3 md:gap-6">
                                    <div class="md:col-span-1">
                                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                            Additional Information
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            Add description and specifications for your product.
                                        </p>
                                    </div>
                                    <div class="mt-5 md:mt-0 md:col-span-2 space-y-6">
                                        <div>
                                            <InputLabel for="description" value="Description" />
                                            <textarea id="description" v-model="form.description" rows="4"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300"
                                                placeholder="Enter product description"></textarea>
                                        </div>

                                        <!-- Specifications -->
                                        <div class="space-y-4">
                                            <div class="flex justify-between items-center">
                                                <InputLabel value="Specifications" />
                                                <button type="button"
                                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 dark:text-indigo-100 dark:bg-indigo-900 dark:hover:bg-indigo-800"
                                                    @click="addSpecification">
                                                    <PlusIcon class="h-4 w-4 mr-1.5" />
                                                    Add Specification
                                                </button>
                                            </div>

                                            <TransitionGroup
                                                enter-active-class="transition-all duration-300 ease-in-out"
                                                enter-from-class="opacity-0 -translate-y-4"
                                                enter-to-class="opacity-100 translate-y-0"
                                                leave-active-class="transition-all duration-200 ease-in-out"
                                                leave-from-class="opacity-100 translate-y-0"
                                                leave-to-class="opacity-0 translate-y-4">
                                                <div v-for="(spec, index) in specifications" :key="index"
                                                    class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                                    <TextInput v-model="spec.key" type="text" class="flex-1"
                                                        placeholder="Specification name (e.g., Color)" />
                                                    <TextInput v-model="spec.value" type="text" class="flex-1"
                                                        placeholder="Specification value (e.g., Red)" />
                                                    <button type="button"
                                                        class="p-2 text-gray-400 hover:text-red-500 dark:hover:text-red-400"
                                                        @click="removeSpecification(index)">
                                                        <TrashIcon class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            </TransitionGroup>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Product Images -->
                        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="md:grid md:grid-cols-3 md:gap-6">
                                    <div class="md:col-span-1">
                                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                            Product Images
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            Manage product images. Click the star icon to set an image as primary.
                                        </p>
                                    </div>
                                    <div class="mt-5 md:mt-0 md:col-span-2">
                                        <!-- Current Images Section -->
                                        <div class="mb-6">
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                                Current Images</h4>
                                            <div v-if="existingImages.length"
                                                class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                                <div v-for="image in existingImages" :key="image.id"
                                                    class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800">
                                                    <img :src="`/storage/${image.image}`"
                                                        class="h-full w-full object-cover">
                                                    <div
                                                        class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2">
                                                        <button type="button"
                                                            @click="setExistingImageAsPrimary(image.id)"
                                                            class="p-2 text-white hover:text-indigo-400 transition-colors"
                                                            :class="{ 'text-indigo-400': primaryImageId === image.id }">
                                                            <StarIcon class="h-6 w-6"
                                                                :class="{ 'fill-current': primaryImageId === image.id }" />
                                                        </button>
                                                        <button type="button" @click="deleteExistingImage(image.id)"
                                                            class="p-2 text-white hover:text-red-500 transition-colors">
                                                            <TrashIcon class="h-6 w-6" />
                                                        </button>
                                                    </div>
                                                    <!-- Primary Image Badge -->
                                                    <div v-if="primaryImageId === image.id"
                                                        class="absolute top-2 left-2 bg-indigo-500 text-white px-2 py-1 rounded-md text-xs font-medium">
                                                        Primary
                                                    </div>
                                                </div>
                                            </div>
                                            <p v-else class="text-sm text-gray-500 dark:text-gray-400">
                                                No images yet. Upload some below.
                                            </p>
                                        </div>

                                        <!-- Upload New Images -->
                                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-indigo-500 dark:hover:border-indigo-400 transition-colors"
                                            @dragover.prevent @drop.prevent="onFilesDrop">
                                            <div class="text-center">
                                                <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
                                                <div
                                                    class="mt-4 flex text-sm leading-6 text-gray-600 dark:text-gray-400">
                                                    <label for="file-upload"
                                                        class="relative cursor-pointer rounded-md font-semibold text-indigo-600 dark:text-indigo-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500 dark:hover:text-indigo-300">
                                                        <span>Upload files</span>
                                                        <input id="file-upload" type="file" multiple class="sr-only"
                                                            accept="image/*" @change="onImagesSelected">
                                                    </label>
                                                    <p class="pl-1">or drag and drop</p>
                                                </div>
                                                <p class="text-xs leading-5 text-gray-600 dark:text-gray-400">
                                                    PNG, JPG, GIF up to 2MB each
                                                </p>
                                            </div>
                                        </div>

                                        <!-- New Image Previews -->
                                        <div v-if="imagesPreviews.length" class="mt-6">
                                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">New
                                                Images</h4>
                                            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                                                <div v-for="(preview, index) in imagesPreviews" :key="index"
                                                    class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800">
                                                    <img :src="preview" class="h-full w-full object-cover">
                                                    <div
                                                        class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2">
                                                        <button type="button" @click="setNewImageAsPrimary(index)"
                                                            class="p-2 text-white hover:text-indigo-400 transition-colors"
                                                            :class="{ 'text-indigo-400': primaryImageIndex === index }">
                                                            <StarIcon class="h-6 w-6"
                                                                :class="{ 'fill-current': primaryImageIndex === index }" />
                                                        </button>
                                                        <button type="button" @click="removeNewImage(index)"
                                                            class="p-2 text-white hover:text-red-500 transition-colors">
                                                            <TrashIcon class="h-6 w-6" />
                                                        </button>
                                                    </div>
                                                    <!-- Primary Image Badge -->
                                                    <div v-if="primaryImageIndex === index"
                                                        class="absolute top-2 left-2 bg-indigo-500 text-white px-2 py-1 rounded-md text-xs font-medium">
                                                        Primary
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Toggle -->
                        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                            <div class="px-4 py-5 sm:p-6">
                                <div class="md:grid md:grid-cols-3 md:gap-6">
                                    <div class="md:col-span-1">
                                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                            Product Status
                                        </h3>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            Enable or disable this product in your store.
                                        </p>
                                    </div>
                                    <div class="mt-5 md:mt-0 md:col-span-2">
                                        <div class="flex items-center">
                                            <button type="button" @click="form.status = !form.status"
                                                class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                                                :class="[form.status ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-600']">
                                                <span
                                                    class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"
                                                    :class="[form.status ? 'translate-x-5' : 'translate-x-0']"></span>
                                            </button>
                                            <span class="ml-3"
                                                :class="{ 'text-green-600 dark:text-green-400': form.status, 'text-gray-500 dark:text-gray-400': !form.status }">
                                                {{ form.status ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3">
                            <Link :href="route('admin.products.index')"
                                class="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            Cancel
                            </Link>
                            <PrimaryButton type="submit" :disabled="form.processing">
                                <span v-if="form.processing">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    Updating...
                                </span>
                                <span v-else>Update Product</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useForm, Link, Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ArrowLeftIcon,
    PhotoIcon,
    TrashIcon,
    ArrowPathIcon,
    StarIcon,
    PlusIcon
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import axios from 'axios';

const props = defineProps({
    product: Object,
    categories: Array,
    brands: Array,
    units: Array
});

// Initialize form with product data
const form = useForm({
    name: props.product.name || '',
    sku: props.product.sku || '',
    barcode: props.product.barcode || '',
    category_id: props.product.category_id || '',
    brand_id: props.product.brand_id || '',
    unit_id: props.product.unit_id || '',
    selling_price: props.product.selling_price || '',
    alert_quantity: props.product.alert_quantity || 0,
    description: props.product.description || '',
    specifications: props.product.specifications || {},
    images: [],
    primary_image_index: null,
    primary_image_id: null,
    status: props.product.status !== undefined ? props.product.status : true
});

// Initialize specifications from product data
const specifications = ref([]);
onMounted(() => {
    if (props.product.specifications && Object.keys(props.product.specifications).length > 0) {
        specifications.value = Object.entries(props.product.specifications).map(([key, value]) => ({ key, value }));
    } else {
        specifications.value = [{ key: '', value: '' }];
    }
});

// Specifications Management
const addSpecification = () => {
    specifications.value.push({ key: '', value: '' });
};

const removeSpecification = (index) => {
    specifications.value.splice(index, 1);
};

// Image Management
const imagesPreviews = ref([]);
const existingImages = ref(props.product.images || []);
const primaryImageId = ref(null);
const primaryImageIndex = ref(null);
const pendingImageFiles = ref([]);


// Initialize from props.product
onMounted(() => {
    // Find the primary image in existing images
    const primaryImage = existingImages.value.find(img => img.is_primary);
    if (primaryImage) {
        primaryImageId.value = primaryImage.id;
    } else if (existingImages.value.length > 0) {
        // Default to first image if no primary is set
        primaryImageId.value = existingImages.value[0].id;
    }

    // Update form
    form.primary_image_id = primaryImageId.value;
});


// Methods for handling existing images
const deleteExistingImage = (imageId) => {
    axios.delete(route('admin.products.delete-image', imageId))
        .then(() => {
            // Remove from list
            existingImages.value = existingImages.value.filter(img => img.id !== imageId);

            // If the primary image was deleted, set a new one
            if (primaryImageId.value === imageId) {
                primaryImageId.value = existingImages.value.length > 0 ? existingImages.value[0].id : null;
                form.primary_image_id = primaryImageId.value;

                // If no existing images but we have new ones, set first new one as primary
                if (!primaryImageId.value && imagesPreviews.value.length > 0) {
                    primaryImageIndex.value = 0;
                    form.primary_image_index = 0;
                    form.primary_image_id = null;
                }
            }
        })
        .catch(error => console.error('Error deleting image:', error));
};

const setExistingImageAsPrimary = (imageId) => {
    primaryImageId.value = imageId;
    primaryImageIndex.value = null; // Clear new image primary
    form.primary_image_id = imageId;
    form.primary_image_index = null;
};
// Methods for handling new images
const handleFilesForUpdate = (files) => {
    const validFiles = files.filter(file => {
        if (file.size > 2 * 1024 * 1024) {
            alert(`File ${file.name} is too large. Maximum size is 2MB.`);
            return false;
        }
        if (!file.type.startsWith('image/')) {
            alert(`File ${file.name} is not an image.`);
            return false;
        }
        return true;
    });

    // Create previews for valid files
    validFiles.forEach(file => {
        const reader = new FileReader();
        reader.onload = (e) => {
            imagesPreviews.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
        pendingImageFiles.value.push(file);
    });

    // If no primary image is set and this is the first upload, set as primary
    if (primaryImageId.value === null && primaryImageIndex.value === null && imagesPreviews.value.length === validFiles.length) {
        primaryImageIndex.value = 0;
    }
};

const onImagesSelected = (event) => {
    const files = Array.from(event.target.files);
    handleFilesForUpdate(files);
};

const onFilesDrop = (event) => {
    const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('image/'));
    if (files.length > 0) {
        handleFilesForUpdate(files);
    }
};

// Updated remove function
const removeNewImage = (index) => {
    imagesPreviews.value.splice(index, 1);
    pendingImageFiles.value.splice(index, 1);

    // Update primary image index if necessary
    if (primaryImageIndex.value === index) {
        primaryImageIndex.value = null;
        // Default back to an existing image if available
        if (existingImages.value.length > 0 && !primaryImageId.value) {
            primaryImageId.value = existingImages.value[0].id;
        }
    } else if (primaryImageIndex.value !== null && primaryImageIndex.value > index) {
        primaryImageIndex.value--;
    }
};

// Updated submit function
// Modified submit function
const submit = () => {
    // Convert specifications array to object
    const specsObject = specifications.value.reduce((acc, spec) => {
        if (spec.key && spec.value) {
            acc[spec.key] = spec.value;
        }
        return acc;
    }, {});

    // Update the form data
    form.specifications = specsObject;
    form.images = pendingImageFiles.value;
    form.primary_image_index = primaryImageIndex.value;
    form.primary_image_id = primaryImageId.value;

    // Submit using Inertia's form
    form.post(route('admin.products.update', props.product.id) + '?_method=PUT', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Reset file state
            imagesPreviews.value = [];
            pendingImageFiles.value = [];
        }
    });
};

const setNewImageAsPrimary = (index) => {
    primaryImageIndex.value = index;
    primaryImageId.value = null; // Clear existing image primary
    form.primary_image_index = index;
    form.primary_image_id = null;
};

// Barcode Generation
const generateBarcode = () => {
    // Generate a random 6-digit barcode
    const randomDigits = Array.from({ length: 6 }, () => Math.floor(Math.random() * 10));

    // Join digits to create the barcode
    form.barcode = randomDigits.join('');
};

// Generate SKU
const generateSku = () => {
    if (!form.name) return;

    const namePrefix = form.name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase();

    const randomNum = Math.floor(Math.random() * 10000)
        .toString()
        .padStart(4, '0');

    form.sku = `${namePrefix}-${randomNum}`;
};

// Form Submission
</script>
