<template>
    <AdminLayout>
        <Head title="Dashboard" />

        <div class="min-h-[calc(100vh-4rem)] bg-gradient-to-b from-slate-50 to-white dark:from-gray-950 dark:to-gray-900">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                            Dashboard
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ filters.periodLabel }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-sm font-medium transition-colors"
                            :class="preset === 'today'
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-gray-800 dark:text-slate-200 dark:ring-gray-700'"
                            @click="applyPreset('today')"
                        >
                            Today
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-sm font-medium transition-colors"
                            :class="preset === 'month'
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-gray-800 dark:text-slate-200 dark:ring-gray-700'"
                            @click="applyPreset('month')"
                        >
                            This month
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-4 py-2 text-sm font-medium transition-colors"
                            :class="preset === '30d'
                                ? 'bg-indigo-600 text-white shadow-md'
                                : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-gray-800 dark:text-slate-200 dark:ring-gray-700'"
                            @click="applyPreset('30d')"
                        >
                            30 days
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white px-4 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-gray-800 dark:text-slate-200 dark:ring-gray-700"
                            @click="router.reload()"
                        >
                            <ArrowPathIcon class="h-4 w-4" />
                            Refresh
                        </button>
                    </div>
                </div>

                <!-- Metric cards -->
                <div class="mt-10 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="(card, idx) in metricCards"
                        :key="card.key"
                        class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm transition-all hover:border-indigo-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800/80 dark:hover:border-indigo-500/40"
                    >
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-xl text-white shadow-inner"
                            :class="card.iconBg"
                        >
                            <component :is="card.icon" class="h-6 w-6" aria-hidden="true" />
                        </div>
                        <div class="mt-4 flex items-center gap-2">
                            <span
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-700 dark:bg-gray-700 dark:text-slate-200"
                            >
                                {{ idx + 1 }}
                            </span>
                            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                                {{ card.label }}
                            </p>
                        </div>
                        <p class="mt-1 text-2xl font-semibold tabular-nums tracking-tight text-slate-900 dark:text-white">
                            <template v-if="card.format === 'currency'">{{ formatCurrency(stats[card.key]) }}</template>
                            <template v-else>{{ Number(stats[card.key] ?? 0).toLocaleString() }}</template>
                        </p>
                        <p v-if="card.hint" class="mt-2 text-xs text-slate-400 dark:text-slate-500">
                            {{ card.hint }}
                        </p>
                        <div
                            class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full opacity-[0.07] transition-transform group-hover:scale-110"
                            :class="card.blob"
                        />
                    </article>
                </div>

                <!-- Quick links -->
                <h2 class="mt-14 text-lg font-semibold text-slate-900 dark:text-white">
                    Quick links
                </h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="link in quickLinks"
                        :key="link.href"
                        :href="link.href"
                        class="flex items-center gap-4 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm transition-all hover:border-indigo-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800/60 dark:hover:border-indigo-500/50"
                    >
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-slate-300"
                        >
                            <component :is="link.icon" class="h-5 w-5" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-slate-900 dark:text-white">{{ link.title }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ link.sub }}</p>
                        </div>
                        <ChevronRightIcon class="h-5 w-5 shrink-0 text-slate-300 dark:text-slate-600" />
                    </Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import {
    ArrowPathIcon,
    BanknotesIcon,
    ChartBarIcon,
    ChevronRightIcon,
    CurrencyDollarIcon,
    CubeIcon,
    ShoppingCartIcon,
    BuildingStorefrontIcon,
    DocumentChartBarIcon,
} from '@heroicons/vue/24/outline'
import { format, startOfMonth, subDays } from 'date-fns'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { formatCurrency } from '@/utils'

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
})

const preset = computed(() => {
    const s = props.filters?.startDate
    const e = props.filters?.endDate
    if (!s || !e) return 'month'
    const today = format(new Date(), 'yyyy-MM-dd')
    if (s === e && s === today) return 'today'
    const monthStart = format(startOfMonth(new Date()), 'yyyy-MM-dd')
    if (s === monthStart && e === today) return 'month'
    const thirty = format(subDays(new Date(), 30), 'yyyy-MM-dd')
    if (s === thirty && e === today) return '30d'
    return 'custom'
})

const metricCards = computed(() => [
    {
        key: 'buy_total',
        label: 'Total buy',
        format: 'currency',
        icon: ShoppingCartIcon,
        iconBg: 'bg-gradient-to-br from-indigo-500 to-blue-700',
        blob: 'bg-indigo-500',
        hint: 'Purchases in selected period',
    },
    {
        key: 'sales_total',
        label: 'Total sales',
        format: 'currency',
        icon: CurrencyDollarIcon,
        iconBg: 'bg-gradient-to-br from-emerald-500 to-teal-600',
        blob: 'bg-emerald-500',
        hint: `${props.stats.sales_count ?? 0} invoices`,
    },
    {
        key: 'net_profit',
        label: 'Profit',
        format: 'currency',
        icon: BanknotesIcon,
        iconBg: 'bg-gradient-to-br from-violet-500 to-purple-700',
        blob: 'bg-violet-500',
        hint: null,
    },
    {
        key: 'bank_balance',
        label: 'Bank balance (to date)',
        format: 'currency',
        icon: BuildingStorefrontIcon,
        iconBg: 'bg-gradient-to-br from-sky-500 to-blue-700',
        blob: 'bg-sky-500',
        hint: 'Active accounts',
    },

    // Keep the remaining cards after Bank Balance
    {
        key: 'expenses_total',
        label: 'Total expenses',
        format: 'currency',
        icon: ChartBarIcon,
        iconBg: 'bg-gradient-to-br from-rose-500 to-orange-600',
        blob: 'bg-rose-500',
        hint: 'Operating (excl. Fixed Asset)',
    },
    {
        key: 'sales_due',
        label: 'Outstanding (due)',
        format: 'currency',
        icon: DocumentChartBarIcon,
        iconBg: 'bg-gradient-to-br from-amber-500 to-yellow-600',
        blob: 'bg-amber-500',
        hint: 'Sales in selected period',
    },
    {
        key: 'extra_income',
        label: 'Extra income',
        format: 'currency',
        icon: CurrencyDollarIcon,
        iconBg: 'bg-gradient-to-br from-cyan-500 to-indigo-600',
        blob: 'bg-cyan-500',
        hint: null,
    },
    {
        key: 'products_count',
        label: 'Active products',
        format: 'number',
        icon: CubeIcon,
        iconBg: 'bg-gradient-to-br from-slate-600 to-slate-800',
        blob: 'bg-slate-600',
        hint: 'Catalog',
    },
])

const quickLinks = [
    { title: 'POS / Sales', sub: 'New sale', href: '/admin/pos', icon: ShoppingCartIcon },
    { title: 'Sales list', sub: 'All invoices', href: '/admin/sales', icon: DocumentChartBarIcon },
    { title: 'Products', sub: 'Stock & pricing', href: '/admin/products', icon: CubeIcon },
    { title: 'Bank', sub: 'Transactions', href: '/admin/bank-transactions', icon: BanknotesIcon },
]

function applyPreset(key) {
    const end = new Date()
    let start = new Date()
    if (key === 'today') {
        start = end
    } else if (key === 'month') {
        start = startOfMonth(end)
    } else if (key === '30d') {
        start = subDays(end, 30)
    } else {
        return
    }
    router.get(
        route('admin.dashboard'),
        {
            start_date: format(start, 'yyyy-MM-dd'),
            end_date: format(end, 'yyyy-MM-dd'),
        },
        { preserveScroll: true, preserveState: true },
    )
}
</script>
