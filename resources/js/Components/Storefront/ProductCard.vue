<script setup>
import { Link } from '@inertiajs/vue3';
import { formatMoney } from '@/utils/money';

defineProps({
    product: { type: Object, required: true },
    shopSlug: { type: String, required: true },
});
</script>

<template>
    <Link
        :href="route('storefront.products.show', [shopSlug, product.slug])"
        class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-200 transition hover:-translate-y-0.5 hover:shadow-md"
    >
        <div class="relative aspect-square overflow-hidden bg-stone-100">
            <img
                v-if="product.thumbnail_url"
                :src="product.thumbnail_url"
                :alt="product.name"
                loading="lazy"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
            />
            <div v-else class="flex h-full items-center justify-center text-sm text-stone-400">No image</div>
            <span
                v-if="!product.in_stock"
                class="absolute left-3 top-3 rounded-full bg-stone-900/80 px-2.5 py-1 text-xs font-semibold text-white"
                >Out of stock</span
            >
        </div>
        <div class="flex flex-1 flex-col p-4">
            <h3 class="font-semibold leading-snug text-stone-900 group-hover:text-emerald-700">{{ product.name }}</h3>
            <p class="mt-1 line-clamp-2 text-sm text-stone-500">{{ product.excerpt }}</p>
            <p class="mt-auto pt-3 text-lg font-bold text-stone-900">{{ formatMoney(product.price) }}</p>
        </div>
    </Link>
</template>
