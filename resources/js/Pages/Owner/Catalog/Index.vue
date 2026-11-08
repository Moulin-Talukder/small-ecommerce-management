<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AddToShopModal from '@/Components/Listings/AddToShopModal.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import ProductThumb from '@/Components/Products/ProductThumb.vue';
import SearchInput from '@/Components/SearchInput.vue';
import { useFilters } from '@/composables/useFilters';
import { formatMoney } from '@/utils/money';

const props = defineProps({
    products: { type: Object, required: true },
    shops: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const filters = useFilters(props.filters, route('owner.catalog.index'));
const selectedShop = computed(() => props.shops.find((s) => s.id === props.filters.shop) ?? null);
const adding = ref(null);
</script>

<template>
    <Head title="Catalog" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Shared catalog</h2>
                <p class="mt-1 text-sm text-gray-500">Products from the admin and all shop owners. Add any of them to your shop.</p>
            </div>
            <Link
                :href="route('owner.products.create')"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
                + Create product
            </Link>
        </template>

        <Card class="mb-6">
            <div class="flex flex-col gap-3 p-4 lg:flex-row lg:items-center">
                <label v-if="shops.length" class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <span class="shrink-0">Shop:</span>
                    <select v-model="filters.shop" class="w-full rounded-md border-indigo-300 bg-indigo-50 text-sm font-semibold text-indigo-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:w-56">
                        <option v-for="shop in shops" :key="shop.id" :value="shop.id">{{ shop.name }}{{ shop.is_active ? '' : ' (inactive)' }}</option>
                    </select>
                </label>
                <SearchInput v-model="filters.search" placeholder="Search name, SKU, description…" />
                <select v-model="filters.creator" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:w-48">
                    <option value="">Any creator</option>
                    <option value="admin">Admin</option>
                    <option value="mine">Created by me</option>
                    <option value="others">Other shop owners</option>
                </select>
                <select
                    v-if="selectedShop"
                    v-model="filters.availability"
                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 lg:w-52"
                >
                    <option value="">All products</option>
                    <option value="available">Not in {{ selectedShop.name }}</option>
                    <option value="in_shop">Already in {{ selectedShop.name }}</option>
                </select>
            </div>
            <p v-if="!shops.length" class="border-t px-4 py-3 text-sm text-amber-800">
                You don't have a shop yet, so you can browse the catalog but not add products. Ask the administrator to assign a shop to you.
            </p>
        </Card>

        <Card v-if="products.data.length === 0">
            <EmptyState title="No products found" message="Try a different search or filter." />
        </Card>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <div v-for="product in products.data" :key="product.id" class="flex overflow-hidden rounded-lg bg-white shadow-sm sm:flex-col">
                <!-- Compact row on phones, square image card from sm: up -->
                <ProductThumb :url="product.thumbnail_url" :name="product.name" size="h-28 w-28 rounded-none sm:aspect-square sm:h-auto sm:w-full" />
                <div class="flex min-w-0 flex-1 flex-col p-3 sm:p-4">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-gray-900">{{ product.name }}</h3>
                        <span class="shrink-0 font-semibold text-gray-900">{{ formatMoney(product.base_price) }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        <span class="font-mono">{{ product.sku ?? 'No SKU' }}</span> ·
                        <span :class="product.is_mine ? 'font-medium text-indigo-700' : ''">
                            by {{ product.is_mine ? 'you' : product.creator_is_admin ? 'Admin' : product.creator_name }}
                        </span>
                    </p>
                    <p class="mt-2 line-clamp-2 hidden text-sm text-gray-600 sm:block">{{ product.description }}</p>

                    <div class="mt-auto flex flex-wrap items-center gap-x-3 gap-y-2 pt-3 text-sm font-medium sm:pt-4">
                        <template v-if="selectedShop">
                            <span v-if="product.listing_id" class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2.5 py-1.5 text-green-800">
                                ✓ Already in this shop
                            </span>
                            <button
                                v-else
                                type="button"
                                class="rounded-md bg-indigo-600 px-3 py-1.5 text-white shadow-sm hover:bg-indigo-500"
                                @click="adding = product"
                            >
                                Add to shop
                            </button>
                            <Link
                                v-if="product.listing_id && route().has('owner.listings.edit')"
                                :href="route('owner.listings.edit', [selectedShop.id, product.listing_id])"
                                class="text-indigo-600 hover:text-indigo-800"
                            >
                                Edit listing
                            </Link>
                        </template>
                        <Link v-if="product.is_mine" :href="route('owner.products.edit', product.id)" class="ms-auto text-gray-600 hover:text-gray-900">
                            Edit product
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <Pagination :paginator="products" class="mt-4" />

        <AddToShopModal :product="adding" :shop="selectedShop" @close="adding = null" />
    </AuthenticatedLayout>
</template>
