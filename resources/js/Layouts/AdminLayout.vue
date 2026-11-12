<template>
    <div class="min-h-screen bg-gray-50 text-sm dark:bg-gray-900">
        <!-- Sidebar -->

        <aside :class="[
            'fixed inset-y-0 left-0 z-50 w-64 transition-transform duration-300 ease-in-out flex flex-col',
            'bg-gradient-to-b from-slate-50 via-indigo-50/40 to-slate-100 text-slate-800 border-r border-slate-200/70 shadow-[inset_-1px_0_0_rgba(15,23,42,0.04)]',
            'dark:bg-gradient-to-b dark:from-indigo-900 dark:via-indigo-950 dark:to-slate-950 dark:text-slate-100 dark:border-white/10',
            isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]">

            <!-- Fixed Header -->
            <div class="flex-shrink-0 border-b border-slate-200/70 dark:border-white/10">
                <div class="flex items-center justify-between h-14 px-3.5">
                    <Link href="/admin/dashboard" class="flex items-center gap-2.5 min-w-0">
                        <div class="p-1 rounded-md bg-indigo-50 dark:bg-white/10 flex-shrink-0">
                            <img src="/logo.svg" alt="Logo" class="w-6 h-6" />
                        </div>
                        <div class="min-w-0">
                            <span class="block text-[13px] font-bold text-slate-900 dark:text-slate-100 truncate leading-tight tracking-tight">
                                Mousumi Prokashon
                            </span>
                            <span class="block text-[10px] text-slate-500 dark:text-slate-400 font-medium truncate leading-tight">
                                Variety Store & POS
                            </span>
                        </div>
                    </Link>
                    <button
                        class="p-1.5 transition-colors rounded-md lg:hidden hover:bg-slate-200/60 dark:hover:bg-white/10 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white"
                        @click="toggleSidebar">
                        <i class="text-sm fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Scrollable Navigation -->
            <div class="flex-1 overflow-y-auto px-2 py-2">
                <nav class="space-y-2">
                    <div v-for="section in filteredNavSections" :key="section.title" class="space-y-0.5">
                        <!-- Section Category Header -->
                        <div class="px-2 pt-1.5 pb-0.5 text-[9.5px] font-bold tracking-wider text-slate-400 uppercase select-none dark:text-slate-500 flex items-center justify-between">
                            <span>{{ section.title }}</span>
                        </div>

                        <!-- Section Items -->
                        <div class="space-y-0.5">
                            <template v-for="item in section.items" :key="item.name">
                                <!-- Link Item -->
                                <Link v-if="item.type === 'link'" :href="item.href" :class="[
                                    'flex items-center px-2 py-1.5 text-[12px] rounded-md transition-all duration-150 group',
                                    isActivePath(item.href)
                                        ? 'bg-indigo-600 text-white font-medium shadow-xs dark:bg-indigo-600'
                                        : 'text-slate-700 hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white'
                                ]">
                                    <i :class="[
                                        'fas',
                                        item.icon,
                                        'w-4 text-center mr-2 text-[11px] flex-shrink-0 transition-colors',
                                        isActivePath(item.href)
                                            ? 'text-white'
                                            : 'text-slate-400 group-hover:text-slate-700 dark:text-slate-400 dark:group-hover:text-slate-200'
                                    ]"></i>
                                    <span class="truncate">{{ item.name }}</span>
                                    <span
                                        v-if="item.badgeKey && (counts[item.badgeKey] || 0) > 0"
                                        class="ml-auto inline-flex items-center justify-center min-w-4 h-4 px-1 rounded-full text-[9px] font-bold bg-amber-500 text-white">
                                        {{ counts[item.badgeKey] }}
                                    </span>
                                </Link>

                                <!-- Dropdown Item -->
                                <div v-else-if="item.type === 'dropdown'" class="space-y-0.5">
                                    <button @click="toggleDropdown(item.name)" :class="[
                                        'w-full flex items-center justify-between px-2 py-1.5 text-[12px] rounded-md transition-all duration-150 group',
                                        isDropdownActive(item)
                                            ? 'text-indigo-700 font-semibold bg-indigo-50/80 dark:bg-white/10 dark:text-indigo-300'
                                            : 'text-slate-700 hover:bg-slate-200/60 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white font-normal'
                                    ]">
                                        <div class="flex items-center min-w-0">
                                            <i :class="[
                                                'fas',
                                                item.icon,
                                                'w-4 text-center mr-2 text-[11px] flex-shrink-0 transition-colors',
                                                isDropdownActive(item)
                                                    ? 'text-indigo-600 dark:text-indigo-300'
                                                    : 'text-slate-400 group-hover:text-slate-700 dark:text-slate-400 dark:group-hover:text-slate-200'
                                            ]"></i>
                                            <span class="truncate">{{ item.name }}</span>
                                        </div>
                                        <i :class="[
                                            'fas fa-chevron-right text-[9px] transition-transform duration-150 text-slate-400 dark:text-slate-500 ml-1.5 flex-shrink-0',
                                            activeDropdowns.includes(item.name) && 'rotate-90'
                                        ]"></i>
                                    </button>

                                    <!-- Dropdown Content -->
                                    <TransitionRoot :show="activeDropdowns.includes(item.name)"
                                        enter="transition-all duration-150 ease-out"
                                        enter-from="transform opacity-0 -translate-y-1"
                                        enter-to="transform opacity-100 translate-y-0"
                                        leave="transition-all duration-100 ease-in"
                                        leave-from="transform opacity-100 translate-y-0"
                                        leave-to="transform opacity-0 -translate-y-1">
                                        <div class="pl-2 ml-3 my-0.5 space-y-0.5 border-l border-slate-300/80 dark:border-white/15">
                                            <Link v-for="subItem in item.items" :key="subItem.name" :href="subItem.href"
                                                :class="[
                                                    'flex items-center px-2 py-1 text-[11.5px] rounded transition-all duration-150 group',
                                                    isActivePath(subItem.href)
                                                        ? 'bg-indigo-600 text-white font-medium shadow-xs'
                                                        : 'text-slate-600 hover:bg-slate-200/50 hover:text-slate-900 dark:text-slate-300 dark:hover:bg-white/10 dark:hover:text-white'
                                                ]">
                                                <i :class="[
                                                    'fas',
                                                    subItem.icon,
                                                    'w-3.5 text-center mr-1.5 text-[10px] flex-shrink-0',
                                                    isActivePath(subItem.href)
                                                        ? 'text-white'
                                                        : 'text-slate-400 group-hover:text-slate-600 dark:text-slate-400 dark:group-hover:text-slate-200'
                                                ]"></i>
                                                <span class="truncate">{{ subItem.name }}</span>
                                            </Link>
                                        </div>
                                    </TransitionRoot>
                                </div>
                            </template>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Fixed Footer with User Profile -->
            <div class="flex-shrink-0 p-2 border-t border-slate-200/70 dark:border-white/10 bg-white/40 dark:bg-white/5">
                <Menu as="div" class="relative">
                    <MenuButton
                        class="flex items-center w-full p-1.5 transition-all duration-150 rounded-md hover:bg-slate-200/60 dark:hover:bg-white/10">
                        <div class="relative flex-shrink-0">
                            <img :src="user.avatar || '/default-avatar.png'"
                                class="w-7 h-7 border rounded-md border-slate-300/60 dark:border-white/20 object-cover" alt="User Avatar" />
                            <div
                                class="absolute -bottom-0.5 -right-0.5 w-2 h-2 bg-emerald-500 border border-white dark:border-slate-900 rounded-full">
                            </div>
                        </div>
                        <div class="flex-1 ml-2 text-left min-w-0">
                            <p class="text-[12px] font-semibold leading-tight text-slate-800 dark:text-white truncate">{{ user.name }}</p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 truncate leading-tight">{{ user.role?.name || 'Administrator' }}</p>
                        </div>
                        <i class="ml-1 text-[10px] text-slate-400 dark:text-slate-500 fas fa-chevron-up flex-shrink-0"></i>
                    </MenuButton>

                    <transition enter-active-class="transition duration-200 ease-out"
                        enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-150 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0">
                        <MenuItems
                            class="absolute left-0 w-full mb-2 overflow-hidden bg-white shadow-xl bottom-full rounded-xl shadow-indigo-900/20 backdrop-blur-sm">
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
                                <i class="mr-3 text-indigo-500 fas fa-user-circle"></i>
                                Your Profile
                                </Link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">

                                </MenuItem>
                                <div class="my-1 border-t border-gray-100"></div>
                                <MenuItem v-slot="{ active }">
                                <Link href="/logout" method="post" as="button" class="w-full" :class="[
                                    'flex items-center px-4 py-2.5 text-sm rounded-lg transition-colors',
                                    active ? 'bg-red-50 text-red-900' : 'text-red-600 hover:bg-red-50'
                                ]">
                                <i class="mr-3 fas fa-sign-out-alt"></i>
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
        <div :class="['lg:pl-64 min-h-screen flex flex-col', isOpen && 'overflow-hidden']">
            <!-- Top Navigation -->
            <header
                class="sticky top-0 z-40 border-b border-gray-200 bg-white/80 dark:bg-gray-800 backdrop-blur-sm dark:border-gray-700">
                <div class="h-14 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center justify-between h-full">
                        <!-- Left Side -->
                        <div class="flex items-center gap-3">
                            <button
                                class="p-2 text-gray-600 rounded-lg lg:hidden hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                                @click="toggleSidebar">
                                <i class="text-lg fas fa-bars"></i>
                            </button>

                            <!-- Breadcrumbs -->
                            <nav class="items-center hidden space-x-2 sm:flex">
                                <Link v-for="(crumb, index) in breadcrumbs" :key="crumb.name" :href="crumb.href" :class="[
                                    'text-[13px] font-medium transition-colors',
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
                        <div class="flex items-center gap-3">
                            <!-- Search -->
                            <div class="hidden md:block">
                                <div class="relative">
                                    <input type="text" placeholder="Search..."
                                        class="w-56 py-1.5 pl-9 pr-3 text-[13px] border border-gray-200 rounded-lg dark:border-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-gray-50/50 dark:bg-gray-700 dark:text-white" />
                                    <i
                                        class="fas fa-search absolute left-3 top-2 text-gray-400 dark:text-gray-500"></i>
                                </div>
                            </div>

                            <!-- Mousumi Apps Launcher -->
                            <a href="https://app.mousumibd.org"
                                target="_self"
                                class="group inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 text-white hover:bg-purple-600 dark:bg-purple-600 dark:hover:bg-purple-500 font-bold text-xs shadow-md hover:shadow-lg transition-all duration-300 transform hover:scale-105"
                                title="Return to Mousumi Apps">
                                <div class="flex items-center justify-center w-5 h-5 rounded-lg bg-purple-500 dark:bg-white/20 text-white p-0.5 shadow-sm group-hover:rotate-12 transition-transform duration-300">
                                    <i class="fas fa-th-large text-xs"></i>
                                </div>
                                <span class="tracking-wide">Mousumi Apps</span>
                            </a>

                            <!-- POS Quick Access -->
                            <Link href="/admin/pos"
                                class="flex items-center px-3 py-1.5 text-[13px] text-white transition-colors duration-150 ease-in-out bg-indigo-600 rounded-lg hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                            <i class="mr-2 fas fa-cash-register"></i>
                            <span class="font-medium">POS</span>
                            </Link>

                            <!-- Notifications -->
                            <Menu as="div" class="relative">
                                <MenuButton
                                    class="relative p-2 text-gray-600 transition-colors rounded-lg dark:text-gray-300 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:text-indigo-400 dark:hover:bg-gray-700">
                                    <span class="sr-only">View notifications</span>
                                    <i class="text-lg fas fa-bell"></i>
                                    <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                                </MenuButton>

                                <transition enter-active-class="transition duration-100 ease-out"
                                    enter-from-class="transform scale-95 opacity-0"
                                    enter-to-class="transform scale-100 opacity-100"
                                    leave-active-class="transition duration-75 ease-in"
                                    leave-from-class="transform scale-100 opacity-100"
                                    leave-to-class="transform scale-95 opacity-0">
                                    <MenuItems
                                        class="absolute right-0 py-1 mt-2 origin-top-right bg-white rounded-lg shadow-lg w-80 dark:bg-gray-800 focus:outline-none">
                                        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                Notifications</h3>
                                        </div>
                                        <div class="overflow-y-auto max-h-96">
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
                                                    <div class="flex-1 w-0 ml-3">
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
                                class="p-2 text-gray-500 rounded-lg hover:text-gray-700 dark:text-gray-300 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                                <i class="text-lg fa-solid fa-circle-half-stroke"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                <!-- Page Header -->
                <div v-if="$slots.header" class="mb-6">
                    <slot name="header" />
                </div>

                <!-- Page Content -->
                <div class="space-y-4">
                    <slot />
                </div>
            </main>

            <!-- Footer -->
            <footer class="mt-auto bg-white border-t border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                <div class="px-4 mx-auto sm:px-6 lg:px-8">
                    <div class="py-3 text-center sm:text-left">
                        <p class="text-[13px] text-gray-500 dark:text-gray-400">
                            © {{ new Date().getFullYear() }} Mousumi Prokashon. All rights reserved.
                        </p>
                    </div>
                </div>
            </footer>

        </div>

        <!-- Mobile Sidebar Overlay -->
        <div v-if="isOpen" class="fixed inset-0 z-40 transition-opacity bg-gray-600 bg-opacity-75 lg:hidden"
            @click="toggleSidebar"></div>
    </div>


