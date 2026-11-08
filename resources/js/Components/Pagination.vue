<script setup>
import { Link } from '@inertiajs/vue3';

// Accepts a Laravel LengthAwarePaginator serialized to JSON.
defineProps({
    paginator: { type: Object, required: true },
});

function label(text) {
    return text.replace('&laquo;', '‹').replace('&raquo;', '›');
}
</script>

<template>
    <nav
        v-if="paginator.total > 0"
        class="flex flex-col items-center justify-between gap-3 px-1 py-3 sm:flex-row"
        aria-label="Pagination"
    >
        <p class="text-sm text-gray-600">
            Showing <span class="font-medium">{{ paginator.from }}</span>–<span class="font-medium">{{ paginator.to }}</span>
            of <span class="font-medium">{{ paginator.total }}</span>
        </p>

        <div v-if="paginator.last_page > 1" class="flex flex-wrap items-center gap-1">
            <template v-for="(link, i) in paginator.links" :key="i">
                <span
                    v-if="!link.url"
                    class="rounded-md px-3 py-1.5 text-sm text-gray-400"
                    >{{ label(link.label) }}</span
                >
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="rounded-md px-3 py-1.5 text-sm transition"
                    :class="link.active ? 'bg-indigo-600 font-semibold text-white' : 'text-gray-700 hover:bg-gray-100'"
                    :aria-current="link.active ? 'page' : undefined"
                    >{{ label(link.label) }}</Link
                >
            </template>
        </div>
    </nav>
</template>
