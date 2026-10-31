<script setup>
import { computed } from 'vue';
import DateInput from '@/Components/DateInput.vue';

const props = defineProps({
    modelValue: {
        type: [String, Number], // Accept both String and Number
        default: ''
    },
    type: {
        type: String,
        default: 'text'
    },
    placeholder: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    readonly: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false
    },
    min: {
        type: [String, Number],
        default: null
    },
    max: {
        type: [String, Number],
        default: null
    },
    step: {
        type: [String, Number],
        default: null
    },
    class: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'input', 'change']);

// Convert modelValue to string for input display
const inputValue = computed({
    get() {
        return String(props.modelValue || '');
    },
    set(value) {
        emit('update:modelValue', value);
    }
});

// Base classes for the input
const baseClasses = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm';

// Combine base classes with custom classes
const inputClasses = computed(() => {
    return `${baseClasses} ${props.class}`.trim();
});

const onTextInput = (event) => {
    inputValue.value = event.target.value;
    emit('input', event);
};

const onDate = (value) => {
    emit('update:modelValue', value ?? '');
    emit('input', value ?? '');
    emit('change', value ?? '');
};
</script>

<template>
    <DateInput
        v-if="type === 'date'"
        :model-value="modelValue"
        :min="min == null ? '' : String(min)"
        :max="max == null ? '' : String(max)"
        :required="required"
        :disabled="disabled"
        :class="inputClasses"
        :placeholder="placeholder || 'dd-mm-yyyy'"
        @update:model-value="onDate"
    />
    <input
        v-else
        :type="type"
        :value="inputValue"
        @input="onTextInput"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :min="min"
        :max="max"
        :step="step"
        :class="inputClasses"
    />
</template>

<style scoped>
/* Add any additional styling if needed */
input:focus {
    outline: none;
}

input:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

input:readonly {
    background-color: #f9fafb;
}
</style>
