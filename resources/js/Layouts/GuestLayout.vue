<script setup>
import { computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';

const year = computed(() => new Date().getFullYear());
</script>

<template>
    <div class="min-h-screen flex flex-col bg-gradient-to-br from-blue-50 to-indigo-50">
        <!-- Navigation -->
        <nav class="bg-white/80 backdrop-blur-sm border-b border-gray-100 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <!-- Logo -->
                        <Link :href="route('home')" class="flex items-center">
                            <ApplicationLogo class="block h-9 w-auto" />
                            <span class="ml-3 text-xl font-semibold text-gray-900">Mousumi Prokashon</span>
                        </Link>
                    </div>

                    <!-- Right Navigation -->
                    <div class="flex items-center">
                        <Link
                            :href="route('login')"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Sign in
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow flex items-center justify-center p-4">
            <div class="w-full">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Company Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 tracking-wider uppercase">
                            Mousumi Prokashon
                        </h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Professional Point of Sale System for Publishing Houses
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 tracking-wider uppercase">
                            Quick Links
                        </h3>
                        <ul class="mt-2 space-y-2">
                            <li>
                                <Link :href="route('about')" class="text-sm text-gray-600 hover:text-gray-900">
                                    About Us
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('contact')" class="text-sm text-gray-600 hover:text-gray-900">
                                    Contact
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('privacy')" class="text-sm text-gray-600 hover:text-gray-900">
                                    Privacy Policy
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('terms')" class="text-sm text-gray-600 hover:text-gray-900">
                                    Terms of Service
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 tracking-wider uppercase">
                            Contact Us
                        </h3>
                        <ul class="mt-2 space-y-2">
                            <li class="text-sm text-gray-600">
                                <span class="font-medium">Email:</span> support@mousumiprokashon.com
                            </li>
                            <li class="text-sm text-gray-600">
                                <span class="font-medium">Phone:</span> +880 XXX XXX XXXX
                            </li>
                            <li class="text-sm text-gray-600">
                                <span class="font-medium">Hours:</span> 9:00 AM - 6:00 PM (BD Time)
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Copyright -->
                <div class="mt-8 pt-8 border-t border-gray-200">
                    <p class="text-center text-sm text-gray-500">
                        © {{ year }} Mousumi Prokashon. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
