<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import ShopFields from './Partials/ShopFields.vue';

const props = defineProps({
    shop: { type: Object, required: true },
    owners: { type: Array, required: true },
});

const { confirmDelete } = useConfirm();

const form = useForm({
    name: props.shop.name,
    owner_id: props.shop.owner_id,
    description: props.shop.description ?? '',
    logo: null,
    remove_logo: false,
    is_active: props.shop.is_active,
});

function submit() {
    // Multipart forms can't use PUT directly: spoof the method.
    form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.shops.update', props.shop.id), {
        forceFormData: true,
    });
}

function destroy() {
    confirmDelete(route('admin.shops.destroy', props.shop.id), {
        title: `Delete ${props.shop.name}?`,
        message: `This also removes its ${props.shop.shop_products_count} product listing(s). Catalog products are not affected.`,
    });
}
</script>

<template>
    <Head :title="`Edit ${shop.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit shop</h2>
            <Link :href="route('admin.shops.index')" class="text-sm text-gray-600 hover:text-gray-900">← Back to shops</Link>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <form class="space-y-6 p-6" @submit.prevent="submit">
                    <p class="text-sm text-gray-500">
                        Public URL: <a :href="`/shops/${shop.slug}`" target="_blank" class="font-mono text-indigo-600 hover:underline">/shops/{{ shop.slug }}</a>
                    </p>

                    <ShopFields :form="form" :owners="owners" :current-logo-url="shop.logo_url" />

                    <div class="flex justify-end border-t pt-5">
                        <PrimaryButton :disabled="form.processing">Save changes</PrimaryButton>
                    </div>
                </form>
            </Card>

            <Card class="h-fit">
                <div class="space-y-3 p-6">
                    <h3 class="text-base font-semibold text-red-700">Delete shop</h3>
                    <p class="text-sm text-gray-600">Removes the shop and its {{ shop.shop_products_count }} listing(s). Catalog products stay.</p>
                    <DangerButton type="button" @click="destroy">Delete shop</DangerButton>
                </div>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
