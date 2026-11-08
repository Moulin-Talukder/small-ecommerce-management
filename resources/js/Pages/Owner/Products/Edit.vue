<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProductFields from '@/Components/Products/ProductFields.vue';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps({
    product: { type: Object, required: true },
    ownShops: { type: Array, required: true },
    otherShopsCount: { type: Number, required: true },
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
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('owner.products.update', props.product.id), {
        forceFormData: true,
    });
}

function destroy() {
    confirmDelete(route('owner.products.destroy', props.product.id), {
        title: `Delete ${props.product.name}?`,
        message: 'It will be removed from the shared catalog and from your shops.',
    });
}
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit product</h2>
            <Link :href="route('owner.products.index')" class="text-sm text-gray-600 hover:text-gray-900">← Back to my products</Link>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <form class="space-y-6 p-6" @submit.prevent="submit">
                    <p class="rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                        This is a shared catalog product. Changes apply to every shop that lists it, except fields a shop has overridden.
                    </p>
                    <ProductFields :form="form" :current-thumbnail-url="product.thumbnail_url" />
                    <div class="flex justify-end border-t pt-5">
                        <PrimaryButton :disabled="form.processing">Save changes</PrimaryButton>
                    </div>
                </form>
            </Card>

            <div class="space-y-6">
                <Card>
                    <div class="p-6 text-sm">
                        <h3 class="text-base font-semibold text-gray-900">Where it's listed</h3>
                        <p class="mt-2 text-gray-600">
                            Your shops: <span class="font-medium text-gray-900">{{ ownShops.length ? ownShops.join(', ') : 'none' }}</span>
                        </p>
                        <p class="mt-1 text-gray-600">
                            Other owners' shops: <span class="font-medium text-gray-900">{{ otherShopsCount }}</span>
                        </p>
                    </div>
                </Card>

                <Card>
                    <div class="space-y-3 p-6">
                        <h3 class="text-base font-semibold text-red-700">Delete product</h3>
                        <p v-if="otherShopsCount" class="text-sm text-gray-600">
                            This product is listed in {{ otherShopsCount }} other shop(s), so it can't be deleted while they sell it.
                        </p>
                        <p v-else class="text-sm text-gray-600">Removes it from the shared catalog and from your shops.</p>
                        <DangerButton type="button" :disabled="otherShopsCount > 0" :class="{ 'cursor-not-allowed opacity-50': otherShopsCount > 0 }" @click="destroy">
                            Delete product
                        </DangerButton>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
