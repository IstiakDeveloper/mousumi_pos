<template>
    <TransitionRoot appear :show="true" as="template">
        <Dialog as="div" class="relative z-10" @close="$emit('close')">
            <TransitionChild as="template"
                enter="ease-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-200"
                leave-from="opacity-100"
                leave-to="opacity-0">
                <div class="fixed inset-0 bg-black bg-opacity-25" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <TransitionChild as="template"
                        enter="ease-out duration-300"
                        enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100"
                        leave="ease-in duration-200"
                        leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all">
                            <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">
                                Sale Completed Successfully!
                            </DialogTitle>

                            <div class="mt-4">
                                <div class="space-y-2">
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Invoice No: {{ sale.invoice_no }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Total Amount: ৳{{ formatNumber(sale.total) }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        Paid Amount: ৳{{ formatNumber(sale.paid) }}
                                    </div>
                                    <div v-if="sale.due > 0" class="text-sm text-yellow-600 dark:text-yellow-400">
                                        Due Amount: ৳{{ formatNumber(sale.due) }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button @click="$emit('print', sale.id)"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-blue-100 dark:bg-blue-900 px-4 py-2 text-sm font-medium text-blue-900 dark:text-blue-100 hover:bg-blue-200 dark:hover:bg-blue-800">
                                    <PrinterIcon class="w-4 h-4 mr-2" />
                                    Print Receipt
                                </button>
                                <button @click="$emit('close')"
                                    class="inline-flex justify-center rounded-md border border-transparent bg-green-100 dark:bg-green-900 px-4 py-2 text-sm font-medium text-green-900 dark:text-green-100 hover:bg-green-200 dark:hover:bg-green-800">
                                    <PlusCircleIcon class="w-4 h-4 mr-2" />
                                    New Sale
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import {
    Printer as PrinterIcon,
    PlusCircle as PlusCircleIcon
} from 'lucide-vue-next'

const props = defineProps({
    sale: {
        type: Object,
        required: true
    }
})

defineEmits(['close', 'print'])

const formatNumber = (value) => {
    return Number(value).toLocaleString('en-BD', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })
}
</script>
