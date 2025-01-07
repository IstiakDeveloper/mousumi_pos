<template>
    <div class="w-full" :style="{ height: `${height}px` }">
        <ResponsiveContainer width="100%" :height="height">
            <ComposedChart :data="data">
                <XAxis dataKey="month" />
                <YAxis />
                <CartesianGrid strokeDasharray="3 3" />
                <Tooltip
                    :formatter="formatterFunction"
                />
                <Legend />

                <Line
                    v-for="line in lines"
                    :key="line.dataKey"
                    type="monotone"
                    :dataKey="line.dataKey"
                    :stroke="line.stroke"
                    :name="line.name || line.dataKey"
                    :dot="false"
                />
            </ComposedChart>
        </ResponsiveContainer>
    </div>
</template>

<script setup>
import {
    ResponsiveContainer,
    ComposedChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend
} from 'recharts'

const props = defineProps({
    data: {
        type: Array,
        required: true
    },
    lines: {
        type: Array,
        default: () => [
            {
                dataKey: 'total_amount',
                stroke: '#3B82F6',
                name: 'Total Sales'
            },
            {
                dataKey: 'paid_amount',
                stroke: '#10B981',
                name: 'Paid Amount'
            }
        ]
    },
    height: {
        type: Number,
        default: 300
    }
})

// Formatter function for tooltip
const formatterFunction = (value, name) => {
    return [
        `৳${new Intl.NumberFormat('en-BD').format(value)}`,
        name
    ]
}
</script>
