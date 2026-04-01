<script setup>
import { computed, ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { switchTheme } from '@/theme';

const year = computed(() => new Date().getFullYear());

const isDark = ref(false);
onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

function toggleTheme() {
    switchTheme();
    isDark.value = document.documentElement.classList.contains('dark');
}
</script>

<template>
    <div
        class="min-h-screen flex flex-col bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-950 dark:to-gray-900">
        <!-- Navigation -->
        <nav class="bg-white/80 dark:bg-gray-950/70 backdrop-blur-sm border-b border-gray-100 dark:border-gray-800 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="route('home')" class="flex items-center">
                            <span class="text-xl font-semibold text-gray-900 dark:text-white">Mousumi Prokashon/ Variety Store</span>
                        </Link>
                    </div>

                    <!-- Right Navigation -->
                    <div class="flex items-center gap-2">
                        <button type="button" @click="toggleTheme"
                            class="inline-flex items-center justify-center rounded-full p-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                            :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'">
                            <span v-if="isDark" class="text-sm font-semibold">☀</span>
                            <span v-else class="text-sm font-semibold">🌙</span>
                        </button>
                        <slot name="nav-right" />
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow p-4 sm:p-6 lg:p-8">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-white dark:bg-gray-950 border-t border-gray-200 dark:border-gray-800">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Company Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 tracking-wider uppercase">
                            Mousumi Prokashon/ Variety Store
                        </h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Ukilpara, Naogaon Sadar, Naogaon.</p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Phone: (+88) 01334766435 | Email: mou.prokashon@gmail.com</p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 tracking-wider uppercase">
                            Quick Links
                        </h3>
                        <ul class="mt-2 space-y-2">
                            <li>
                                <Link :href="route('about')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                    About Us
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('contact')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                    Contact
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('privacy')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                    Privacy Policy
                                </Link>
                            </li>
                            <li>
                                <Link :href="route('terms')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                    Terms of Service
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 tracking-wider uppercase">
                            Contact Us
                        </h3>
                        <ul class="mt-2 space-y-2">
                            <li class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Email:</span> mou.prokashon@gmail.com
                            </li>
                            <li class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Phone:</span> (+88) 01334766435
                            </li>
                            <li class="text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-medium">Address:</span> Ukilpara, Naogaon Sadar, Naogaon.
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Copyright -->
                <div class="mt-8 pt-8 border-t border-gray-200 dark:border-gray-800">
                    <p class="text-center text-sm text-gray-500 dark:text-gray-500">
                        © {{ year }} Mousumi Prokashon/ Variety Store. All rights reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
