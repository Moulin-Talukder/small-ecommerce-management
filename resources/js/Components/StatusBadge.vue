<script setup>
defineProps({
    active: { type: Boolean, required: true },
    activeLabel: { type: String, default: 'Active' },
    inactiveLabel: { type: String, default: 'Inactive' },
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'"
    >
        <span class="h-1.5 w-1.5 rounded-full" :class="active ? 'bg-green-600' : 'bg-gray-500'" aria-hidden="true" />
        {{ active ? activeLabel : inactiveLabel }}
    </span>
</template>
