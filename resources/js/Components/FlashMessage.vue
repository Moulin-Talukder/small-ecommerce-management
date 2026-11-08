<script setup>
import { ref, watch, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const toasts = ref([]);
let nextId = 1;
const timers = new Map();

function dismiss(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id);
    clearTimeout(timers.get(id));
    timers.delete(id);
}

function push(type, message) {
    const id = nextId++;
    toasts.value.push({ id, type, message });
    timers.set(id, setTimeout(() => dismiss(id), type === 'error' ? 7000 : 4000));
}

// Every Inertia visit returns a fresh `flash` object, so a non-deep watch
// fires once per response, even when the same message repeats.
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) push('success', flash.success);
        if (flash?.error) push('error', flash.error);
    },
    { immediate: true },
);

onBeforeUnmount(() => timers.forEach((t) => clearTimeout(t)));
</script>

<template>
    <div
        aria-live="polite"
        class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4 sm:items-end sm:px-6"
    >
        <TransitionGroup
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-for="toast in toasts"
                :key="toast.id"
                role="status"
                class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border p-4 shadow-lg"
                :class="toast.type === 'error' ? 'border-red-200 bg-red-50 text-red-800' : 'border-green-200 bg-green-50 text-green-800'"
            >
                <span aria-hidden="true" class="mt-0.5 text-lg leading-none">{{ toast.type === 'error' ? '⚠' : '✓' }}</span>
                <p class="flex-1 text-sm font-medium">{{ toast.message }}</p>
                <button type="button" class="text-current opacity-60 hover:opacity-100" aria-label="Dismiss" @click="dismiss(toast.id)">✕</button>
            </div>
        </TransitionGroup>
    </div>
</template>
