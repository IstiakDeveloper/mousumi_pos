<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Sidebar -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-50 w-72 transition-transform duration-300 ease-in-out flex flex-col',
            'bg-gradient-to-b from-indigo-700 via-indigo-800 to-indigo-900 dark:from-gray-800 dark:via-gray-900 dark:to-black',
            isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]">
            <!-- Fixed Header -->
            <div class="flex-shrink-0">
                <div
                    class="h-16 flex items-center justify-between px-6 bg-indigo-800/50 dark:bg-gray-800/50 backdrop-blur-sm">
                    <Link href="/admin/dashboard" class="flex items-center space-x-3">
                    <div class="p-2 bg-white/10 dark:bg-gray-700 rounded-lg backdrop-blur-sm">
                        <img src="/logo.svg" alt="Logo" class="h-8 w-8" />
                    </div>
                    <span
                        class="text-xl font-bold bg-gradient-to-r from-white to-indigo-200 dark:from-gray-100 dark:to-gray-400 bg-clip-text text-transparent">
                        Admin Panel
                    </span>
                    </Link>
                    <button
                        class="lg:hidden p-2 rounded-lg hover:bg-white/10 dark:hover:bg-gray-700 text-white/80 dark:text-gray-400 hover:text-white transition-colors"
                        @click="toggleSidebar">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Scrollable Navigation -->
            <div class="flex-1 overflow-y-auto">
                <nav class="mt-6 px-4">
                    <div class="space-y-2">
                        <!-- Single Items -->
                        <Link v-for="item in singleNavItems" :key="item.name" :href="item.href" :class="[
                            'flex items-center px-4 py-3 text-sm rounded-xl transition-all duration-200',
                            'hover:bg-white/10 dark:hover:bg-gray-700 backdrop-blur-sm group',
                            isActivePath(item.href)
                                ? 'bg-white/15 dark:bg-gray-700 text-white shadow-lg shadow-indigo-900/20'
                                : 'text-indigo-100 dark:text-gray-400 hover:text-white dark:hover:text-gray-100'
                        ]">
                        <div :class="[
                            'p-2 rounded-lg mr-3 transition-all duration-200',
                            isActivePath(item.href)
                                ? 'bg-white/20 dark:bg-gray-600 text-white'
                                : 'bg-white/10 dark:bg-gray-700 text-indigo-200 dark:text-gray-400 group-hover:bg-white/15 group-hover:dark:bg-gray-600 group-hover:text-white'
                        ]">
                            <i :class="['fas', item.icon, 'w-4 h-4']"></i>
                        </div>
                        <span class="font-medium">{{ item.name }}</span>
                        </Link>

                        <!-- Dropdown Items -->
                        <div v-for="(group, index) in dropdownNavItems" :key="index" class="space-y-1">
                            <button @click="toggleDropdown(group.name)" :class="[
                                'w-full flex items-center justify-between px-4 py-3 text-sm rounded-xl transition-all duration-200',
                                'hover:bg-white/10 dark:hover:bg-gray-700 backdrop-blur-sm group',
                                isDropdownActive(group.name)
                                    ? 'bg-white/15 dark:bg-gray-700 text-white shadow-lg shadow-indigo-900/20'
                                    : 'text-indigo-100 dark:text-gray-400 hover:text-white dark:hover:text-gray-100'
                            ]">
                                <div class="flex items-center">
                                    <div :class="[
                                        'p-2 rounded-lg mr-3 transition-all duration-200',
                                        isDropdownActive(group.name)
                                            ? 'bg-white/20 dark:bg-gray-600 text-white'
                                            : 'bg-white/10 dark:bg-gray-700 text-indigo-200 dark:text-gray-400 group-hover:bg-white/15 group-hover:dark:bg-gray-600 group-hover:text-white'
                                    ]">
                                        <i :class="['fas', group.icon, 'w-4 h-4']"></i>
                                    </div>
                                    <span class="font-medium">{{ group.name }}</span>
                                </div>
                                <i :class="[
                                    'fas fa-chevron-right transition-transform duration-200',
                                    activeDropdowns.includes(group.name) && 'rotate-90'
                                ]"></i>
                            </button>

                            <!-- Dropdown Content -->
                            <TransitionRoot :show="activeDropdowns.includes(group.name)"
                                enter="transition-all duration-300 ease-in-out"
                                enter-from="transform opacity-0 scale-95 -translate-y-2"
                                enter-to="transform opacity-100 scale-100 translate-y-0"
                                leave="transition-all duration-200 ease-in-out"
                                leave-from="transform opacity-100 scale-100 translate-y-0"
                                leave-to="transform opacity-0 scale-95 -translate-y-2">
                                <div class="pl-4 ml-3 border-l-2 border-indigo-500/30 dark:border-gray-700 space-y-1">
                                    <Link v-for="subItem in group.items" :key="subItem.name" :href="subItem.href"
                                        :class="[
                                            'flex items-center px-4 py-2.5 text-sm rounded-lg transition-all duration-200',
                                            'hover:bg-white/10 dark:hover:bg-gray-700 group',
                                            isActivePath(subItem.href)
                                                ? 'bg-white/15 dark:bg-gray-700 text-white'
                                                : 'text-indigo-200 dark:text-gray-400 hover:text-white dark:hover:text-gray-100'
                                        ]">
                                    <i :class="[
                                        'fas',
                                        subItem.icon,
                                        'w-4 h-4 mr-3',
                                        isActivePath(subItem.href) ? 'text-white' : 'text-indigo-300 dark:text-gray-400 group-hover:text-white'
                                    ]"></i>
                                    <span class="font-medium">{{ subItem.name }}</span>
                                    </Link>
                                </div>
                            </TransitionRoot>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <!-- <div class="mt-8 pt-6 border-t border-indigo-500/20 mb-20">
                        <h3 class="px-4 text-xs font-semibold text-indigo-200 uppercase tracking-wider">
                            Quick Actions
                        </h3>
                        <div class="mt-4 space-y-2">
                            <button v-for="action in quickActions" :key="action.name" @click="action.handler"
                                class="w-full flex items-center px-4 py-2.5 text-sm text-indigo-100 hover:text-white hover:bg-white/10 rounded-xl transition-all duration-200 group">
                                <div
                                    class="p-2 bg-white/10 rounded-lg mr-3 group-hover:bg-white/15 transition-all duration-200">
                                    <i :class="['fas', action.icon, 'w-4 h-4']"></i>
                                </div>
                                <span class="font-medium">{{ action.name }}</span>
                            </button>
                        </div>
                    </div> -->
                </nav>
            </div>

            <!-- Fixed Footer with User Profile -->
            <div class="flex-shrink-0 p-4 border-t border-indigo-500/20">
                <Menu as="div" class="relative">
                    <MenuButton
                        class="w-full flex items-center p-3 rounded-xl hover:bg-white/10 transition-all duration-200">
                        <div class="relative">
                            <img :src="user.avatar || '/default-avatar.png'"
                                class="h-10 w-10 rounded-lg border-2 border-white/20" alt="User Avatar" />
                            <div
                                class="absolute bottom-0 right-0 h-3 w-3 rounded-full bg-green-500 border-2 border-indigo-900">
                            </div>
                        </div>
                        <div class="ml-3 flex-1 text-left">
                            <p class="text-sm font-medium text-white">{{ user.name }}</p>
                            <p class="text-xs text-indigo-200">{{ user.role || 'Administrator' }}</p>
                        </div>
                        <i class="fas fa-chevron-up ml-2 text-indigo-200"></i>
                    </MenuButton>

                    <transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0">
                        <MenuItems
                            class="absolute bottom-full left-0 w-full mb-2 bg-white rounded-xl shadow-xl shadow-indigo-900/20 backdrop-blur-sm overflow-hidden">
                            <div class="px-3 py-2 bg-indigo-50">
                                <p class="text-sm font-medium text-indigo-900">Signed in as</p>
                                <p class="text-sm text-indigo-700">{{ user.email }}</p>
                            </div>
                            <div class="p-1">
                                <MenuItem v-slot="{ active }">
                                <Link href="/profile" :class="[
                                    'flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors',
                                    active ? 'bg-indigo-50 text-indigo-900' : 'text-gray-700 hover:bg-gray-50'
                                ]">
                                <i class="fas fa-user-circle mr-3 text-indigo-500"></i>
                                Your Profile
                                </Link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">

                                </MenuItem>
                                <div class="border-t border-gray-100 my-1"></div>
                                <MenuItem v-slot="{ active }">
                                <Link href="/logout" method="post" as="button" class="w-full" :class="[
                                    'flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors',
                                    active ? 'bg-red-50 text-red-900' : 'text-red-600 hover:bg-red-50'
                                ]">
                                <i class="fas fa-sign-out-alt mr-3"></i>
                                Sign out
                                </Link>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div :class="['lg:pl-72 min-h-screen flex flex-col', isOpen && 'overflow-hidden']">
            <!-- Top Navigation -->
            <header
                class="sticky top-0 z-40 bg-white/80 dark:bg-gray-800 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700">
                <div class="h-16 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-full">
                        <!-- Left Side -->
                        <div class="flex items-center space-x-4">
                            <button
                                class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                @click="toggleSidebar">
                                <i class="fas fa-bars text-xl"></i>
                            </button>

                            <!-- Breadcrumbs -->
                            <nav class="hidden sm:flex items-center space-x-2">
                                <Link v-for="(crumb, index) in breadcrumbs" :key="crumb.name" :href="crumb.href" :class="[
                                    'text-sm font-medium transition-colors',
                                    index === breadcrumbs.length - 1
                                        ? 'text-indigo-600 dark:text-indigo-400'
                                        : 'text-gray-500 hover:text-indigo-600 dark:text-gray-300 dark:hover:text-indigo-400'
                                ]">
                                {{ crumb.name }}
                                <span v-if="index < breadcrumbs.length - 1"
                                    class="mx-2 text-gray-400 dark:text-gray-500">
                                    /
                                </span>
                                </Link>
                            </nav>
                        </div>

                        <!-- Right Side -->
                        <div class="flex items-center space-x-4">
                            <!-- Search -->
                            <div class="hidden md:block">
                                <div class="relative">
                                    <input type="text" placeholder="Search..."
                                        class="w-64 pl-10 pr-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50/50 dark:bg-gray-700 dark:text-white" />
                                    <i
                                        class="fas fa-search absolute left-3 top-2.5 text-gray-400 dark:text-gray-500"></i>
                                </div>
                            </div>

                            <!-- POS Quick Access -->
                            <Link href="/admin/pos"
                                class="flex items-center px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white transition-colors duration-150 ease-in-out">
                            <i class="fas fa-cash-register mr-2"></i>
                            <span class="font-medium">POS</span>
                            </Link>

                            <!-- Notifications -->
                            <Menu as="div" class="relative">
                                <MenuButton
                                    class="relative p-2 text-gray-600 dark:text-gray-300 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    <span class="sr-only">View notifications</span>
                                    <i class="fas fa-bell text-xl"></i>
                                    <span class="absolute top-0 right-0 h-2 w-2 rounded-full bg-red-500"></span>
                                </MenuButton>

                                <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="absolute right-0 mt-2 w-80 origin-top-right bg-white dark:bg-gray-800 rounded-lg shadow-lg py-1 focus:outline-none">
                                        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                Notifications</h3>
                                        </div>
                                        <div class="max-h-96 overflow-y-auto">
                                            <MenuItem v-for="notification in notifications" :key="notification.id"
                                                v-slot="{ active }">
                                            <a :href="notification.href" :class="[
                                                'block px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700',
                                                !notification.read && 'bg-indigo-50 dark:bg-indigo-700',
                                                active && 'bg-gray-50 dark:bg-gray-600'
                                            ]">
                                                <div class="flex items-start">
                                                    <div class="shrink-0">
                                                        <i
                                                            :class="['fas', notification.icon, 'text-indigo-500 dark:text-indigo-400 mt-0.5']"></i>
                                                    </div>
                                                    <div class="ml-3 w-0 flex-1">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                            {{ notification.title }}
                                                        </p>
                                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                            {{ notification.description }}
                                                        </p>
                                                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                                            {{ notification.time }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </a>
                                            </MenuItem>
                                        </div>
                                        <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700">
                                            <Link href="/admin/notifications"
                                                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-500">
                                            View all notifications
                                            </Link>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>

                            <!-- Settings -->
                            <button @click="switchTheme"
                                class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-circle-half-stroke text-xl"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 px-4 sm:px-6 lg:px-8 py-8">
                <!-- Page Header -->
                <div v-if="$slots.header" class="mb-8">
                    <slot name="header" />
                </div>

                <!-- Page Content -->
                <div class="space-y-6">
                    <slot />
                </div>
            </main>

            <!-- Footer -->
            <footer class="mt-auto border-t border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div class="mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="py-4 text-center sm:text-left">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            © {{ new Date().getFullYear() }} Mousumi Prokashon. All rights reserved.
                        </p>
                    </div>
                </div>
            </footer>

        </div>

        <!-- Mobile Sidebar Overlay -->
        <div v-if="isOpen" class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden transition-opacity"
            @click="toggleSidebar"></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import {usePage, Link, router } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItems, MenuItem, TransitionRoot } from '@headlessui/vue'
