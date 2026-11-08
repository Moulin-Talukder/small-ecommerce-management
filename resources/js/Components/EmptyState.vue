<script setup>
defineProps({
    title: { type: String, default: 'Nothing here yet' },
    message: { type: String, default: '' },
});
</script>

<template>
    <div class="px-6 py-12 text-center">
        <p class="text-sm font-semibold text-gray-900">{{ title }}</p>
        <p v-if="message" class="mt-1 text-sm text-gray-500">{{ message }}</p>
        <div v-if="$slots.default" class="mt-4"><slot /></div>
    </div>
</template>
