<script setup>
import ImageInput from '@/Components/ImageInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

// Catalog product fields; used by admin and shop-owner product forms.
defineProps({
    form: { type: Object, required: true },
    currentThumbnailUrl: { type: String, default: null },
});
</script>

<template>
    <div class="space-y-5">
        <div>
            <InputLabel for="name" value="Product name" />
            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <InputLabel for="base_price" value="Base price (USD)" />
                <TextInput id="base_price" v-model="form.base_price" type="number" min="0" step="0.01" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.base_price" />
            </div>
            <div>
                <InputLabel for="sku" value="SKU (optional)" />
                <TextInput id="sku" v-model="form.sku" type="text" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.sku" />
            </div>
        </div>

        <div>
            <InputLabel for="description" value="Description" />
            <textarea
                id="description"
                v-model="form.description"
                rows="5"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div>
            <InputLabel for="thumbnail" value="Thumbnail" />
            <div class="mt-2">
                <ImageInput id="thumbnail" v-model="form.thumbnail" v-model:remove="form.remove_thumbnail" :current-url="currentThumbnailUrl" />
            </div>
            <InputError class="mt-2" :message="form.errors.thumbnail" />
        </div>
    </div>
</template>
