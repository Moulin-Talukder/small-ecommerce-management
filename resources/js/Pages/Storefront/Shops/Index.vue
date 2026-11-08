<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ShopLogo from '@/Components/Storefront/ShopLogo.vue';

defineProps({
    shops: { type: Object, required: true },
});
</script>

<template>
    <Head title="Independent shops" />

    <StorefrontLayout>
        <section class="relative overflow-hidden bg-gradient-to-br from-emerald-900 via-emerald-800 to-stone-900 text-white">
            <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl" aria-hidden="true" />
            <div class="relative mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24">
                <p class="text-sm font-semibold uppercase tracking-widest text-emerald-300">Shop local, shop small</p>
                <h1 class="mt-3 max-w-2xl text-4xl font-bold tracking-tight sm:text-5xl">Discover independent shops and the things they love.</h1>
                <p class="mt-4 max-w-xl text-lg text-emerald-100/90">
                    Every shop curates its own selection, with its own prices and its own story.
                </p>
                <a href="#shops" class="mt-8 inline-flex rounded-full bg-white px-6 py-3 text-sm font-semibold text-emerald-900 shadow transition hover:bg-emerald-50">
                    Browse shops ↓
                </a>
            </div>
        </section>

        <section id="shops" class="mx-auto max-w-6xl scroll-mt-20 px-4 py-12 sm:px-6 sm:py-16">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Our shops</h2>
                    <p class="mt-1 text-stone-500">{{ shops.total }} {{ shops.total === 1 ? 'shop' : 'shops' }} open right now</p>
                </div>
            </div>

            <div v-if="shops.data.length === 0" class="mt-8 rounded-2xl border-2 border-dashed border-stone-300 bg-white px-6 py-16 text-center">
                <p class="text-lg font-semibold">No shops are open yet</p>
                <p class="mt-1 text-stone-500">Please check back soon.</p>
            </div>

            <div v-else class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="shop in shops.data"
                    :key="shop.slug"
                    :href="route('storefront.shops.show', shop.slug)"
                    class="group flex flex-col rounded-2xl bg-white p-6 shadow-sm ring-1 ring-stone-200 transition hover:-translate-y-0.5 hover:shadow-md"
                >
                    <div class="flex items-center gap-4">
                        <ShopLogo :url="shop.logo_url" :name="shop.name" size="h-16 w-16" />
                        <div class="min-w-0">
                            <h3 class="truncate text-lg font-semibold group-hover:text-emerald-700">{{ shop.name }}</h3>
                            <p class="text-sm text-stone-500">{{ shop.products_count }} {{ shop.products_count === 1 ? 'product' : 'products' }}</p>
                        </div>
                    </div>
                    <p class="mt-4 line-clamp-3 text-sm leading-relaxed text-stone-600">{{ shop.description }}</p>
                    <span class="mt-auto pt-5 text-sm font-semibold text-emerald-700">Visit shop →</span>
                </Link>
            </div>

            <Pagination :paginator="shops" class="mt-8" />
        </section>
    </StorefrontLayout>
</template>
