<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ListingFields from '@/Components/Listings/ListingFields.vue';

const props = defineProps({
    product: { type: Object, default: null }, // null = closed
    shop: { type: Object, default: null },
});
const emit = defineEmits(['close']);

const form = useForm({
    product_id: null,
    price: '',
    stock: 0,
    custom_name: '',
    custom_description: '',
    custom_thumbnail: null,
    remove_custom_thumbnail: false,
    is_active: true,
});

// Reset the form for every product the modal opens with.
watch(
    () => props.product,
    (product) => {
        if (!product) return;
        form.reset();
        form.clearErrors();
        form.product_id = product.id;
        form.price = product.base_price; // prefilled with the catalog price
    },
);

function submit() {
    form.transform(({ remove_custom_thumbnail, ...data }) => data).post(route('owner.listings.store', props.shop.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Modal :show="!!product" max-width="2xl" @close="emit('close')">
        <form v-if="product && shop" class="p-6" @submit.prevent="submit">
            <h2 class="text-lg font-semibold text-gray-900">Add “{{ product.name }}” to {{ shop.name }}</h2>
            <p class="mt-1 text-sm text-gray-500">Set the price and stock for this shop. Overrides are optional and only affect this shop.</p>

            <InputError class="mt-4" :message="form.errors.product_id" />

            <div class="mt-5 max-h-[60vh] overflow-y-auto pe-1">
                <ListingFields :form="form" :product="product" id-prefix="add" />
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t pt-4">
                <SecondaryButton @click="emit('close')">Cancel</SecondaryButton>
                <PrimaryButton :disabled="form.processing">Add to shop</PrimaryButton>
            </div>
        </form>
    </Modal>
</template>
