<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProductFields from '@/Components/Products/ProductFields.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { formatMoney } from '@/utils/money';

const props = defineProps({
    product: { type: Object, required: true },
    listings: { type: Array, required: true },
});

const { confirmDelete } = useConfirm();

const form = useForm({
    name: props.product.name,
    base_price: props.product.base_price,
    sku: props.product.sku ?? '',
    description: props.product.description ?? '',
    thumbnail: null,
    remove_thumbnail: false,
});

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.products.update', props.product.id), {
        forceFormData: true,
    });
}

function destroy() {
    confirmDelete(route('admin.products.destroy', props.product.id), {
        title: `Delete ${props.product.name}?`,
        message: props.listings.length
            ? `It is listed in ${props.listings.length} shop(s) and will disappear from all of them.`
            : 'It is not listed in any shop.',
    });
}
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit catalog product</h2>
            <Link :href="route('admin.products.index')" class="text-sm text-gray-600 hover:text-gray-900">← Back to catalog</Link>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <form class="space-y-6 p-6" @submit.prevent="submit">
                    <p class="text-sm text-gray-500">
                        Created by <span class="font-medium text-gray-700">{{ product.creator?.name ?? 'a deleted user' }}</span>.
                        Changes apply to every shop, except fields a shop has overridden.
                    </p>
                    <ProductFields :form="form" :current-thumbnail-url="product.thumbnail_url" />
                    <div class="flex justify-end border-t pt-5">
                        <PrimaryButton :disabled="form.processing">Save changes</PrimaryButton>
                    </div>
                </form>
            </Card>

            <div class="space-y-6">
                <Card>
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-gray-900">Listed in {{ listings.length }} shop(s)</h3>
                        <ul v-if="listings.length" class="mt-3 divide-y text-sm">
                            <li v-for="listing in listings" :key="listing.id" class="py-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-medium text-gray-900">{{ listing.shop.name }}</span>
                                    <StatusBadge :active="listing.is_active && listing.shop.is_active" active-label="Visible" inactive-label="Hidden" />
                                </div>
                                <div class="mt-0.5 text-xs text-gray-500">
                                    {{ listing.price !== null ? formatMoney(listing.price) : 'Catalog price' }}
                                    · stock {{ listing.stock }}
                                    <template v-if="listing.custom_name"> · “{{ listing.custom_name }}”</template>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="mt-2 text-sm text-gray-500">Not listed in any shop, so it is not visible publicly.</p>
                    </div>
                </Card>

                <Card>
                    <div class="space-y-3 p-6">
                        <h3 class="text-base font-semibold text-red-700">Delete product</h3>
                        <p class="text-sm text-gray-600">Soft-deletes the product. It disappears from the catalog and from every shop.</p>
                        <DangerButton type="button" @click="destroy">Delete product</DangerButton>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
