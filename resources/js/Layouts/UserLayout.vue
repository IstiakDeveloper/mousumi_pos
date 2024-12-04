<!-- UserLayout.vue -->
<template>
    <div class="min-h-screen bg-gray-50">
      <!-- Navigation -->
      <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4">
          <div class="flex justify-between h-16">
            <!-- Logo and Main Nav -->
            <div class="flex">
              <div class="flex-shrink-0 flex items-center">
                <Link href="/" class="flex items-center space-x-2">
                  <img src="/logo.svg" alt="Logo" class="h-8 w-8" />
                  <span class="text-xl font-bold text-gray-900">Your Brand</span>
                </Link>
              </div>

              <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                <Link
                  v-for="item in navigation"
                  :key="item.name"
                  :href="item.href"
                  :class="[
                    'inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2 transition-colors',
                    $page.url === item.href
                      ? 'border-blue-500 text-gray-900'
                      : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                  ]"
                >
                  {{ item.name }}
                </Link>
              </div>
            </div>

            <!-- Right Navigation -->
            <div class="flex items-center">
              <!-- Search -->
              <div class="hidden md:block">
                <div class="relative">
                  <input
                    type="text"
                    placeholder="Search products..."
                    class="w-64 pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
              </div>

              <!-- Cart -->
              <Link
                href="/cart"
                class="ml-4 relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg"
              >
                <i class="fas fa-shopping-cart text-xl"></i>
                <span
                  v-if="cartCount > 0"
                  class="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-blue-500 text-white text-xs flex items-center justify-center"
                >
                  {{ cartCount }}
                </span>
              </Link>

              <!-- User Menu -->
              <Menu as="div" class="ml-4 relative">
                <MenuButton class="flex items-center space-x-2 text-sm focus:outline-none">
                  <img
                    :src="user.avatar || '/default-avatar.png'"
                    class="h-8 w-8 rounded-full"
                  />
                  <span class="hidden md:block font-medium text-gray-700">
                    {{ user.name }}
                  </span>
                  <i class="fas fa-chevron-down text-gray-400"></i>
                </MenuButton>

                <transition
                  enter-active-class="transition duration-100 ease-out"
                  enter-from-class="transform scale-95 opacity-0"
                  enter-to-class="transform scale-100 opacity-100"
                  leave-active-class="transition duration-75 ease-in"
                  leave-from-class="transform scale-100 opacity-100"
                  leave-to-class="transform scale-95 opacity-0"
                >
                  <MenuItems class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50">
                    <MenuItem v-slot="{ active }">
                      <Link
                        href="/profile"
                        :class="[
                          'block px-4 py-2 text-sm text-gray-700',
                          active && 'bg-gray-100'
                        ]"
                      >
                        Your Profile
                      </Link>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <Link
                        href="/orders"
                        :class="[
                          'block px-4 py-2 text-sm text-gray-700',
                          active && 'bg-gray-100'
                        ]"
                      >
                        Orders
                      </Link>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <Link
                        href="/settings"
                        :class="[
                          'block px-4 py-2 text-sm text-gray-700',
                          active && 'bg-gray-100'
                        ]"
                      >
                        Settings
                      </Link>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <Link
                        href="/help"
                        :class="[
                          'block px-4 py-2 text-sm text-gray-700',
                          active && 'bg-gray-100'
                        ]"
                      >
                        Help Center
                      </Link>
                    </MenuItem>
                    <div class="border-t border-gray-100 my-1"></div>
                    <MenuItem v-slot="{ active }">
                      <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="w-full text-left"
                        :class="[
                          'block px-4 py-2 text-sm text-red-600',
                          active && 'bg-gray-100'
                        ]"
                      >
                        Sign out
                      </Link>
                    </MenuItem>
                  </MenuItems>
                </transition>
              </Menu>

              <!-- Mobile menu button -->
              <div class="flex items-center sm:hidden ml-4">
                <button
                  type="button"
                  class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
                  @click="mobileMenuOpen = !mobileMenuOpen"
                >
                  <span class="sr-only">Open main menu</span>
                  <i
                    :class="[
                      'fas',
                      mobileMenuOpen ? 'fa-times' : 'fa-bars',
                      'text-xl'
                    ]"
                  ></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile menu -->
        <transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="transform -translate-y-2 opacity-0"
          enter-to-class="transform translate-y-0 opacity-100"
          leave-active-class="transition duration-100 ease-in"
          leave-from-class="transform translate-y-0 opacity-100"
          leave-to-class="transform -translate-y-2 opacity-0"
        >
          <div v-if="mobileMenuOpen" class="sm:hidden">
            <div class="pt-2 pb-3 space-y-1">
              <Link
                v-for="item in navigation"
                :key="item.name"
                :href="item.href"
                :class="[
                  'block pl-3 pr-4 py-2 border-l-4 text-base font-medium',
                  $page.url === item.href
                    ? 'border-blue-500 text-blue-700 bg-blue-50'
                    : 'border-transparent text-gray-500 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-700'
                ]"
              >
                {{ item.name }}
              </Link>
            </div>
            <!-- Mobile search -->
            <div class="pt-4 pb-3 border-t border-gray-200">
              <div class="px-4">
                <div class="relative">
                  <input
                    type="text"
                    placeholder="Search products..."
                    class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  />
                  <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
              </div>
            </div>
          </div>
        </transition>
      </nav>

      <!-- Page Content -->
      <main>
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
          <!-- Page header -->
          <div v-if="$slots.header" class="mb-6">
            <slot name="header" />
          </div>

          <!-- Main content -->
          <slot />
        </div>
      </main>

      <!-- Footer -->
      <footer class="bg-white border-t border-gray-200 mt-auto">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div>
              <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Company</h3>
              <ul class="mt-4 space-y-4">
                <li>
                  <Link href="/about" class="text-base text-gray-600 hover:text-gray-900">
                    About
                  </Link>
                </li>
                <li>
                  <Link href="/careers" class="text-base text-gray-600 hover:text-gray-900">
                    Careers
                  </Link>
                </li>
                <li>
                  <Link href="/blog" class="text-base text-gray-600 hover:text-gray-900">
                    Blog
                  </Link>
                </li>
              </ul>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Support</h3>
              <ul class="mt-4 space-y-4">
                <li>
                  <Link href="/contact" class="text-base text-gray-600 hover:text-gray-900">
                    Contact
                  </Link>
                </li>
                <li>
                  <Link href="/faq" class="text-base text-gray-600 hover:text-gray-900">
                    FAQ
                  </Link>
                </li>
                <li>
                  <Link href="/shipping" class="text-base text-gray-600 hover:text-gray-900">
                    Shipping
                  </Link>
                </li>
              </ul>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Legal</h3>
              <ul class="mt-4 space-y-4">
                <li>
                  <Link href="/privacy" class="text-base text-gray-600 hover:text-gray-900">
                    Privacy
                  </Link>
                </li>
                <li>
                  <Link href="/terms" class="text-base text-gray-600 hover:text-gray-900">
                    Terms
                  </Link>
                </li>
              </ul>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Social</h3>
              <ul class="mt-4 space-y-4">
                <li>
                  <a href="#" class="text-base text-gray-600 hover:text-gray-900 flex items-center">
                    <i class="fab fa-facebook mr-2"></i> Facebook
                  </a>
                </li>
                <li>
                  <a href="#" class="text-base text-gray-600 hover:text-gray-900 flex items-center">
                    <i class="fab fa-twitter mr-2"></i> Twitter
                  </a>
                </li>
                <li>
                  <a href="#" class="text-base text-gray-600 hover:text-gray-900 flex items-center">
                    <i class="fab fa-instagram mr-2"></i> Instagram
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <div class="mt-8 border-t border-gray-200 pt-8 md:flex md:items-center md:justify-between">
            <div class="flex space-x-6 md:order-2">
              <Link href="/privacy" class="text-gray-500 hover:text-gray-900">
                Privacy Policy
              </Link>
              <Link href="/terms" class="text-gray-500 hover:text-gray-900">
                Terms of Service
              </Link>
            </div>
            <p class="mt-8 text-base text-gray-500 md:mt-0 md:order-1">
              &copy; {{ new Date().getFullYear() }} Your Company. All rights reserved.
            </p>
          </div>
        </div>
      </footer>
    </div>
  </template>

  <script setup>
  import { ref } from 'vue'
  import { Link } from '@inertiajs/vue3'
  import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'

  const props = defineProps({
    user: {
      type: Object,
      required: true
    },
    cartCount: {
      type: Number,
      default: 0
    }
  })

  const mobileMenuOpen = ref(false)

  const navigation = [
    { name: 'Home', href: '/' },
    { name: 'Products', href: '/products' },
    { name: 'Categories', href: '/categories' },
    { name: 'Deals', href: '/deals' },
    { name: 'Contact', href: '/contact' }
  ]
  </script>
