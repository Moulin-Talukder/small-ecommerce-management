<script setup>
defineProps({
    url: { type: String, default: null },
    name: { type: String, required: true },
    size: { type: String, default: 'h-14 w-14' },
});
</script>

<template>
    <img v-if="url" :src="url" :alt="`${name} logo`" class="shrink-0 rounded-xl object-cover ring-1 ring-stone-200" :class="size" />
    <div v-else class="flex shrink-0 items-center justify-center rounded-xl bg-emerald-100 font-bold text-emerald-800" :class="size">
        {{ name.charAt(0) }}
    </div>
</template>
