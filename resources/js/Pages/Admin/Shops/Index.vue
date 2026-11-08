<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';

const props = defineProps({
    shops: { type: Object, required: true },
    filters: { type: Object, required: true },
    owners: { type: Array, required: true },
});

const filters = useFilters(props.filters, route('admin.shops.index'));
const { confirmDelete } = useConfirm();

function destroy(shop) {
    confirmDelete(route('admin.shops.destroy', shop.id), {
        title: `Delete ${shop.name}?`,
        message: `This also removes its ${shop.shop_products_count} product listing(s). Catalog products are not affected.`,
    });
}
</script>

<template>
    <Head title="Shops" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Shops</h2>
            <Link
                :href="route('admin.shops.create')"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
                + New shop
            </Link>
        </template>

        <Card>
            <div class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center">
                <SearchInput v-model="filters.search" placeholder="Search shops…" />
                <select v-model="filters.owner" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-64">
                    <option value="">All owners</option>
                    <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.label }}</option>
                </select>
                <select v-model="filters.status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-40">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <EmptyState v-if="shops.data.length === 0" title="No shops found" message="Try a different filter or create a new shop." />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Shop</th>
                            <th class="hidden px-4 py-3 md:table-cell">Owner</th>
                            <th class="hidden px-4 py-3 sm:table-cell">Listings</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="shop in shops.data" :key="shop.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img v-if="shop.logo_url" :src="shop.logo_url" :alt="shop.name" class="h-10 w-10 shrink-0 rounded-md object-cover" />
                                    <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-gray-100 font-semibold text-gray-400">
                                        {{ shop.name.charAt(0) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate font-medium text-gray-900">{{ shop.name }}</div>
                                        <div class="truncate font-mono text-xs text-gray-500">/shops/{{ shop.slug }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                <div class="text-gray-900">{{ shop.owner.name }}</div>
                                <div class="text-xs" :class="shop.owner.is_active ? 'text-gray-500' : 'text-amber-700'">
                                    {{ shop.owner.is_active ? shop.owner.email : 'Owner inactive — shop hidden' }}
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 text-gray-700 sm:table-cell">{{ shop.shop_products_count }}</td>
                            <td class="px-4 py-3"><StatusBadge :active="shop.is_active" /></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-x-4 gap-y-1 font-medium">
                                    <Link :href="route('admin.shops.edit', shop.id)" class="text-indigo-600 hover:text-indigo-800">Edit</Link>
                                    <button type="button" class="text-red-600 hover:text-red-800" @click="destroy(shop)">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t px-4">
                <Pagination :paginator="shops" />
            </div>
        </Card>
    </AuthenticatedLayout>
</template>
