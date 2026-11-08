<script setup>
defineProps({
    url: { type: String, default: null },
    name: { type: String, required: true },
    size: { type: String, default: 'h-12 w-12' },
});
</script>

<template>
    <img v-if="url" :src="url" :alt="name" loading="lazy" class="shrink-0 rounded-md object-cover" :class="size" />
    <div v-else class="flex shrink-0 items-center justify-center rounded-md bg-gray-100 text-xs text-gray-400" :class="size">No image</div>
</template>
