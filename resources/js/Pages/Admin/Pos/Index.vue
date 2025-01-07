<template>
    <AdminLayout title="Point of Sale">
        <div class="flex h-[calc(100vh-64px)] dark:bg-gray-900">
            <!-- Left Side -->
            <div class="w-[60%] flex flex-col p-4 border-r border-gray-200 dark:border-gray-700 h-full">
                <!-- Search Component -->
                <PosSearch :categories="categories" :selected-category="selectedCategory" @search="handleSearch"
                    @filter="handleCategoryFilter" @add-scanned-product="handleScannedProduct" />

                <!-- Product Grid with 50% height and scrollable -->
                <div class="h-2/3 flex-1 overflow-auto" ref="productGridContainer">
                    <PosProductGrid :products="displayProducts" :loading="loading" @add-to-cart="addToCart" />
                </div>

                <!-- Cart with 50% height and scrollable -->
                <div class="h-1/3 overflow-auto mt-4" ref="cartContainer">
                    <PosCart :items="cartItems" @update-quantity="updateCartItemQuantity"
                        @remove-item="removeCartItem" />
                </div>
            </div>

            <!-- Right Side -->
            <div class="w-[40%] flex flex-col p-4 bg-gray-50 dark:bg-gray-800">
                <PosPayment :customers="customers" :bank-accounts="bankAccounts" :cart-items="cartItems"
                    :cart-summary="cartSummary" @process-sale="processSale" @reset-cart="resetCart" />
            </div>
        </div>

        <PosSuccessModal v-if="showSuccessModal" :sale="lastSale" @close="closeSuccessModal" @print="printReceipt" />
    </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PosSearch from './components/PosSearch.vue'
import PosProductGrid from './components/PosProductGrid.vue'
import PosCart from './components/PosCart.vue'
import PosPayment from './components/PosPayment.vue'
import PosSuccessModal from './components/PosSuccessModal.vue'
import { Howl } from 'howler'

const props = defineProps({
    customers: Array,
    bankAccounts: Array,
    categories: Array
})

// State
const searchQuery = ref('')
const products = ref([])
const searchResults = ref([])
const cartItems = ref([])
const selectedCategory = ref(null)
const loading = ref(false)
const showSuccessModal = ref(false)
const lastSale = ref(null)

// Computed
const displayProducts = computed(() => {
    return searchQuery.value ? searchResults.value : products.value
})

const cartSummary = computed(() => {
    const subtotal = cartItems.value.reduce((sum, item) =>
        sum + (item.quantity * item.unit_price), 0
    )
    return {
        subtotal,
        items: cartItems.value.length
    }
})

// Methods
const fetchProducts = async () => {
    loading.value = true
    try {
        const response = await axios.get(route('admin.pos.products'));
        products.value = response.data;
    } catch (error) {
        console.error('Error fetching products:', error);
    } finally {
        loading.value = false;
    }
};

const handleSearch = async (query) => {
    searchQuery.value = query;
    if (!query) {
        searchResults.value = [];
        return;
    }

    loading.value = true;
    try {
        const response = await axios.get(route('admin.pos.search-products'), {
            params: { search: query }
        });
        searchResults.value = response.data;
    } catch (error) {
        console.error('Error searching products:', error);
    } finally {
        loading.value = false;
    }
};

const handleCategoryFilter = async (categoryId) => {
    selectedCategory.value = categoryId;
    loading.value = true;
    try {
        const response = await axios.get(route('admin.pos.products.by.category'), {
            params: { category_id: categoryId }
        });
        products.value = response.data;
        searchQuery.value = '';
    } catch (error) {
        console.error('Error filtering products:', error);
    } finally {
        loading.value = false;
    }
};

const processSale = (saleData) => {
    router.post(route('admin.pos.store'), saleData, {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash?.sale) {
                lastSale.value = page.props.flash.sale;
                new Howl({ src: ['/sounds/success.mp3'] }).play();
                showSuccessModal.value = true;
            }
        }
    });
};

const printReceipt = (saleId) => {
    window.open(route('admin.pos.print-receipt', saleId), '_blank');
};

// Cart Methods
const addToCart = (product) => {
    const beepSound = new Howl({ src: ['/sounds/beep.mp3'] })
    beepSound.play()

    const existingItem = cartItems.value.find(item => item.product_id === product.id)
    if (existingItem) {
        existingItem.quantity++
    } else {
        cartItems.value.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            unit_price: Number(product.selling_price),
            quantity: 1
        })
    }
}

const handleScannedProduct = (product) => {
    console.log('Adding scanned product to cart:', product);
    addToCart(product);
};

const updateCartItemQuantity = (index, quantity) => {
    if (quantity > 0) {
        cartItems.value[index].quantity = quantity
    }
}

const removeCartItem = (index) => {
    cartItems.value.splice(index, 1)
}

const resetCart = () => {
    cartItems.value = []
    selectedCategory.value = null
    searchQuery.value = ''
}


const closeSuccessModal = () => {
    showSuccessModal.value = false
    resetCart()
}


// Lifecycle Hooks
onMounted(() => {
    fetchProducts()
})
</script>

<style>
.dark {
    color-scheme: dark;
}
</style>
