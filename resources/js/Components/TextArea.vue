<script setup>
import { useAttrs, computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    rows: {
        type: [String, Number],
        default: 3
    },
    error: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false
    },
    maxlength: {
        type: Number,
        default: null
    },
    showCount: {
        type: Boolean,
        default: false
    },
    resize: {
        type: String,
        default: 'vertical', // none, vertical, horizontal, both
        validator: (value) => ['none', 'vertical', 'horizontal', 'both'].includes(value)
    }
})

const emit = defineEmits(['update:modelValue'])

const attrs = useAttrs()

const textareaClasses = computed(() => {
    return [
        'block w-full rounded-md shadow-sm transition duration-150 ease-in-out',
        'dark:bg-gray-800 dark:text-gray-300',
        'focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-900',
        {
            'border-gray-300 dark:border-gray-600': !props.error,
            'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500': props.error,
            'bg-gray-100 dark:bg-gray-700 cursor-not-allowed': props.disabled,
            'resize-none': props.resize === 'none',
            'resize-y': props.resize === 'vertical',
            'resize-x': props.resize === 'horizontal',
            'resize': props.resize === 'both'
        }
    ]
})

const characterCount = computed(() => {
    return props.modelValue?.length || 0
})

const handleInput = (event) => {
    emit('update:modelValue', event.target.value)
}
</script>

<template>
    <div class="relative">
        <!-- Label -->
        <label
            v-if="label"
            :for="attrs.id"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
        >
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <!-- Textarea -->
        <textarea
            :id="attrs.id"
            :value="modelValue"
            :placeholder="placeholder"
            :rows="rows"
            :disabled="disabled"
            :required="required"
            :maxlength="maxlength"
            @input="handleInput"
            v-bind="{ ...attrs, class: undefined }"
            :class="[
                textareaClasses,
                attrs.class
            ]"
        ></textarea>

        <!-- Character Counter -->
        <div
            v-if="showCount && maxlength"
            class="absolute bottom-2 right-2 text-xs text-gray-500 dark:text-gray-400"
        >
            {{ characterCount }}/{{ maxlength }}
        </div>

        <!-- Error Message -->
        <p
            v-if="error"
            class="mt-1 text-sm text-red-600 dark:text-red-400"
        >
            {{ error }}
        </p>

        <!-- Help Text (from slot) -->
        <slot name="help">
            <p
                v-if="$slots.help"
                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
            >
                <slot name="help"></slot>
            </p>
        </slot>
    </div>
</template>

<style scoped>
textarea {
    min-height: 2.5rem;
}

textarea:disabled {
    opacity: 0.7;
}

/* Custom scrollbar for Webkit browsers */
textarea::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

textarea::-webkit-scrollbar-track {
    background: transparent;
}

textarea::-webkit-scrollbar-thumb {
    background-color: rgba(156, 163, 175, 0.5);
    border-radius: 4px;
}

textarea::-webkit-scrollbar-thumb:hover {
    background-color: rgba(156, 163, 175, 0.7);
}

/* For Firefox */
textarea {
    scrollbar-width: thin;
    scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}
</style>
