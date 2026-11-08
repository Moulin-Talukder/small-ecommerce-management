<script setup>
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';
import ProductCard from '@/Components/Storefront/ProductCard.vue';
import ShopLogo from '@/Components/Storefront/ShopLogo.vue';
import { formatMoney } from '@/utils/money';

defineProps({
    shop: { type: Object, required: true },
    product: { type: Object, required: true },
    others: { type: Array, required: true },
});
</script>

<template>
    <Head :title="`${product.name} – ${shop.name}`" />

    <StorefrontLayout>
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-12">
            <nav class="flex flex-wrap items-center gap-x-2 text-sm text-stone-500" aria-label="Breadcrumb">
                <Link href="/" class="hover:text-stone-900">Shops</Link>
                <span>/</span>
                <Link :href="route('storefront.shops.show', shop.slug)" class="hover:text-stone-900">{{ shop.name }}</Link>
                <span>/</span>
                <span class="text-stone-900">{{ product.name }}</span>
            </nav>

            <div class="mt-6 grid gap-8 md:grid-cols-2 md:gap-12">
                <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-stone-200">
                    <img v-if="product.thumbnail_url" :src="product.thumbnail_url" :alt="product.name" class="aspect-square w-full object-cover" />
                    <div v-else class="flex aspect-square items-center justify-center text-stone-400">No image</div>
                </div>

                <div class="flex flex-col">
                    <Link :href="route('storefront.shops.show', shop.slug)" class="flex items-center gap-3 self-start rounded-full bg-white py-1.5 pe-4 ps-1.5 text-sm font-medium shadow-sm ring-1 ring-stone-200 hover:ring-stone-300">
                        <ShopLogo :url="shop.logo_url" :name="shop.name" size="h-8 w-8" />
                        Sold by {{ shop.name }}
                    </Link>

                    <h1 class="mt-5 text-3xl font-bold tracking-tight sm:text-4xl">{{ product.name }}</h1>
                    <p class="mt-4 text-3xl font-bold text-emerald-700">{{ formatMoney(product.price) }}</p>

                    <p
                        class="mt-4 inline-flex items-center gap-2 self-start rounded-full px-3 py-1 text-sm font-semibold"
                        :class="product.in_stock ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-200 text-stone-700'"
                    >
                        <span class="h-2 w-2 rounded-full" :class="product.in_stock ? 'bg-emerald-500' : 'bg-stone-500'" aria-hidden="true" />
                        {{ product.in_stock ? 'In stock' : 'Out of stock' }}
                    </p>

                    <div v-if="product.description" class="mt-6 whitespace-pre-line leading-relaxed text-stone-700">{{ product.description }}</div>

                    <p v-if="product.sku" class="mt-6 text-xs text-stone-400">SKU {{ product.sku }}</p>

                    <Link
                        :href="route('storefront.shops.show', shop.slug)"
                        class="mt-8 inline-flex self-start rounded-full border border-stone-300 px-5 py-2.5 text-sm font-semibold transition hover:border-stone-900"
                    >
                        ← Back to {{ shop.name }}
                    </Link>
                </div>
            </div>

            <section v-if="others.length" class="mt-16">
                <h2 class="text-xl font-bold tracking-tight">Other products in {{ shop.name }}</h2>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:gap-6 lg:grid-cols-4">
                    <ProductCard v-for="other in others" :key="other.id" :product="other" :shop-slug="shop.slug" />
                </div>
            </section>
        </div>
    </StorefrontLayout>
</template>
