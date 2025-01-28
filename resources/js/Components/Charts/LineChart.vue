<template>
    <div class="w-full h-72">
      <ResponsiveContainer>
        <LineChart :data="chartData">
          <XAxis dataKey="date" />
          <YAxis :tickFormatter="formatCurrency" />
          <Tooltip
            :content="renderTooltip"
            :formatter="formatCurrency"
          />
          <Legend />
          <Line
            type="monotone"
            dataKey="total_amount"
            stroke="#4F46E5"
            name="Sales"
          />
          <Line
            type="monotone"
            dataKey="daily_profit"
            stroke="#059669"
            name="Profit"
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  </template>

  <script setup>
  import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    Tooltip,
    Legend,
    ResponsiveContainer
  } from 'recharts'
  import { h, computed } from 'vue'
  import { formatCurrency } from '@/utils'

  const props = defineProps({
    data: {
      type: Array,
      required: true,
      default: () => []
    }
  })

  const chartData = computed(() => props.data)

  const renderTooltip = ({ active, payload, label }) => {
    if (active && payload && payload.length) {
      return h('div', {
        class: 'bg-white p-3 border shadow-sm rounded-lg'
      }, [
        h('p', { class: 'text-sm font-medium' }, label),
        ...payload.map(entry =>
          h('p', { class: 'text-sm' }, [
            h('span', { class: 'font-medium' }, `${entry.name}: `),
            formatCurrency(entry.value)
          ])
        )
      ])
    }
    return null
  }
  </script>

  <style scoped>
  .recharts-responsive-container {
    min-height: 300px;
  }
  </style>
