<template>
    <div class="p-6 bg-white rounded-lg shadow-sm">
        <h3 class="mb-4 text-lg font-medium">Stock Status</h3>
        <div class="h-72">
            <BarChart
                :data="chartData"
                :options="chartOptions"
            />
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Bar as BarChart } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend);

const props = defineProps({
    data: {
        type: Array,
        required: true
    }
});

const chartData = computed(() => ({
    labels: props.data.map(item => item.product.name),
    datasets: [
        {
            label: 'Stock Quantity',
            data: props.data.map(item => item.total_quantity),
            backgroundColor: '#3B82F6'
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false
};
</script>
