<template>
    <div
      :class="[
        'inline-flex items-center justify-center',
        fullPage ? 'fixed inset-0 bg-white bg-opacity-75 z-50' : ''
      ]"
    >
      <div
        :class="[
          'inline-block animate-spin rounded-full border-4 border-solid',
          `h-${size} w-${size}`,
          colorClasses[color]
        ]"
        role="status"
      >
        <span class="sr-only">Loading...</span>
      </div>
      <span
        v-if="text"
        :class="[
          'ml-2 text-sm font-medium',
          textColorClasses[color]
        ]"
      >
        {{ text }}
      </span>
    </div>
  </template>

  <script setup>
  const props = defineProps({
    size: {
      type: String,
      default: '8', // Tailwind size classes (4, 8, 12, 16, etc.)
    },
    color: {
      type: String,
      default: 'primary',
      validator: (value) => ['primary', 'secondary', 'white'].includes(value)
    },
    text: {
      type: String,
      default: ''
    },
    fullPage: {
      type: Boolean,
      default: false
    }
  })

  const colorClasses = {
    primary: 'border-blue-600 border-t-transparent',
    secondary: 'border-gray-600 border-t-transparent',
    white: 'border-white border-t-transparent'
  }

  const textColorClasses = {
    primary: 'text-blue-600',
    secondary: 'text-gray-600',
    white: 'text-white'
  }
  </script>
