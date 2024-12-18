<template>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="animate-pulse">
            <!-- Simulate Table Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-5">
                <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-1/4"></div>
            </div>

            <!-- Simulate Table Rows -->
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                <div v-for="i in 5" :key="i" class="px-4 py-4">
                    <div class="flex items-center space-x-4">
                        <div class="h-12 w-12 bg-gray-200 dark:bg-gray-600 rounded-full"></div>
                        <div class="flex-1">
                            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded w-3/4 mb-2"></div>
                            <div class="h-3 bg-gray-200 dark:bg-gray-600 rounded w-1/2"></div>
                        </div>
                        <div class="h-8 w-8 bg-gray-200 dark:bg-gray-600 rounded"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
</script>
