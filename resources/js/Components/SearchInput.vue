<script setup>
import { ref, watch } from 'vue';

// Debounced search box: emits update:modelValue 300 ms after typing stops.
const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Search…' },
});
const emit = defineEmits(['update:modelValue']);

const value = ref(props.modelValue ?? '');
let timer;

watch(() => props.modelValue, (v) => (value.value = v ?? ''));
watch(value, (v) => {
    clearTimeout(timer);
    timer = setTimeout(() => emit('update:modelValue', v), 300);
});
</script>

<template>
    <div class="relative w-full sm:max-w-xs">
        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-gray-400" aria-hidden="true">⌕</span>
        <input
            v-model="value"
            type="search"
            :placeholder="placeholder"
            class="w-full rounded-md border-gray-300 ps-8 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        />
    </div>
</template>