import { switchTheme } from '@/theme';


const page = usePage();
const { flash, auth } = page.props;
const user = auth.user;
const props = defineProps({
    user: {
        type: Object,
        required: true
    }
})


// Sidebar state
const isOpen = ref(false)
const activeDropdowns = ref([])

// Navigation items
const singleNavItems = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: 'fa-tachometer-alt' }
]

const dropdownNavItems = [
    {
        name: 'Products',
        icon: 'fa-box',
        items: [
            { name: 'Products', href: '/admin/products', icon: 'fa-boxes' },
            { name: 'Categories', href: '/admin/categories', icon: 'fa-folder' },
            { name: 'Units', href: '/admin/units', icon: 'fa-ruler' },
            { name: 'Brands', href: '/admin/brands', icon: 'fa-tag' },
            { name: 'Stock Management', href: '/admin/product-stocks', icon: 'fa-warehouse' }
        ]
    },
    {
        name: 'Sales',
        icon: 'fa-shopping-cart',
        items: [
            { name: 'Sales List', href: '/admin/sales', icon: 'fa-list' },
            { name: 'Extra Income', href: '/admin/extra-incomes', icon: 'fa-coins' },
            { name: 'POS', href: '/admin/pos', icon: 'fa-cash-register' }
        ]
    },
    {
        name: 'Customers',
        icon: 'fa-users',
        items: [
            { name: 'Customer List', href: '/admin/customers', icon: 'fa-user-friends' },
        ]
    },
    {
        name: 'Banking',
        icon: 'fa-university',
        items: [
            { name: 'Bank Accounts', href: '/admin/bank-accounts', icon: 'fa-piggy-bank' },
            { name: 'Funds', href: '/admin/funds', icon: 'fas fa-wallet' },
            { name: 'Transactions', href: '/admin/bank-transactions', icon: 'fa-exchange-alt' }
        ]
    },
    {
        name: 'Expenses',
        icon: 'fa-receipt',
        items: [
            { name: 'Expenses', href: '/admin/expenses', icon: 'fa-file-invoice-dollar' },
            { name: 'Categories', href: '/admin/expense-categories', icon: 'fa-folder' }
        ]
    },
    {
        name: 'Reports',
        icon: 'fa-chart-line',
        items: [
            { name: 'Bank Report', href: '/admin/reports/bank', icon: 'fa-university' },
            { name: 'Stock Report', href: '/admin/reports/stock', icon: 'fa-warehouse' },
            { name: 'Sales Report', href: '/admin/reports/sales', icon: 'fa-chart-bar' },
            { name: 'Income & Expenditure', href: '/admin/reports/income-expenditure', icon: 'fa-file-alt' },
            { name: 'Balance Sheet', href: '/admin/reports/balance-sheet', icon: 'fas fa-balance-scale' }
        ]
    }
]

