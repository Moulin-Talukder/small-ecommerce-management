<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

/**
 * File input with preview.
 *   v-model         → the selected File (or null)
 *   v-model:remove  → true when the user asks to remove the current image
 *   current-url     → URL of the image already saved on the model
 *   fallback-url    → shown (dimmed) when there is no image, e.g. the catalog thumbnail
 */
const props = defineProps({
    modelValue: { type: [File, null], default: null },
    remove: { type: Boolean, default: false },
    currentUrl: { type: String, default: null },
    fallbackUrl: { type: String, default: null },
    fallbackLabel: { type: String, default: 'Using catalog image' },
    removable: { type: Boolean, default: true },
    id: { type: String, default: 'image' },
});

const emit = defineEmits(['update:modelValue', 'update:remove']);

const input = ref(null);
const objectUrl = ref(null);

watch(
    () => props.modelValue,
    (file) => {
        if (objectUrl.value) URL.revokeObjectURL(objectUrl.value);
        objectUrl.value = file ? URL.createObjectURL(file) : null;
        if (!file && input.value) input.value.value = '';
    },
);

onBeforeUnmount(() => objectUrl.value && URL.revokeObjectURL(objectUrl.value));

const preview = computed(() => {
    if (objectUrl.value) return { url: objectUrl.value, note: 'New image (not saved yet)' };
    if (props.currentUrl && !props.remove) return { url: props.currentUrl, note: null };
    if (props.fallbackUrl) return { url: props.fallbackUrl, note: props.fallbackLabel, dim: true };
    return null;
});

function onChange(event) {
    emit('update:modelValue', event.target.files[0] ?? null);
    emit('update:remove', false);
}

function clearSelection() {
    emit('update:modelValue', null);
}

function removeCurrent() {
    emit('update:modelValue', null);
    emit('update:remove', true);
}
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
        <div class="flex h-32 w-32 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
            <img
                v-if="preview"
                :src="preview.url"
                alt="Image preview"
                class="h-full w-full object-cover"
                :class="{ 'opacity-50': preview.dim }"
            />
            <span v-else class="px-2 text-center text-xs text-gray-400">No image</span>
        </div>

        <div class="flex flex-col gap-2 text-sm">
            <input
                :id="id"
                ref="input"
                type="file"
                accept="image/jpeg,image/png,image/webp"
                class="block w-full text-sm text-gray-700 file:me-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100"
                @change="onChange"
            />
            <p class="text-xs text-gray-500">JPG, PNG or WebP, up to 2 MB.</p>
            <p v-if="preview?.note" class="text-xs text-gray-500">{{ preview.note }}</p>

            <div class="flex gap-3">
                <button v-if="modelValue" type="button" class="text-xs font-medium text-gray-600 underline hover:text-gray-900" @click="clearSelection">
                    Cancel new image
                </button>
                <button
                    v-if="removable && currentUrl && !remove && !modelValue"
                    type="button"
                    class="text-xs font-medium text-red-600 underline hover:text-red-800"
                    @click="removeCurrent"
                >
                    Remove image
                </button>
                <button v-if="remove" type="button" class="text-xs font-medium text-gray-600 underline hover:text-gray-900" @click="emit('update:remove', false)">
                    Undo remove
                </button>
            </div>
        </div>
    </div>
</template>
