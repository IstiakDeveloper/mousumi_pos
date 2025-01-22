<template>
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button
                v-for="tab in tabs"
                :key="tab.name"
                @click="updateModelValue(tab.name)"
                :class="[
                    tab.name === modelValue
                        ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                        : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300',
                    'group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium'
                ]"
            >
                {{ tab.name }}
                <span
                    v-if="tab.badge"
                    :class="[
                        tab.name === modelValue
                            ? 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900 dark:text-indigo-400'
                            : 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-300',
                        'ml-3 hidden rounded-full py-0.5 px-2.5 text-xs font-medium md:inline-block'
                    ]"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </nav>
    </div>
    <div class="mt-4">
        <slot></slot>
    </div>
</template>

<script setup>
import { useSlots, provide, computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        required: true
    }
})

const emit = defineEmits(['update:modelValue'])

const slots = useSlots()

const tabs = computed(() => {
    return slots.default()
        .filter(node => node.type?.name === 'Tab')
        .map(node => ({
            name: node.props?.name,
            badge: node.props?.badge
        }))
})

const updateModelValue = (value) => {
    emit('update:modelValue', value)
}

// Provide active tab name to child components
provide('activeTab', computed(() => props.modelValue))
</script>

# /Components/ui/tabs/Tab.vue
<template>
    <div v-show="isActive">
        <slot></slot>
    </div>
</template>

<script setup>
import { computed, inject } from 'vue'

const props = defineProps({
    name: {
        type: String,
        required: true
    },
    badge: {
        type: [Number, String],
        default: null
    }
})

const activeTab = inject('activeTab')
const isActive = computed(() => activeTab.value === props.name)
</script>
