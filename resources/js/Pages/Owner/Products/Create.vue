<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ProductFields from '@/Components/Products/ProductFields.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    shops: { type: Array, required: true },
});

const form = useForm({
    name: '',
    base_price: '',
    sku: '',
    description: '',
    thumbnail: null,
    remove_thumbnail: false,
    list_in_shop_id: props.shops.length === 1 ? props.shops[0].id : '',
    stock: 0,
});

function submit() {
    form.transform(({ remove_thumbnail, ...data }) => ({
        ...data,
        list_in_shop_id: data.list_in_shop_id || null,
        stock: data.list_in_shop_id ? data.stock : null,
    })).post(route('owner.products.store'), { forceFormData: true });
}
</script>

<template>
    <Head title="Create product" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Create product</h2>
                <p class="mt-1 text-sm text-gray-500">New products are added to the shared catalog, so other shop owners can sell them too.</p>
            </div>
        </template>

        <Card class="max-w-2xl">
            <form class="space-y-6 p-6" @submit.prevent="submit">
                <ProductFields :form="form" />

                <fieldset v-if="shops.length" class="space-y-4 rounded-lg border border-indigo-100 bg-indigo-50/50 p-4">
                    <legend class="px-1 text-sm font-semibold text-gray-900">Also list it in a shop (optional)</legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="list_in_shop_id" value="Shop" />
                            <select
                                id="list_in_shop_id"
                                v-model="form.list_in_shop_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Don't list it yet</option>
                                <option v-for="shop in shops" :key="shop.id" :value="shop.id">{{ shop.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.list_in_shop_id" />
                        </div>
                        <div v-if="form.list_in_shop_id">
                            <InputLabel for="stock" value="Stock" />
                            <TextInput id="stock" v-model="form.stock" type="number" min="0" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.stock" />
                        </div>
                    </div>
                    <p v-if="form.list_in_shop_id" class="text-xs text-gray-500">The listing uses the catalog price, name and image. You can override them later.</p>
                </fieldset>

                <div class="flex items-center justify-end gap-4 border-t pt-5">
                    <Link :href="route('owner.products.index')" class="text-sm text-gray-600 hover:text-gray-900">Cancel</Link>
                    <PrimaryButton :disabled="form.processing">Create product</PrimaryButton>
                </div>
            </form>
        </Card>
    </AuthenticatedLayout>
</template>
