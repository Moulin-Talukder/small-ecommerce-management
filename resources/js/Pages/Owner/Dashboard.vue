<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({
    shops: { type: Array, required: true },
});
</script>

<template>
    <Head title="My shops" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">My shops</h2>
        </template>

        <Card v-if="shops.length === 0">
            <EmptyState title="You don't have a shop yet" message="Shops are created by the administrator. Ask them to assign a shop to your account." />
        </Card>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <Card v-for="shop in shops" :key="shop.id">
                <div class="flex gap-4 p-5">
                    <img v-if="shop.logo_url" :src="shop.logo_url" :alt="shop.name" class="h-16 w-16 shrink-0 rounded-lg object-cover" />
                    <div v-else class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xl font-semibold text-gray-400">
                        {{ shop.name.charAt(0) }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-lg font-semibold text-gray-900">{{ shop.name }}</h3>
                            <StatusBadge :active="shop.is_active" />
                        </div>
                        <p class="mt-1 line-clamp-2 text-sm text-gray-600">{{ shop.description }}</p>
                        <p class="mt-2 text-xs text-gray-500">
                            {{ shop.listings_count }} products listed · {{ shop.active_listings_count }} active
                        </p>
                        <div class="mt-3 flex flex-wrap gap-4 text-sm font-medium">
                            <Link
                                v-if="route().has('owner.listings.index')"
                                :href="route('owner.listings.index', shop.id)"
                                class="text-indigo-600 hover:text-indigo-800"
                            >
                                Manage products →
                            </Link>
                            <a
                                v-if="shop.is_active"
                                :href="`/shops/${shop.slug}`"
                                target="_blank"
                                class="text-gray-600 hover:text-gray-900"
                            >
                                View public page ↗
                            </a>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
