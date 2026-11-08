<script setup>
// Labelled on/off switch bound to a boolean v-model.
const model = defineModel({ type: Boolean, required: true });

defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="flex items-start gap-3">
        <button
            :id="id"
            type="button"
            role="switch"
            :aria-checked="model"
            class="relative mt-0.5 inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            :class="model ? 'bg-indigo-600' : 'bg-gray-300'"
            @click="model = !model"
        >
            <span
                aria-hidden="true"
                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition"
                :class="model ? 'translate-x-5' : 'translate-x-0'"
            />
        </button>
        <label :for="id" class="cursor-pointer text-sm">
            <span class="font-medium text-gray-900">{{ label }}</span>
            <span v-if="description" class="block text-gray-500">{{ description }}</span>
        </label>
    </div>
</template>
