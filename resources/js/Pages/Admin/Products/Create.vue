<template>
    <AdminLayout>
        <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
            <!-- Header -->
            <header class="bg-white dark:bg-gray-800 shadow">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Add New Product</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Create a new product with complete details
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
                    <form @submit.prevent="submit" class="space-y-8 divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- Product Information -->
                        <div class="space-y-8 divide-y divide-gray-200 dark:divide-gray-700 sm:space-y-5 p-8">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    Basic Information
                                </h3>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <!-- Name -->
                                    <div class="sm:col-span-4">
                                        <InputLabel for="name" value="Product Name" required />
                                        <TextInput
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            required
                                        />
                                        <InputError :message="form.errors.name" class="mt-2" />
                                    </div>

                                    <!-- SKU -->
                                    <div class="sm:col-span-2">
                                        <InputLabel for="sku" value="SKU" required />
                                        <TextInput
                                            id="sku"
                                            v-model="form.sku"
                                            type="text"
                                            class="mt-1 block w-full"
                                            required
                                        />
                                        <InputError :message="form.errors.sku" class="mt-2" />
                                    </div>

                                    <!-- Barcode -->
                                    <div class="sm:col-span-3">
                                        <InputLabel for="barcode" value="Barcode" />
                                        <TextInput
                                            id="barcode"
                                            v-model="form.barcode"
                                            type="text"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError :message="form.errors.barcode" class="mt-2" />
                                    </div>

                                    <!-- Category -->
                                    <div class="sm:col-span-3">
                                        <InputLabel for="category_id" value="Category" required />
                                        <select
                                            id="category_id"
                                            v-model="form.category_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        >
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
                                        <select
                                            id="brand_id"
                                            v-model="form.brand_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                        >
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
                                        <select
                                            id="unit_id"
                                            v-model="form.unit_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        >
                                            <option value="">Select Unit</option>
                                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                                {{ unit.name }} ({{ unit.short_name }})
                                            </option>
                                        </select>
                                        <InputError :message="form.errors.unit_id" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Pricing Information -->
                            <div class="pt-8 sm:pt-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    Pricing Information
                                </h3>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <!-- Cost Price -->
                                    <div class="sm:col-span-2">
                                        <InputLabel for="cost_price" value="Cost Price" required />
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">$</span>
                                            </div>
                                            <TextInput
                                                id="cost_price"
                                                v-model="form.cost_price"
                                                type="number"
                                                step="0.01"
                                                class="pl-7 block w-full"
                                                required
                                            />
                                        </div>
                                        <InputError :message="form.errors.cost_price" class="mt-2" />
                                    </div>

                                    <!-- Selling Price -->
                                    <div class="sm:col-span-2">
                                        <InputLabel for="selling_price" value="Selling Price" required />
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 sm:text-sm">$</span>
                                            </div>
                                            <TextInput
                                                id="selling_price"
                                                v-model="form.selling_price"
                                                type="number"
                                                step="0.01"
                                                class="pl-7 block w-full"
                                                required
                                            />
                                        </div>
                                        <InputError :message="form.errors.selling_price" class="mt-2" />
                                    </div>

                                    <!-- Alert Quantity -->
                                    <div class="sm:col-span-2">
                                        <InputLabel for="alert_quantity" value="Alert Quantity" required />
                                        <TextInput
                                            id="alert_quantity"
                                            v-model="form.alert_quantity"
                                            type="number"
                                            min="0"
                                            class="mt-1 block w-full"
                                            required
                                        />
                                        <InputError :message="form.errors.alert_quantity" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Description and Specifications -->
                            <div class="pt-8 sm:pt-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    Description & Specifications
                                </h3>
                                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4">
                                    <!-- Description -->
                                    <div>
                                        <InputLabel for="description" value="Description" />
                                        <textarea
                                            id="description"
                                            v-model="form.description"
                                            rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        />
                                        <InputError :message="form.errors.description" class="mt-2" />
                                    </div>

                                    <!-- Specifications -->
                                    <div>
                                        <InputLabel value="Specifications" />
                                        <div class="mt-2 space-y-4">
                                            <div v-for="(spec, index) in specifications" :key="index"
                                                class="flex items-center gap-4">
                                                <TextInput
                                                    v-model="spec.key"
                                                    type="text"
                                                    class="w-1/3"
                                                    placeholder="Key"
                                                />
                                                <TextInput
                                                    v-model="spec.value"
                                                    type="text"
                                                    class="w-1/2"
                                                    placeholder="Value"
                                                />
                                                <button type="button" @click="removeSpecification(index)"
                                                    class="text-red-600 hover:text-red-900">
                                                    <TrashIcon class="h-5 w-5" />
                                                </button>
                                            </div>
                                            <SecondaryButton type="button" @click="addSpecification">
                                                Add Specification
                                            </SecondaryButton>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Images -->
                            <div class="pt-8 sm:pt-5">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                    Product Images
                                </h3>
                                <div class="mt-6">
                                    <div class="flex items-center justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md">
                                        <div class="space-y-1 text-center">
                                            <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
                                            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                                <label class="relative cursor-pointer rounded-md font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none">
                                                    <span>Upload images</span>
                                                    <input
                                                        type="file"
                                                        multiple
                                                        class="sr-only"
                                                        accept="image/*"
                                                        @change="onImagesSelected"
                                                    >
                                                </label>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                PNG, JPG, GIF up to 2MB
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Image Previews -->
                                    <div v-if="imagesPreviews.length > 0" class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                                        <div v-for="(preview, index) in imagesPreviews" :key="index"
                                            class="relative group">
                                            <img :src="preview"
                                                class="h-24 w-24 rounded-lg object-cover"
                                            >
                                            <button type="button"
                                                @click="removeImage(index)"
                                                class="absolute top-0 right-0 hidden group-hover:flex -mt-2 -mr-2 h-6 w-6 items-center justify-center rounded-full bg-red-600 text-white hover:bg-red-700 focus:outline-none">
                                                <XMarkIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="p-8 flex justify-end space-x-3">
                            <Link :href="route('admin.products.index')"
                                class="inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 py-2 px-4 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                Create Product
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    ArrowLeftIcon,
    PhotoIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    categories: Array,
    brands: Array,
    units: Array
});

