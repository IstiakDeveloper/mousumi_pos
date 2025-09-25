<template>
    <div class="flex flex-col h-full bg-white border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-700">
        <!-- Cart Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Shopping Cart</h2>
                <span class="px-2.5 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-400
                           rounded-full text-sm font-medium">
                    {{ items.length }} Items
                </span>
            </div>
        </div>

        <!-- Cart Content -->
        <div class="flex-1 overflow-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase dark:text-gray-400">
                            Product
                        </th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">
                            Price
                        </th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">
                            Qty
                        </th>
                        <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase dark:text-gray-400">
                            Subtotal
                        </th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                    <tr v-for="(item, index) in items"
                        :key="index"
                        class="transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 overflow-hidden bg-gray-100 rounded-lg dark:bg-gray-600">
                                    <img v-if="item.image" :src="item.image" :alt="item.name"
                                         class="object-cover w-full h-full" />
                                    <span v-else class="text-gray-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-200">
                                        {{ item.name }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ item.sku }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-500 whitespace-nowrap dark:text-gray-400">
                            ৳{{ formatNumber(item.unit_price) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center justify-end space-x-2">
                                <button @click="decrementQuantity(index)"
                                    class="p-1 text-gray-500 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                    </svg>
                                </button>
                                <input type="number"
                                    v-model.number="item.quantity"
                                    class="w-16 p-1 text-center border rounded dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 focus:ring-blue-500 focus:border-blue-500"
                                    min="1"
                                    @change="$emit('update-quantity', index, item.quantity)" />
                                <button @click="incrementQuantity(index)"
                                    class="p-1 text-gray-500 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-right text-gray-900 whitespace-nowrap dark:text-gray-200">
                            ৳{{ formatNumber(item.quantity * item.unit_price) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                            <button @click="$emit('remove-item', index)"
                                class="text-red-600 transition-colors duration-150 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="items.length === 0">
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-600"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Cart is empty
                        </td>
                    </tr>
                </tbody>
                <tfoot v-if="items.length > 0" class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-sm font-medium text-right text-gray-500 dark:text-gray-400">
                            Total Items: {{ items.length }}
                        </td>
                        <td class="px-6 py-3 text-sm font-medium text-right text-gray-900 dark:text-gray-200">
                            ৳{{ formatNumber(totalAmount) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import {
    MinusCircle as MinusCircleIcon,
    PlusCircle as PlusCircleIcon,
    Trash as TrashIcon,
    ShoppingCart as ShoppingCartIcon
} from 'lucide-vue-next'

const props = defineProps({
    items: {
        type: Array,
        required: true
    }
})

const emit = defineEmits(['update-quantity', 'remove-item'])

const totalAmount = computed(() => {
    return props.items.reduce((sum, item) => sum + (item.quantity * item.unit_price), 0)
})

const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}

const incrementQuantity = (index) => {
    emit('update-quantity', index, props.items[index].quantity + 1)
}

const decrementQuantity = (index) => {
    if (props.items[index].quantity > 1) {
        emit('update-quantity', index, props.items[index].quantity - 1)
    }
}
</script>