// Quick actions for frequently used features
const quickActions = [

]

// Sample notifications
const notifications = [
    {
        id: 1,
        title: 'New Order Received',
        description: 'Order #12345 needs processing',
        time: '5 minutes ago',
        icon: 'fa-shopping-bag',
        href: '/admin/orders/12345',
        read: false
    },
    {
        id: 2,
        title: 'New User Registration',
        description: 'John Doe just signed up',
        time: '1 hour ago',
        icon: 'fa-user-plus',
        href: '/admin/users/789',
        read: true
    }
]

// Breadcrumbs based on current route
const breadcrumbs = computed(() => {
    const path = window.location.pathname
    const parts = path.split('/').filter(Boolean)

    return [{
        name: 'Dashboard',
        href: '/admin/dashboard'
    }].concat(
        parts.slice(1).map((part, index) => ({
            name: part.charAt(0).toUpperCase() + part.slice(1),
            href: '/admin/' + parts.slice(1, index + 2).join('/')
        }))
    )
})

// Methods
const toggleSidebar = () => {
    isOpen.value = !isOpen.value
}

const toggleDropdown = (name) => {
    const index = activeDropdowns.value.indexOf(name)
    if (index === -1) {
        activeDropdowns.value.push(name)
    } else {
        activeDropdowns.value.splice(index, 1)
    }
}

const isActivePath = (path) => {
    return window.location.pathname.startsWith(path)
}

const isDropdownActive = (groupName) => {
    const group = dropdownNavItems.find(g => g.name === groupName)
    if (!group) return false
    return group.items.some(item => isActivePath(item.href)) ||
        activeDropdowns.value.includes(groupName)
}
</script>

<style scoped>
.bg-clip-text {
    -webkit-background-clip: text;
}

/* Optional: Add custom scrollbar styling */
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: #818cf8 #e0e7ff;
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #e0e7ff;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: #818cf8;
    border-radius: 3px;
}
</style>
