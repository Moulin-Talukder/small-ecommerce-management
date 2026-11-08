<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import EmptyState from '@/Components/EmptyState.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useFilters } from '@/composables/useFilters';

const props = defineProps({
    owners: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const filters = useFilters(props.filters, route('admin.shop-owners.index'));
const { confirm, confirmDelete } = useConfirm();

async function toggleActive(owner) {
    if (owner.is_active) {
        const ok = await confirm({
            title: `Deactivate ${owner.name}?`,
            message: 'They will be logged out, cannot log in, and their shops are hidden from the storefront.',
            confirmText: 'Deactivate',
        });
        if (!ok) return;
    }

    router.patch(route('admin.shop-owners.toggle-active', owner.id), {}, { preserveScroll: true });
}

function destroy(owner) {
    confirmDelete(route('admin.shop-owners.destroy', owner.id), {
        title: `Delete ${owner.name}?`,
        message: 'The account will be removed permanently. Products they created stay in the catalog.',
    });
}
</script>

<template>
    <Head title="Shop owners" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Shop owners</h2>
            <Link
                :href="route('admin.shop-owners.create')"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
            >
                + New shop owner
            </Link>
        </template>

        <Card>
            <div class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center">
                <SearchInput v-model="filters.search" placeholder="Search name or email…" />
                <select v-model="filters.status" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:w-44">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <EmptyState v-if="owners.data.length === 0" title="No shop owners found" message="Try a different search or create a new shop owner." />

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="hidden px-4 py-3 md:table-cell">Shops</th>
                            <th class="hidden px-4 py-3 md:table-cell">Products created</th>
                            <th class="hidden px-4 py-3 sm:table-cell">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="owner in owners.data" :key="owner.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ owner.name }}</div>
                                <div class="break-all text-gray-500">{{ owner.email }}</div>
                                <StatusBadge :active="owner.is_active" class="mt-1 sm:hidden" />
                            </td>
                            <td class="hidden px-4 py-3 text-gray-700 md:table-cell">{{ owner.shops_count }}</td>
                            <td class="hidden px-4 py-3 text-gray-700 md:table-cell">{{ owner.created_products_count }}</td>
                            <td class="hidden px-4 py-3 sm:table-cell"><StatusBadge :active="owner.is_active" /></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-x-4 gap-y-1 font-medium">
                                    <Link :href="route('admin.shop-owners.edit', owner.id)" class="text-indigo-600 hover:text-indigo-800">Edit</Link>
                                    <button type="button" class="text-gray-600 hover:text-gray-900" @click="toggleActive(owner)">
                                        {{ owner.is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                    <button type="button" class="text-red-600 hover:text-red-800" @click="destroy(owner)">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="border-t px-4">
                <Pagination :paginator="owners" />
            </div>
        </Card>
    </AuthenticatedLayout>
</template>