</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePage, Link, router } from '@inertiajs/vue3'
import { Menu, MenuButton, MenuItems, MenuItem, TransitionRoot } from '@headlessui/vue'
import { switchTheme } from '@/theme';


const page = usePage();
const user = page.props.auth.user;
const counts = computed(() => page.props.counts || {});



// Sidebar state
const isOpen = ref(false)
const activeDropdowns = ref([])

// Current reactive path
const currentPath = computed(() => {
    const url = page.url || (typeof window !== 'undefined' ? window.location.pathname : '');
    return url.split('?')[0];
});

const isActivePath = (href) => {
    if (!href) return false;
    const current = currentPath.value;
    if (current === href) return true;
    if (href !== '/admin' && href !== '/admin/dashboard' && current.startsWith(href + '/')) {
        return true;
    }
    return false;
};

// Categorized Navigation Sections
const navigationSections = [
    {
        title: 'MAIN',
        items: [
            {
                type: 'link',
                name: 'Dashboard',
                href: '/admin/dashboard',
                icon: 'fa-tachometer-alt',
                allowedRoles: ['admin', 'manager', 'staff']
            },
            {
                type: 'link',
                name: 'POS Terminal',
                href: '/admin/pos',
                icon: 'fa-cash-register',
                allowedRoles: ['admin', 'manager', 'staff']
            },
            {
                type: 'link',
                name: 'Online Orders',
                href: '/admin/pending-sales',
                icon: 'fa-clipboard-list',
                badgeKey: 'pendingOrders',
                allowedRoles: ['admin', 'manager']
            }
        ]
    },
    {
        title: 'SALES & CRM',
        items: [
            {
                type: 'dropdown',
                name: 'Sales',
                icon: 'fa-shopping-cart',
                allowedRoles: ['admin', 'manager', 'staff'],
                items: [
                    { name: 'Sales List', href: '/admin/sales', icon: 'fa-list', allowedRoles: ['admin', 'staff', 'manager'] },
                    { name: 'POS Counter', href: '/admin/pos', icon: 'fa-cash-register', allowedRoles: ['admin', 'manager', 'staff'] }
                ]
            },
            {
                type: 'dropdown',
                name: 'Customers',
                icon: 'fa-users',
                allowedRoles: ['admin', 'manager', 'staff'],
                items: [
                    { name: 'Customer List', href: '/admin/customers', icon: 'fa-user-friends', allowedRoles: ['admin', 'manager', 'staff'] }
                ]
            }
        ]
    },
    {
        title: 'INVENTORY & CATALOG',
        items: [
            {
                type: 'dropdown',
                name: 'Products & Stock',
                icon: 'fa-boxes',
                allowedRoles: ['admin', 'manager'],
                items: [
                    { name: 'All Products', href: '/admin/products', icon: 'fa-box', allowedRoles: ['admin', 'manager'] },
                    { name: 'Stock Management', href: '/admin/product-stocks', icon: 'fa-warehouse', allowedRoles: ['admin', 'manager'] },
                    { name: 'Categories', href: '/admin/categories', icon: 'fa-folder', allowedRoles: ['admin'] },
                    { name: 'Brands', href: '/admin/brands', icon: 'fa-tag', allowedRoles: ['admin'] },
                    { name: 'Units', href: '/admin/units', icon: 'fa-ruler', allowedRoles: ['admin'] }
                ]
            }
        ]
    },
    {
        title: 'FINANCE & ACCOUNTS',
        items: [
            {
                type: 'dropdown',
                name: 'Expenses & Assets',
                icon: 'fa-receipt',
                allowedRoles: ['admin', 'manager'],
                items: [
                    { name: 'Expenses', href: '/admin/expenses', icon: 'fa-file-invoice-dollar', allowedRoles: ['admin', 'manager'] },
                    { name: 'Expense Categories', href: '/admin/expense-categories', icon: 'fa-folder-open', allowedRoles: ['admin'] },
                    { name: 'Fixed Assets', href: '/admin/fixed-assets', icon: 'fa-building', allowedRoles: ['admin', 'manager'] }
                ]
            },
            {
                type: 'dropdown',
                name: 'Banking & Accounts',
                icon: 'fa-university',
                allowedRoles: ['admin'],
                items: [
                    { name: 'Bank Accounts', href: '/admin/bank-accounts', icon: 'fa-piggy-bank', allowedRoles: ['admin'] },
                    { name: 'Transactions', href: '/admin/bank-transactions', icon: 'fa-exchange-alt', allowedRoles: ['admin'] },
                    { name: 'Funds', href: '/admin/funds', icon: 'fas fa-wallet', allowedRoles: ['admin'] }
                ]
            },
            {
                type: 'dropdown',
                name: 'Extra Income',
                icon: 'fa-coins',
                allowedRoles: ['admin', 'manager'],
                items: [
                    { name: 'Extra Incomes', href: '/admin/extra-incomes', icon: 'fa-hand-holding-usd', allowedRoles: ['admin', 'manager'] },
                    { name: 'Income Categories', href: '/admin/extra-income-categories', icon: 'fa-tags', allowedRoles: ['admin'] }
                ]
            }
        ]
    },
    {
        title: 'REPORTS & ANALYTICS',
        items: [
            {
                type: 'dropdown',
                name: 'Reports',
                icon: 'fa-chart-line',
                allowedRoles: ['admin', 'manager'],
                items: [
                    { name: 'Sales Report', href: '/admin/reports/sales', icon: 'fa-chart-bar', allowedRoles: ['admin'] },
                    { name: 'Stock Report', href: '/admin/reports/stock', icon: 'fa-warehouse', allowedRoles: ['admin'] },
                    { name: 'Product Analysis', href: '/admin/reports/product-analysis', icon: 'fa-chart-pie', allowedRoles: ['admin', 'manager'] },
                    { name: 'Bank Report', href: '/admin/reports/bank-transaction-report', icon: 'fa-university', allowedRoles: ['admin', 'manager'] },
                    { name: 'Receipt & Payment', href: '/admin/reports/receipt-payment', icon: 'fa-credit-card', allowedRoles: ['admin'] },
                    { name: 'Income & Expenditure', href: '/admin/reports/income-expenditure', icon: 'fa-file-alt', allowedRoles: ['admin'] },
                    { name: 'Balance Sheet', href: '/admin/reports/balance-sheet', icon: 'fas fa-balance-scale', allowedRoles: ['admin'] }
                ]
            }
        ]
    },
    {
        title: 'SYSTEM & SETTINGS',
        items: [
            {
                type: 'link',
                name: 'User Management',
                href: '/admin/users',
                icon: 'fa-users-cog',
                allowedRoles: ['admin']
            }
        ]
    }
];

