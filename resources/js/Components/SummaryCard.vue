<template>
    <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800">
        <div class="flex items-center">
            <div class="p-3 bg-blue-100 rounded-full dark:bg-blue-900">
                <component
                    :is="iconComponent"
                    class="w-6 h-6 text-blue-600 dark:text-blue-400"
                />
            </div>
            <div class="ml-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">{{ title }}</h3>
                <p class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ formattedValue }}
                </p>
            </div>
        </div>
    </div>
</template>


<script setup>
import { computed } from 'vue';
import {
    BanknotesIcon,
    ChartBarIcon,
    ArrowTrendingDownIcon,
    WalletIcon,
    ArchiveBoxIcon,
    CubeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    title: {
        type: String,
        required: true
    },
    value: {
        type: Number,
        required: true
    },
    icon: {
        type: String,
        required: true
    },
    type: {
        type: String,
        default: 'currency'
    }
});

const iconComponent = computed(() => {
    const icons = {
        cash: BanknotesIcon,
        'trending-up': ChartBarIcon,
        'trending-down': ArrowTrendingDownIcon,
        wallet: WalletIcon,
        package: ArchiveBoxIcon,
        box: CubeIcon
    };
    return icons[props.icon];
});

const formattedValue = computed(() => {
    if (props.type === 'currency') {
        const formattedNumber = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(props.value);
        return `৳ ${formattedNumber}`;
    }
    return props.value;
});

</script>
