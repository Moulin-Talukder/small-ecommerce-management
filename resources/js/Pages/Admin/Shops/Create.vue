<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ShopFields from './Partials/ShopFields.vue';

defineProps({
    owners: { type: Array, required: true },
});

const form = useForm({
    name: '',
    owner_id: null,
    description: '',
    logo: null,
    remove_logo: false,
    is_active: true,
});

function submit() {
    form.post(route('admin.shops.store'), { forceFormData: true });
}
</script>

<template>
    <Head title="New shop" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">New shop</h2>
        </template>

        <Card class="max-w-2xl">
            <form class="space-y-6 p-6" @submit.prevent="submit">
                <ShopFields :form="form" :owners="owners" />

                <div class="flex items-center justify-end gap-4 border-t pt-5">
                    <Link :href="route('admin.shops.index')" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                    <PrimaryButton :disabled="form.processing">Create shop</PrimaryButton>
                </div>
            </form>
        </Card>
    </AuthenticatedLayout>
</template>
