<!-- resources/js/Components/DateRangePicker.vue -->
<template>
    <div class="flex items-center space-x-4">
        <div class="flex items-center space-x-2">
            <label class="text-sm font-medium text-gray-700">Start Date:</label>
            <input
                type="date"
                v-model="localStartDate"
                class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="emitUpdate"
            >
        </div>
        <div class="flex items-center space-x-2">
            <label class="text-sm font-medium text-gray-700">End Date:</label>
            <input
                type="date"
                v-model="localEndDate"
                class="px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                @change="emitUpdate"
            >
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    startDate: {
        type: Date,
        required: true
    },
    endDate: {
        type: Date,
        required: true
    }
});

const emit = defineEmits(['update:startDate', 'update:endDate', 'update:range']);

const localStartDate = ref(formatDate(props.startDate));
const localEndDate = ref(formatDate(props.endDate));

function formatDate(date) {
    return date.toISOString().split('T')[0];
}

function emitUpdate() {
    emit('update:startDate', new Date(localStartDate.value));
    emit('update:endDate', new Date(localEndDate.value));
    emit('update:range');
}

watch(() => props.startDate, (newVal) => {
    localStartDate.value = formatDate(newVal);
});

watch(() => props.endDate, (newVal) => {
    localEndDate.value = formatDate(newVal);
});
</script>
