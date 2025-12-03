<template>
    <div class="relative" ref="dropdown">
        <!-- Selected Value Display / Search Input -->
        <div @click="toggleDropdown"
            class="relative w-full cursor-pointer bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:border-gray-400 dark:hover:border-gray-500 transition-colors">
            <div class="flex items-center px-4 py-3">
                <div class="flex-1 min-w-0">
                    <input v-if="isOpen" v-model="searchQuery" ref="searchInput" type="text"
                        :placeholder="placeholder"
                        class="w-full border-0 p-0 focus:ring-0 bg-transparent text-gray-900 dark:text-gray-100 placeholder-gray-400 cursor-text"
                        @click.stop
                        @input="onSearch" @keydown.escape="closeDropdown"
                        @keydown.down.prevent="highlightNext" @keydown.up.prevent="highlightPrev"
                        @keydown.enter.prevent="selectHighlighted" />
                    <div v-else class="flex items-center pointer-events-none">
                        <span v-if="selectedOption"
                            class="block truncate text-gray-900 dark:text-gray-100 font-medium">
                            {{ selectedOption[labelKey] }}
                        </span>
                        <span v-else class="block truncate text-gray-400">
                            {{ placeholder }}
                        </span>
                    </div>
                </div>
                <div class="ml-3 flex items-center gap-2">
                    <span v-if="selectedOption && showBadge"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ badgeText }}
                    </span>
                    <button v-if="selectedOption && clearable" type="button" @click.stop="clear"
                        class="flex-shrink-0 rounded-full p-1 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-4 h-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
                        :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Dropdown Menu -->
        <Transition enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1">
            <div v-if="isOpen"
                class="absolute z-50 mt-2 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 max-h-80 overflow-hidden">
                <!-- Options List -->
                <div class="overflow-y-auto max-h-80 py-1" ref="optionsList">
                    <div v-if="filteredOptions.length === 0"
                        class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm">No results found</p>
                    </div>
                    <button v-for="(option, index) in filteredOptions" :key="option[valueKey]" type="button"
                        @click="selectOption(option)"
                        @mouseenter="highlightedIndex = index"
                        :class="{
                            'bg-blue-50 dark:bg-blue-900/50': highlightedIndex === index,
                            'bg-blue-100 dark:bg-blue-900': selectedOption && option[valueKey] === selectedOption[valueKey]
                        }"
                        class="w-full px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                    {{ option[labelKey] }}
                                </p>
                                <p v-if="option[descriptionKey]"
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ option[descriptionKey] }}
                                </p>
                            </div>
                            <div v-if="selectedOption && option[valueKey] === selectedOption[valueKey]"
                                class="ml-3 flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Object],
        default: null
    },
    options: {
        type: Array,
        required: true
    },
    labelKey: {
        type: String,
        default: 'name'
    },
    valueKey: {
        type: String,
        default: 'id'
    },
    descriptionKey: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Select an option...'
    },
    clearable: {
        type: Boolean,
        default: true
    },
    showBadge: {
        type: Boolean,
        default: false
    },
    badgeText: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'change']);

const dropdown = ref(null);
const searchInput = ref(null);
const optionsList = ref(null);
const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(-1);

const selectedOption = computed(() => {
    if (!props.modelValue) return null;
    return props.options.find(opt => opt[props.valueKey] === props.modelValue);
});

const filteredOptions = computed(() => {
    if (!searchQuery.value) return props.options;
    const query = searchQuery.value.toLowerCase();
    return props.options.filter(option => {
        const label = option[props.labelKey]?.toString().toLowerCase() || '';
        const description = option[props.descriptionKey]?.toString().toLowerCase() || '';
        return label.includes(query) || description.includes(query);
    });
});

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        nextTick(() => {
            searchInput.value?.focus();
            highlightedIndex.value = selectedOption.value
                ? filteredOptions.value.findIndex(opt => opt[props.valueKey] === selectedOption.value[props.valueKey])
                : 0;
            scrollToHighlighted();
        });
    } else {
        searchQuery.value = '';
    }
};

const openDropdown = () => {
    if (!isOpen.value) {
        isOpen.value = true;
        nextTick(() => {
            searchInput.value?.focus();
            highlightedIndex.value = selectedOption.value
                ? filteredOptions.value.findIndex(opt => opt[props.valueKey] === selectedOption.value[props.valueKey])
                : 0;
            scrollToHighlighted();
        });
    }
};

const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
    highlightedIndex.value = -1;
};

const selectOption = (option) => {
    emit('update:modelValue', option[props.valueKey]);
    emit('change', option);
    closeDropdown();
};

const selectHighlighted = () => {
    if (highlightedIndex.value >= 0 && highlightedIndex.value < filteredOptions.value.length) {
        selectOption(filteredOptions.value[highlightedIndex.value]);
    }
};

const highlightNext = () => {
    if (highlightedIndex.value < filteredOptions.value.length - 1) {
        highlightedIndex.value++;
        scrollToHighlighted();
    }
};

const highlightPrev = () => {
    if (highlightedIndex.value > 0) {
        highlightedIndex.value--;
        scrollToHighlighted();
    }
};

const scrollToHighlighted = () => {
    nextTick(() => {
        const list = optionsList.value;
        if (!list) return;
        const items = list.querySelectorAll('button');
        const highlighted = items[highlightedIndex.value];
        if (highlighted) {
            highlighted.scrollIntoView({ block: 'nearest' });
        }
    });
};

const clear = () => {
    emit('update:modelValue', null);
    emit('change', null);
    searchQuery.value = '';
};

const onSearch = () => {
    highlightedIndex.value = 0;
};

const handleClickOutside = (event) => {
    if (dropdown.value && !dropdown.value.contains(event.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

watch(isOpen, (newValue) => {
    if (!newValue) {
        highlightedIndex.value = -1;
    }
});
</script>
