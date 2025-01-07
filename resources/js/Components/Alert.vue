<template>
    <TransitionRoot
      appear
      :show="show"
      as="template"
      enter="transform ease-out duration-300 transition"
      enter-from="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
      enter-to="translate-y-0 opacity-100 sm:translate-x-0"
      leave="transition ease-in duration-100"
      leave-from="opacity-100"
      leave-to="opacity-0"
    >
      <div
        :class="[
          'rounded-md p-4 mb-4',
          typeClasses[type]
        ]"
      >
        <div class="flex">
          <div class="flex-shrink-0">
            <i
              :class="[
                'fas',
                iconClasses[type],
                'h-5 w-5'
              ]"
            ></i>
          </div>
          <div class="ml-3">
            <h3
              v-if="title"
              :class="[
                'text-sm font-medium',
                titleClasses[type]
              ]"
            >
              {{ title }}
            </h3>
            <div
              :class="[
                'text-sm',
                messageClasses[type],
                title ? 'mt-2' : ''
              ]"
            >
              <p>{{ message }}</p>
            </div>
          </div>
          <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
              <button
                type="button"
                :class="[
                  'inline-flex rounded-md p-1.5',
                  buttonClasses[type]
                ]"
                @click="$emit('close')"
              >
                <span class="sr-only">Dismiss</span>
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </TransitionRoot>
  </template>

  <script setup>
  import { TransitionRoot } from '@headlessui/vue'
  import { computed } from 'vue'

  const props = defineProps({
    show: {
      type: Boolean,
      default: false
    },
    type: {
      type: String,
      default: 'success',
      validator: (value) => ['success', 'error', 'warning', 'info'].includes(value)
    },
    title: {
      type: String,
      default: ''
    },
    message: {
      type: String,
      required: true
    }
  })

  // Style configurations for different alert types
  const typeClasses = {
    success: 'bg-green-50 border border-green-200',
    error: 'bg-red-50 border border-red-200',
    warning: 'bg-yellow-50 border border-yellow-200',
    info: 'bg-blue-50 border border-blue-200'
  }

  const iconClasses = {
    success: 'fa-check-circle text-green-400',
    error: 'fa-exclamation-circle text-red-400',
    warning: 'fa-exclamation-triangle text-yellow-400',
    info: 'fa-info-circle text-blue-400'
  }

  const titleClasses = {
    success: 'text-green-800',
    error: 'text-red-800',
    warning: 'text-yellow-800',
    info: 'text-blue-800'
  }

  const messageClasses = {
    success: 'text-green-700',
    error: 'text-red-700',
    warning: 'text-yellow-700',
    info: 'text-blue-700'
  }

  const buttonClasses = {
    success: 'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-2 focus:ring-green-500 focus:ring-offset-2',
    error: 'bg-red-50 text-red-500 hover:bg-red-100 focus:ring-2 focus:ring-red-500 focus:ring-offset-2',
    warning: 'bg-yellow-50 text-yellow-500 hover:bg-yellow-100 focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2',
    info: 'bg-blue-50 text-blue-500 hover:bg-blue-100 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2'
  }
  </script>
