<!-- Components/Barcode.vue -->
<template>
    <div ref="barcodeRef"></div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import JsBarcode from 'jsbarcode'

const props = defineProps({
    value: {
        type: String,
        required: true
    },
    options: {
        type: Object,
        default: () => ({})
    }
})

const barcodeRef = ref(null)

const generateBarcode = () => {
    if (barcodeRef.value) {
        try {
            JsBarcode(barcodeRef.value, props.value, {
                format: 'CODE128',
                width: 1.5,
                height: 40,
                displayValue: true,
                ...props.options
            })
        } catch (error) {
            console.error('Error generating barcode:', error)
        }
    }
}

onMounted(generateBarcode)

watch(() => props.value, generateBarcode)
</script>
