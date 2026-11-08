<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import ProductThumb from '@/Components/Products/ProductThumb.vue';
import SearchInput from '@/Components/SearchInput.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';
import { formatMoney } from '@/utils/money';

const props = defineProps({
    shop: { type: Object, required: true },
    shops: { type: Array, required: true },
    listings: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const filters = useFilters(props.filters, route('owner.listings.index', props.shop.id));
const { confirmDelete } = useConfirm();

function switchShop(event) {
    router.get(route('owner.listings.index', event.target.value));
}

function toggle(listing) {
    router.patch(route('owner.listings.toggle', [props.shop.id, listing.id]), {}, { preserveScroll: true });
}

function remove(listing) {
    confirmDelete(route('owner.listings.destroy', [props.shop.id, listing.id]), {
        title: `Remove “${listing.name}” from ${props.shop.name}?`,
        message: 'The listing and its shop-specific settings are deleted. The product stays in the shared catalog.',
        confirmText: 'Remove',
    });
}

const overrideLabels = { price: 'price', name: 'name', description: 'description', thumbnail: 'image' };
</script>

<template>
    <Head :title="`${shop.name} – products`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ shop.name }}</h2>
                    <StatusBadge :active="shop.is_active" />
                </div>
                <p class="mt-1 text-sm text-gray-500">
                    Products listed in this shop.
                    <a v-if="shop.is_active" :href="`/shops/${shop.slug}`" target="_blank" class="text-indigo-600 hover:underline">View public page ↗</a>
                </p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <select
                    v-if="shops.length > 1"
                    :value="shop.id"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    aria-label="Switch shop"
                    @change="switchShop"
                >
                    <option v-for="s in shops" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <Link
                    :href="route('owner.catalog.index', { shop: shop.id, availability: 'available' })"
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Add from catalog
                </Link>
            </div>
        </template>

        <Card>
            <div class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center">
                <SearchInput v-model="filters.search" placeholder="Search name or SKU…" />
                <select v-model="filters.status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-44">
                    <option value="">All listings</option>
                    <option value="active">Visible</option>
                    <option value="inactive">Hidden</option>
                </select>
            </div>

            <EmptyState
                v-if="listings.data.length === 0"
                :title="filters.search || filters.status ? 'No matching listings' : 'No products in this shop yet'"
                message="Browse the shared catalog to add products."
            />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="hidden px-4 py-3 sm:table-cell">Price</th>
                            <th class="hidden px-4 py-3 md:table-cell">Stock</th>
                            <th class="hidden px-4 py-3 sm:table-cell">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="listing in listings.data" :key="listing.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <ProductThumb :url="listing.thumbnail_url" :name="listing.name" />
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900">{{ listing.name }}</div>
                                        <div v-if="listing.name !== listing.catalog_name" class="text-xs text-gray-500">Catalog: {{ listing.catalog_name }}</div>
                                        <div v-if="listing.overrides.length" class="mt-1 flex flex-wrap gap-1">
                                            <span v-for="o in listing.overrides" :key="o" class="rounded bg-indigo-50 px-1.5 py-0.5 text-[11px] font-medium text-indigo-700">
                                                custom {{ overrideLabels[o] }}
                                            </span>
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500 sm:hidden">
                                            {{ formatMoney(listing.price) }} · stock {{ listing.stock }} · {{ listing.is_active ? 'visible' : 'hidden' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                <div class="font-medium text-gray-900">{{ formatMoney(listing.price) }}</div>
                                <div v-if="listing.overrides.includes('price')" class="text-xs text-gray-500">catalog {{ formatMoney(listing.base_price) }}</div>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <span :class="listing.stock === 0 ? 'font-medium text-red-700' : 'text-gray-700'">{{ listing.stock === 0 ? 'Out of stock' : listing.stock }}</span>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell"><StatusBadge :active="listing.is_active" active-label="Visible" inactive-label="Hidden" /></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-x-4 gap-y-1 font-medium">
                                    <Link :href="route('owner.listings.edit', [shop.id, listing.id])" class="text-indigo-600 hover:text-indigo-800">Edit</Link>
                                    <button type="button" class="text-gray-600 hover:text-gray-900" @click="toggle(listing)">{{ listing.is_active ? 'Hide' : 'Show' }}</button>
                                    <button type="button" class="text-red-600 hover:text-red-800" @click="remove(listing)">Remove</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t px-4">
                <Pagination :paginator="listings" />
            </div>
        </Card>
    </AuthenticatedLayout>
</template>
