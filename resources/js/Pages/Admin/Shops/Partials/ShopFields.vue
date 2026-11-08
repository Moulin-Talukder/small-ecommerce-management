<script setup>
import ImageInput from '@/Components/ImageInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ToggleField from '@/Components/ToggleField.vue';

defineProps({
    form: { type: Object, required: true },
    owners: { type: Array, required: true },
    currentLogoUrl: { type: String, default: null },
});
</script>

<template>
    <div class="space-y-5">
        <div>
            <InputLabel for="name" value="Shop name" />
            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="owner_id" value="Shop owner" />
            <select
                id="owner_id"
                v-model="form.owner_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                <option :value="null" disabled>Select a shop owner…</option>
                <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.label }}</option>
            </select>
            <InputError class="mt-2" :message="form.errors.owner_id" />
            <p v-if="owners.length === 0" class="mt-2 text-sm text-amber-700">Create a shop owner account first.</p>
        </div>

        <div>
            <InputLabel for="description" value="Description" />
            <textarea
                id="description"
                v-model="form.description"
                rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            <InputError class="mt-2" :message="form.errors.description" />
        </div>

        <div>
            <InputLabel for="logo" value="Logo" />
            <div class="mt-2">
                <ImageInput id="logo" v-model="form.logo" v-model:remove="form.remove_logo" :current-url="currentLogoUrl" />
            </div>
            <InputError class="mt-2" :message="form.errors.logo" />
        </div>

        <div>
            <ToggleField id="is_active" v-model="form.is_active" label="Active" description="Inactive shops are hidden from the storefront and their pages return 404." />
            <InputError class="mt-2" :message="form.errors.is_active" />
        </div>
    </div>
</template>
