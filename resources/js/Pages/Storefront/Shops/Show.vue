<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';
import ProductCard from '@/Components/Storefront/ProductCard.vue';
import ShopLogo from '@/Components/Storefront/ShopLogo.vue';
import { useFilters } from '@/composables/useFilters';

const props = defineProps({
    shop: { type: Object, required: true },
    listings: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const filters = useFilters(props.filters, route('storefront.shops.show', props.shop.slug));
</script>

<template>
    <Head :title="shop.name" />

    <StorefrontLayout>
        <section class="border-b border-stone-200 bg-white">
            <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
                <nav class="text-sm text-stone-500" aria-label="Breadcrumb">
                    <Link href="/" class="hover:text-stone-900">Shops</Link>
                    <span class="mx-2">/</span>
                    <span class="text-stone-900">{{ shop.name }}</span>
                </nav>
                <div class="mt-5 flex flex-col gap-5 sm:flex-row sm:items-center">
                    <ShopLogo :url="shop.logo_url" :name="shop.name" size="h-20 w-20 sm:h-24 sm:w-24" />
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ shop.name }}</h1>
                        <p v-if="shop.description" class="mt-2 max-w-2xl text-stone-600">{{ shop.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-stone-500">
                    {{ listings.total }} {{ listings.total === 1 ? 'product' : 'products' }}
                    <template v-if="filters.search"> matching “{{ filters.search }}”</template>
                </p>
                <SearchInput v-model="filters.search" placeholder="Search this shop…" />
            </div>

            <div v-if="listings.data.length === 0" class="mt-8 rounded-2xl border-2 border-dashed border-stone-300 bg-white px-6 py-16 text-center">
                <template v-if="filters.search">
                    <p class="text-lg font-semibold">No products match “{{ filters.search }}”</p>
                    <button type="button" class="mt-3 text-sm font-semibold text-emerald-700 hover:underline" @click="filters.search = ''">Clear search</button>
                </template>
                <template v-else>
                    <p class="text-lg font-semibold">This shop has no products yet</p>
                    <p class="mt-1 text-stone-500">Come back soon to see what they add.</p>
                </template>
            </div>

            <div v-else class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3 lg:grid-cols-4">
                <ProductCard v-for="product in listings.data" :key="product.id" :product="product" :shop-slug="shop.slug" />
            </div>

            <Pagination :paginator="listings" class="mt-8" />
        </section>
    </StorefrontLayout>
</template>
