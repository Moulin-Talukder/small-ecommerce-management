<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ListingFields from '@/Components/Listings/ListingFields.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProductThumb from '@/Components/Products/ProductThumb.vue';
import { useConfirm } from '@/composables/useConfirm';
import { formatMoney } from '@/utils/money';

const props = defineProps({
    shop: { type: Object, required: true },
    listing: { type: Object, required: true },
    product: { type: Object, required: true },
});

const { confirmDelete } = useConfirm();

const form = useForm({
    price: props.listing.price ?? '',
    stock: props.listing.stock,
    custom_name: props.listing.custom_name ?? '',
    custom_description: props.listing.custom_description ?? '',
    custom_thumbnail: null,
    remove_custom_thumbnail: false,
    is_active: props.listing.is_active,
});

function submit() {
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('owner.listings.update', [props.shop.id, props.listing.id]), {
        forceFormData: true,
    });
}

function remove() {
    confirmDelete(route('owner.listings.destroy', [props.shop.id, props.listing.id]), {
        title: `Remove from ${props.shop.name}?`,
        message: 'The listing and its shop-specific settings are deleted. The product stays in the shared catalog.',
        confirmText: 'Remove',
    });
}
</script>

<template>
    <Head :title="`Edit listing – ${product.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit listing in {{ shop.name }}</h2>
                <p class="mt-1 text-sm text-gray-500">These settings only affect {{ shop.name }}. Empty fields use the catalog value.</p>
            </div>
            <Link :href="route('owner.listings.index', shop.id)" class="text-sm text-gray-600 hover:text-gray-900">← Back to {{ shop.name }}</Link>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <form class="space-y-6 p-6" @submit.prevent="submit">
                    <ListingFields :form="form" :product="product" :current-thumbnail-url="listing.custom_thumbnail_url" id-prefix="edit" />
                    <div class="flex justify-end border-t pt-5">
                        <PrimaryButton :disabled="form.processing">Save listing</PrimaryButton>
                    </div>
                </form>
            </Card>

            <div class="space-y-6">
                <Card>
                    <div class="p-6 text-sm">
                        <h3 class="text-base font-semibold text-gray-900">Catalog product</h3>
                        <div class="mt-3 flex gap-3">
                            <ProductThumb :url="product.thumbnail_url" :name="product.name" size="h-16 w-16" />
                            <div>
                                <p class="font-medium text-gray-900">{{ product.name }}</p>
                                <p class="text-gray-500">{{ formatMoney(product.base_price) }} · <span class="font-mono">{{ product.sku ?? 'No SKU' }}</span></p>
                            </div>
                        </div>
                        <p class="mt-3 text-gray-600">If the catalog product changes, fields you haven't overridden update automatically.</p>
                    </div>
                </Card>

                <Card>
                    <div class="space-y-3 p-6">
                        <h3 class="text-base font-semibold text-red-700">Remove from shop</h3>
                        <p class="text-sm text-gray-600">The product stays in the shared catalog.</p>
                        <DangerButton type="button" @click="remove">Remove from {{ shop.name }}</DangerButton>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
