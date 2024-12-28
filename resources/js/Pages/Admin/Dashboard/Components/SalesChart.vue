<template>
    <div class="p-6 bg-white rounded-lg shadow-sm dark:bg-gray-800">
        <h3 class="mb-4 text-lg font-medium dark:text-gray-300">Sales Overview</h3>
        <div class="h-72">
            <LineChart
                :data="chartData"
                :options="chartOptions"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Line as LineChart } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

const props = defineProps({
    data: {
        type: Array,
        required: true
    }
});

const chartData = computed(() => ({
    labels: props.data.map(item => item.created_at),
    datasets: [
        {
            label: 'Sales',
            data: props.data.map(item => item.total),
            borderColor: '#2563EB',
            tension: 0.1
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false
};
</script>
