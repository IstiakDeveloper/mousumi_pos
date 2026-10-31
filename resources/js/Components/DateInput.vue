<script setup>
import { computed, ref, useAttrs, watch } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    min: { type: String, default: '' },
    max: { type: String, default: '' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'change', 'input'])
const attrs = useAttrs()
const text = ref('')
const invalid = ref(false)

const toDisplay = (value) => {
    const raw = String(value ?? '').trim()
    const iso = raw.match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (iso) {
        return `${iso[3]}-${iso[2]}-${iso[1]}`
    }
    const bd = raw.match(/^(\d{2})-(\d{2})-(\d{4})$/)
    return bd ? raw : ''
}

const toIso = (value) => {
    const match = String(value).trim().match(/^(\d{2})-(\d{2})-(\d{4})$/)
    if (!match) {
        return ''
    }
    const day = Number(match[1])
    const month = Number(match[2])
    const year = Number(match[3])
    const date = new Date(year, month - 1, day)
    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
        return ''
    }
    return `${match[3]}-${match[2]}-${match[1]}`
}

const isoValue = computed(() => toIso(text.value))

const publish = (iso) => {
    emit('update:modelValue', iso)
    emit('change', iso)
    emit('input', iso)
}

const commit = (display) => {
    if (!display) {
        invalid.value = false
        publish('')
        return
    }
    const iso = toIso(display)
    if (!iso || (props.min && iso < props.min) || (props.max && iso > props.max)) {
        invalid.value = display.length === 10
        return
    }
    invalid.value = false
    if (iso !== String(props.modelValue ?? '').slice(0, 10)) {
        publish(iso)
    }
}

const onType = (event) => {
    const digits = event.target.value.replace(/\D/g, '').slice(0, 8)
    let formatted = digits
    if (digits.length > 4) {
        formatted = `${digits.slice(0, 2)}-${digits.slice(2, 4)}-${digits.slice(4)}`
    } else if (digits.length > 2) {
        formatted = `${digits.slice(0, 2)}-${digits.slice(2)}`
    }
    text.value = formatted
    event.target.value = formatted
    if (formatted.length === 10 || formatted.length === 0) {
        commit(formatted)
    } else {
        invalid.value = false
    }
}

const onPicker = (event) => {
    text.value = toDisplay(event.target.value)
    commit(text.value)
}

watch(() => props.modelValue, (value) => {
    const next = toDisplay(value)
    if (next !== text.value) {
        text.value = next
        invalid.value = false
    }
}, { immediate: true })
</script>

<template>
    <div class="relative">
        <input
            v-bind="attrs"
            type="text"
            inputmode="numeric"
            autocomplete="off"
            maxlength="10"
            placeholder="dd-mm-yyyy"
            :value="text"
            :required="required"
            :disabled="disabled"
            :class="[attrs.class, invalid ? 'border-red-500' : '', 'pr-10']"
            @input="onType"
        />
        <input
            type="date"
            tabindex="-1"
            aria-label="Open calendar"
            class="absolute right-0 top-0 h-full w-10 cursor-pointer opacity-0"
            :value="isoValue"
            :min="min || undefined"
            :max="max || undefined"
            :disabled="disabled"
            @input="onPicker"
        />
    </div>
</template>
