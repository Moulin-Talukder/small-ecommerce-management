<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import ProductThumb from '@/Components/Products/ProductThumb.vue';
import SearchInput from '@/Components/SearchInput.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';
import { formatMoney } from '@/utils/money';

const props = defineProps({
    products: { type: Object, required: true },
    filters: { type: Object, required: true },
    creators: { type: Array, required: true },
});

const filters = useFilters(props.filters, route('admin.products.index'));
const { confirmDelete } = useConfirm();

function destroy(product) {
    confirmDelete(route('admin.products.destroy', product.id), {
        title: `Delete ${product.name}?`,
        message: product.shops_count
            ? `It is listed in ${product.shops_count} shop(s) and will disappear from all of them.`
            : 'It is not listed in any shop.',
    });
}
</script>

<template>
    <Head title="Catalog" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Catalog</h2>
                <p class="mt-1 text-sm text-gray-500">All products shared between shops, by any creator.</p>
            </div>
            <Link
                :href="route('admin.products.create')"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
                + New product
            </Link>
        </template>

        <Card>
            <div class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:flex-wrap sm:items-center">
                <SearchInput v-model="filters.search" placeholder="Search name, SKU, description…" />
                <select v-model="filters.creator" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-56">
                    <option value="">Any creator</option>
                    <option value="admin">Created by admin</option>
                    <option value="owners">Created by any shop owner</option>
                    <optgroup label="Specific shop owner">
                        <option v-for="creator in creators" :key="creator.id" :value="String(creator.id)">{{ creator.name }}</option>
                    </optgroup>
                </select>
                <select v-model="filters.listing" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-48">
                    <option value="">Listed or not</option>
                    <option value="listed">Listed in a shop</option>
                    <option value="unlisted">Not in any shop</option>
                </select>
            </div>

            <EmptyState v-if="products.data.length === 0" title="No products found" message="Try a different filter or add a product." />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="hidden px-4 py-3 md:table-cell">Creator</th>
                            <th class="hidden px-4 py-3 sm:table-cell">Base price</th>
                            <th class="hidden px-4 py-3 lg:table-cell">Shops</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <ProductThumb :url="product.thumbnail_url" :name="product.name" />
                                    <div class="min-w-0">
                                        <div class="font-medium text-gray-900">{{ product.name }}</div>
                                        <div class="font-mono text-xs text-gray-500">{{ product.sku ?? '—' }}</div>
                                        <div class="mt-0.5 text-xs text-gray-500 sm:hidden">
                                            {{ formatMoney(product.base_price) }} · {{ product.shops_count }} shop(s)
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <div class="text-gray-900">{{ product.creator?.name ?? 'Deleted user' }}</div>
                                <div class="text-xs text-gray-500">{{ product.creator ? (product.creator.role === 'admin' ? 'Admin' : 'Shop owner') : '' }}</div>
                            </td>
                            <td class="hidden px-4 py-3 text-gray-900 sm:table-cell">{{ formatMoney(product.base_price) }}</td>
                            <td class="hidden px-4 py-3 lg:table-cell">
                                <span v-if="product.shops_count" class="text-gray-700">Listed in {{ product.shops_count }} shop(s)</span>
                                <span v-else class="rounded bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800">Not in any shop</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-x-4 gap-y-1 font-medium">
                                    <Link :href="route('admin.products.edit', product.id)" class="text-indigo-600 hover:text-indigo-800">Edit</Link>
                                    <button type="button" class="text-red-600 hover:text-red-800" @click="destroy(product)">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t px-4">
                <Pagination :paginator="products" />
            </div>
        </Card>
    </AuthenticatedLayout>
</template>
