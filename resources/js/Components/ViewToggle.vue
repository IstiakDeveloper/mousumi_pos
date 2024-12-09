
<template>
    <div class="flex rounded-lg shadow-sm">
        <button
            @click="$emit('update:modelValue', 'grid')"
            :class="[
                modelValue === 'grid'
                    ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                    : 'bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                'relative inline-flex items-center px-3 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 dark:ring-gray-600 rounded-l-md focus:z-10'
            ]"
        >
            <Squares2X2Icon class="h-5 w-5" />
        </button>
        <button
            @click="$emit('update:modelValue', 'table')"
            :class="[
                modelValue === 'table'
                    ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                    : 'bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                'relative -ml-px inline-flex items-center px-3 py-2 text-sm font-semibold ring-1 ring-inset ring-gray-300 dark:ring-gray-600 rounded-r-md focus:z-10'
            ]"
        >
            <ListBulletIcon class="h-5 w-5" />
        </button>
    </div>
</template>

<script setup>
import { Squares2X2Icon, ListBulletIcon } from '@heroicons/vue/24/outline';

defineProps({
    modelValue: String
});
defineEmits(['update:modelValue']);
</script>