const userRoleName = computed(() => (user?.role?.name || '').toLowerCase());
const isUserSuperAdmin = computed(() => {
    return Boolean(
        page.props.auth?.is_super_admin ||
        user?.is_super_admin ||
        userRoleName.value.includes('super') ||
        user?.role?.slug === 'super-admin' ||
        (user?.name || '').toLowerCase().includes('super admin') ||
        user?.id === 1
    );
});

const hasRoleAccess = (allowedRoles = []) => {
    if (!allowedRoles || allowedRoles.length === 0) return true;
    if (isUserSuperAdmin.value) return true;
    return allowedRoles.includes(userRoleName.value);
};

const filteredNavSections = computed(() => {
    return navigationSections.map(section => {
        const filteredItems = section.items.map(item => {
            if (item.type === 'dropdown') {
                if (!hasRoleAccess(item.allowedRoles)) return null;
                const filteredSubItems = (item.items || []).filter(sub => hasRoleAccess(sub.allowedRoles));
                if (filteredSubItems.length === 0) return null;
                return {
                    ...item,
                    items: filteredSubItems
                };
            } else {
                if (!hasRoleAccess(item.allowedRoles)) return null;
                return item;
            }
        }).filter(Boolean);

        return {
            ...section,
            items: filteredItems
        };
    }).filter(section => section.items.length > 0);
});

