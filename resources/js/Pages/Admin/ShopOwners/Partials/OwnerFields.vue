<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import ToggleField from '@/Components/ToggleField.vue';

// `form` is an Inertia useForm() object owned by the parent page.
defineProps({
    form: { type: Object, required: true },
    withPassword: { type: Boolean, default: false },
});
</script>

<template>
    <div class="space-y-5">
        <div>
            <InputLabel for="name" value="Name" />
            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus autocomplete="off" />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="email" value="Email" />
            <TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full" required autocomplete="off" />
            <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <template v-if="withPassword">
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <InputLabel for="password" value="Password" />
                    <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
                <div>
                    <InputLabel for="password_confirmation" value="Confirm password" />
                    <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                </div>
            </div>
            <p class="-mt-2 text-xs text-gray-500">At least 8 characters.</p>
        </template>

        <div>
            <ToggleField id="is_active" v-model="form.is_active" label="Active" description="Inactive owners cannot log in and their shops are hidden from the storefront." />
            <InputError class="mt-2" :message="form.errors.is_active" />
        </div>
    </div>
</template>
