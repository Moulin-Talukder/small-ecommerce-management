<script setup>
import ImageInput from '@/Components/ImageInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ToggleField from '@/Components/ToggleField.vue';
import { formatMoney } from '@/utils/money';

/**
 * Per-shop override fields. `product` holds the catalog values; they are shown
 * as placeholders/hints so the owner sees what an empty field falls back to.
 */
defineProps({
    form: { type: Object, required: true },
    product: { type: Object, required: true },
    currentThumbnailUrl: { type: String, default: null },
    idPrefix: { type: String, default: 'listing' },
});
</script>

<template>
    <div class="space-y-5">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <InputLabel :for="`${idPrefix}_price`" value="Price in this shop (USD)" />
                <TextInput
                    :id="`${idPrefix}_price`"
                    v-model="form.price"
                    type="number"
                    min="0"
                    step="0.01"
                    class="mt-1 block w-full"
                    :placeholder="product.base_price"
                />
                <p class="mt-1 text-xs text-gray-500">
                    Catalog price: <span class="font-medium">{{ formatMoney(product.base_price) }}</span>. Leave empty to always follow it.
                </p>
                <InputError class="mt-2" :message="form.errors.price" />
            </div>
            <div>
                <InputLabel :for="`${idPrefix}_stock`" value="Stock" />
                <TextInput :id="`${idPrefix}_stock`" v-model="form.stock" type="number" min="0" step="1" class="mt-1 block w-full" required />
                <InputError class="mt-2" :message="form.errors.stock" />
            </div>
        </div>

        <div>
            <InputLabel :for="`${idPrefix}_custom_name`" value="Name in this shop" />
            <TextInput :id="`${idPrefix}_custom_name`" v-model="form.custom_name" type="text" class="mt-1 block w-full" :placeholder="product.name" />
            <p class="mt-1 text-xs text-gray-500">Leave empty to use the catalog name: “{{ product.name }}”.</p>
            <InputError class="mt-2" :message="form.errors.custom_name" />
        </div>

        <div>
            <InputLabel :for="`${idPrefix}_custom_description`" value="Description in this shop" />
            <textarea
                :id="`${idPrefix}_custom_description`"
                v-model="form.custom_description"
                rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                :placeholder="product.description || 'No catalog description'"
            />
            <p class="mt-1 text-xs text-gray-500">Leave empty to use the catalog description (shown greyed out above).</p>
            <InputError class="mt-2" :message="form.errors.custom_description" />
        </div>

        <div>
            <InputLabel :for="`${idPrefix}_custom_thumbnail`" value="Thumbnail in this shop" />
            <div class="mt-2">
                <ImageInput
                    :id="`${idPrefix}_custom_thumbnail`"
                    v-model="form.custom_thumbnail"
                    v-model:remove="form.remove_custom_thumbnail"
                    :current-url="currentThumbnailUrl"
                    :fallback-url="product.thumbnail_url"
                    fallback-label="Using the catalog image"
                />
            </div>
            <InputError class="mt-2" :message="form.errors.custom_thumbnail" />
        </div>

        <div>
            <ToggleField :id="`${idPrefix}_is_active`" v-model="form.is_active" label="Visible in the shop" description="Inactive listings are hidden from the storefront." />
            <InputError class="mt-2" :message="form.errors.is_active" />
        </div>
    </div>
</template>