const isDropdownActive = (group) => {
    if (!group || !group.items) return false;
    return group.items.some(item => isActivePath(item.href));
};

const ensureActiveDropdownIsOpen = () => {
    navigationSections.forEach(section => {
        section.items.forEach(item => {
            if (item.type === 'dropdown' && item.items) {
                if (item.items.some(subItem => isActivePath(subItem.href))) {
                    if (!activeDropdowns.value.includes(item.name)) {
                        activeDropdowns.value.push(item.name);
                    }
                }
            }
        });
    });
};

onMounted(() => {
    ensureActiveDropdownIsOpen();
});

watch(() => page.url, () => {
    ensureActiveDropdownIsOpen();
});

// Quick actions for frequently used features
const quickActions = []

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
    const path = currentPath.value;
    const parts = path.split('/').filter(Boolean);

    return [{
        name: 'Dashboard',
        href: '/admin/dashboard'
    }].concat(
        parts.slice(1).map((part, index) => ({
            name: part.charAt(0).toUpperCase() + part.slice(1).replace(/-/g, ' '),
            href: '/admin/' + parts.slice(1, index + 2).join('/')
        }))
    );
});

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
</script>

<style scoped>
.bg-clip-text {
    -webkit-background-clip: text;
}

/* Optional: Add custom scrollbar styling */
.overflow-y-auto {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.6) rgba(255, 255, 255, 0.06);
}

.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.06);
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background-color: rgba(148, 163, 184, 0.55);
    border-radius: 3px;
}
</style>
