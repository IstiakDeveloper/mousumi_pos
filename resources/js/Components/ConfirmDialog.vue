<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Confirm Action'
  },
  message: {
    type: String,
    default: 'Are you sure you want to proceed?'
  },
  confirmText: {
    type: String,
    default: 'Confirm'
  },
  cancelText: {
    type: String,
    default: 'Cancel'
  }
})

const emit = defineEmits(['confirm', 'cancel', 'update:show'])

const isOpen = ref(props.show)

watch(() => props.show, (newValue) => {
  isOpen.value = newValue
})

const handleConfirm = () => {
  emit('confirm')
  emit('update:show', false)
}

const handleCancel = () => {
  emit('cancel')
  emit('update:show', false)
}
</script>

<template>
  <transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none"
    >
      <div
        class="relative w-auto max-w-lg mx-auto my-6 transition-all duration-300 ease-in-out transform"
      >
        <div
          class="relative flex flex-col w-full bg-white dark:bg-gray-800 border-0 rounded-lg shadow-lg outline-none focus:outline-none"
        >
          <div
            class="flex items-start justify-between p-5 border-b border-solid rounded-t dark:border-gray-700"
          >
            <h3 class="text-xl font-semibold dark:text-white">
              {{ title }}
            </h3>
            <button
              @click="handleCancel"
              class="float-right p-1 ml-auto text-3xl font-semibold leading-none text-gray-500 bg-transparent border-0 outline-none opacity-5 focus:outline-none"
            >
              <span class="block w-6 h-6 text-2xl text-gray-500 opacity-5 dark:text-white">
                ×
              </span>
            </button>
          </div>

          <div class="relative flex-auto p-6">
            <p class="my-4 text-gray-600 dark:text-gray-300 text-lg leading-relaxed">
              {{ message }}
            </p>
          </div>

          <div
            class="flex items-center justify-end p-6 border-t border-solid rounded-b dark:border-gray-700"
          >
            <button
              @click="handleCancel"
              class="px-6 py-2 mb-1 mr-4 text-sm font-bold text-gray-600 uppercase transition-all duration-150 ease-linear bg-gray-200 rounded-lg dark:bg-gray-700 dark:text-white hover:bg-gray-300 dark:hover:bg-gray-600"
            >
              {{ cancelText }}
            </button>
            <button
              @click="handleConfirm"
              class="px-6 py-2 mb-1 text-sm font-bold text-white uppercase transition-all duration-150 ease-linear bg-red-500 rounded-lg hover:bg-red-600"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>
