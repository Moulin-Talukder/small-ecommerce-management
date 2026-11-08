<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProductFields from '@/Components/Products/ProductFields.vue';

const form = useForm({
    name: '',
    base_price: '',
    sku: '',
    description: '',
    thumbnail: null,
    remove_thumbnail: false,
});

function submit() {
    form.post(route('admin.products.store'), { forceFormData: true });
}
</script>

<template>
    <Head title="New catalog product" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">New catalog product</h2>
        </template>

        <Card class="max-w-2xl">
            <form class="space-y-6 p-6" @submit.prevent="submit">
                <ProductFields :form="form" />
                <div class="flex items-center justify-end gap-4 border-t pt-5">
                    <Link :href="route('admin.products.index')" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                    <PrimaryButton :disabled="form.processing">Add to catalog</PrimaryButton>
                </div>
            </form>
        </Card>
    </AuthenticatedLayout>
</template>
