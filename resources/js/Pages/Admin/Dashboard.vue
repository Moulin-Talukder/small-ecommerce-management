<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    stats: { type: Object, required: true },
});

const cards = computed(() => [
    { label: 'Shops', value: props.stats.shops, hint: `${props.stats.activeShops} active`, route: 'admin.shops.index' },
    { label: 'Catalog products', value: props.stats.products, hint: `${props.stats.unassignedProducts} not in any shop`, route: 'admin.products.index' },
    { label: 'Shop owners', value: props.stats.shopOwners, hint: `${props.stats.activeShopOwners} active`, route: 'admin.shop-owners.index' },
    { label: 'Shop listings', value: props.stats.listings, hint: `${props.stats.publicListings} publicly visible`, route: null },
]);
</script>

<template>
    <Head title="Admin dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Admin dashboard</h2>
        </template>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <component
                :is="card.route && route().has(card.route) ? Link : 'div'"
                v-for="card in cards"
                :key="card.label"
                :href="card.route && route().has(card.route) ? route(card.route) : undefined"
                class="rounded-lg bg-white p-5 shadow-sm transition hover:shadow"
            >
                <p class="text-sm font-medium text-gray-500">{{ card.label }}</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900">{{ card.value }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ card.hint }}</p>
            </component>
        </div>
    </AuthenticatedLayout>
</template>
