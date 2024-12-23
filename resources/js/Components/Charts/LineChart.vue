<template>
    <Line
      :data="chartData"
      :options="chartOptions"
    />
  </template>

  <script setup>
  import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
  } from 'chart.js'
  import { Line } from 'vue-chartjs'
  import { computed } from 'vue'

  ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
  )

  const props = defineProps({
    data: {
      type: Array,
      required: true,
      default: () => []
    }
  })

  const chartData = computed(() => ({
    labels: props.data.map(item => item.date),
    datasets: [
      {
        label: 'Amount',
        data: props.data.map(item => item.amount),
        borderColor: '#6366F1',
        backgroundColor: '#6366F1',
        tension: 0.4,
        pointStyle: false
      }
    ]
  }))

  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      intersect: false,
      mode: 'index'
    },
    scales: {
      x: {
        grid: {
          display: false
        },
        ticks: {
          color: '#6B7280'
        }
      },
      y: {
        grid: {
          color: '#E5E7EB'
        },
        ticks: {
          color: '#6B7280',
          callback: value => '৳' + new Intl.NumberFormat('en-BD').format(value)
        }
      }
    },
    plugins: {
      legend: {
        display: false
      },
      tooltip: {
        backgroundColor: 'white',
        titleColor: '#6B7280',
        bodyColor: '#111827',
        borderColor: '#E5E7EB',
        borderWidth: 1,
        padding: 12,
        cornerRadius: 8,
        titleFont: {
          size: 14,
          weight: 'normal'
        },
        bodyFont: {
          size: 14,
          weight: 'bold'
        },
        callbacks: {
          label: context => '৳' + new Intl.NumberFormat('en-BD').format(context.raw)
        }
      }
    }
  }
  </script>

  <style>
  /* Dark mode styles */
  .dark .chartjs-tooltip {
    background-color: #1F2937 !important;
    border-color: #374151 !important;
  }

  .dark .chartjs-tooltip-header {
    color: #9CA3AF !important;
  }

  .dark .chartjs-tooltip-body {
    color: white !important;
  }

  .dark .chartjs-render-monitor {
    filter: invert(0.8) hue-rotate(180deg);
  }
  </style>