// Form State
const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    category_id: '',
    brand_id: '',
    unit_id: '',
    cost_price: '',
    selling_price: '',
    alert_quantity: 0,
    description: '',
    specifications: {},
    images: [],
    status: true
});

// Specifications Management
const specifications = ref([{ key: '', value: '' }]);

const addSpecification = () => {
    specifications.value.push({ key: '', value: '' });
};

const removeSpecification = (index) => {
    specifications.value.splice(index, 1);
};

// Image Management
const imagesPreviews = ref([]);
const selectedImages = ref([]);

const onImagesSelected = (event) => {
    const files = Array.from(event.target.files);

    // Validate files
    const validFiles = files.filter(file => {
        // Check file size (2MB limit)
        if (file.size > 2 * 1024 * 1024) {
            alert(`File ${file.name} is too large. Maximum size is 2MB.`);
            return false;
        }
        // Check file type
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
        selectedImages.value.push(file);
    });

    form.images = [...form.images, ...validFiles];
};

const removeImage = (index) => {
    imagesPreviews.value.splice(index, 1);
    selectedImages.value.splice(index, 1);
    form.images.splice(index, 1);
};

// Form Submission
const submit = () => {
    // Convert specifications array to object
    const specsObject = specifications.value.reduce((acc, spec) => {
        if (spec.key && spec.value) {
            acc[spec.key] = spec.value;
        }
        return acc;
    }, {});

    form.specifications = specsObject;

    form.post(route('admin.products.store'), {
        onSuccess: () => {
            // Reset form state
            specifications.value = [{ key: '', value: '' }];
            imagesPreviews.value = [];
            selectedImages.value = [];
        },
        preserveScroll: true,
        forceFormData: true
    });
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

// Watch for name changes to suggest SKU
watch(() => form.name, (newValue) => {
    if (newValue && !form.sku) {
        generateSku();
    }
}, { immediate: true });

// Validate selling price is greater than cost price
watch(() => form.selling_price, (newValue) => {
    if (newValue && form.cost_price && parseFloat(newValue) < parseFloat(form.cost_price)) {
        alert('Selling price cannot be less than cost price');
        form.selling_price = form.cost_price;
    }
});
</script>
