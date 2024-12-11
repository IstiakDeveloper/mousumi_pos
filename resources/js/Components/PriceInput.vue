<!-- resources/js/Components/PriceInput.vue -->
<template>
    <div class="relative mt-1">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <span class="text-gray-500 sm:text-sm dark:text-gray-400">৳</span>
        </div>
        <input
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            type="number"
            step="0.01"
            min="0"
            :placeholder="placeholder"
            :required="required"
            class="block w-full rounded-md border-gray-300 pl-7 pr-12 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300"
        >
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <span class="text-gray-500 sm:text-sm dark:text-gray-400">BDT</span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    modelValue: [String, Number],
    placeholder: String,
    required: {
        type: Boolean,
        default: false
    }
});

defineEmits(['update:modelValue']);
</script>
