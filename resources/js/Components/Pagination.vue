<template>
    <div v-if="links.length > 3" class="flex flex-wrap -mb-1">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="mr-1 mb-1 px-4 py-2 text-sm text-gray-500 dark:text-gray-400 border rounded"
                :class="{ 'ml-auto': link.label === 'Next &raquo;' }"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="mr-1 mb-1 px-4 py-2 text-sm border rounded focus:outline-none"
                :class="{
                    'bg-indigo-600 text-white dark:bg-indigo-500': link.active,
                    'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700': !link.active,
                    'ml-auto': link.label === 'Next &raquo;'
                }"
                preserve-scroll
                v-html="link.label"
            />
        </template>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true
    }
});
</script>
