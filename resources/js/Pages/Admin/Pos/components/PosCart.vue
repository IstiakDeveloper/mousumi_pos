# resources/js/Pages/Admin/Pos/components/PosCart.vue
<template>
    <div class="h-2/2 mt-4 overflow-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Product
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Price
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Qty
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Subtotal
                    </th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr v-for="(item, index) in items"
                    :key="index"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-200">
                            {{ item.name }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ item.sku }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right text-sm text-gray-500 dark:text-gray-400">
                        ৳{{ formatNumber(item.unit_price) }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center justify-end space-x-2">
                            <button @click="decrementQuantity(index)"
                                class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600">
                                <MinusCircleIcon class="w-4 h-4" />
                            </button>
                            <input type="number"
                                v-model.number="item.quantity"
                                class="w-16 text-right border rounded p-1 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200"
                                min="1"
                                @change="$emit('update-quantity', index, item.quantity)" />
                            <button @click="incrementQuantity(index)"
                                class="p-1 rounded-md hover:bg-gray-100 dark:hover:bg-gray-600">
                                <PlusCircleIcon class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium text-gray-900 dark:text-gray-200">
                        ৳{{ formatNumber(item.quantity * item.unit_price) }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <button @click="$emit('remove-item', index)"
                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                            <TrashIcon class="w-5 h-5" />
                        </button>
                    </td>
                </tr>
                <tr v-if="items.length === 0">
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        <ShoppingCartIcon class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-600" />
                        Cart is empty
                    </td>
                </tr>
            </tbody>
            <tfoot v-if="items.length > 0" class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <td colspan="3" class="px-6 py-3 text-right text-sm font-medium text-gray-500 dark:text-gray-400">
                        Total Items: {{ items.length }}
                    </td>
                    <td class="px-6 py-3 text-right text-sm font-medium text-gray-900 dark:text-gray-200">
                        ৳{{ formatNumber(totalAmount) }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
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
