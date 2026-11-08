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
});

const filters = useFilters(props.filters, route('owner.products.index'));
const { confirmDelete } = useConfirm();

function destroy(product) {
    confirmDelete(route('owner.products.destroy', product.id), {
        title: `Delete ${product.name}?`,
        message: product.own_shops_count
            ? `It will be removed from the catalog and disappear from your ${product.own_shops_count} shop listing(s).`
            : 'It will be removed from the shared catalog.',
    });
}
</script>

<template>
    <Head title="My products" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">My products</h2>
                <p class="mt-1 text-sm text-gray-500">Products you created. They are part of the shared catalog, so other owners can sell them too.</p>
            </div>
            <Link
                :href="route('owner.products.create')"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
                + Create product
            </Link>
        </template>

        <Card>
            <div class="border-b p-4">
                <SearchInput v-model="filters.search" placeholder="Search my products…" />
            </div>

            <EmptyState v-if="products.data.length === 0" title="No products yet" message="Create a product to add it to the shared catalog." />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="hidden px-4 py-3 sm:table-cell">Base price</th>
                            <th class="hidden px-4 py-3 md:table-cell">Listed in</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <ProductThumb :url="product.thumbnail_url" :name="product.name" />
                                    <div>
                                        <div class="font-medium text-gray-900">{{ product.name }}</div>
                                        <div class="font-mono text-xs text-gray-500">{{ product.sku ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">{{ formatMoney(product.base_price) }}</td>
                            <td class="hidden px-4 py-3 text-gray-700 md:table-cell">
                                {{ product.own_shops_count }} of your shops
                                <span v-if="product.other_shops_count" class="ms-1 rounded bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-800">
                                    + {{ product.other_shops_count }} other shop(s)
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-x-4 gap-y-1 font-medium">
                                    <Link :href="route('owner.products.edit', product.id)" class="text-indigo-600 hover:text-indigo-800">Edit</Link>
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
