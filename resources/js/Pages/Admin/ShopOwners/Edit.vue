<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Card from '@/Components/Card.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import TextInput from '@/Components/TextInput.vue';
import { useConfirm } from '@/composables/useConfirm';
import OwnerFields from './Partials/OwnerFields.vue';

const props = defineProps({
    owner: { type: Object, required: true },
});

const { confirmDelete } = useConfirm();

const form = useForm({
    name: props.owner.name,
    email: props.owner.email,
    is_active: props.owner.is_active,
});

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.put(route('admin.shop-owners.update', props.owner.id));
}

function resetPassword() {
    passwordForm.put(route('admin.shop-owners.password', props.owner.id), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

function destroy() {
    confirmDelete(route('admin.shop-owners.destroy', props.owner.id), {
        title: `Delete ${props.owner.name}?`,
        message: 'The account will be removed permanently. Products they created stay in the catalog.',
    });
}
</script>

<template>
    <Head :title="`Edit ${owner.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit shop owner</h2>
            <Link :href="route('admin.shop-owners.index')" class="text-sm text-gray-600 hover:text-gray-900">← Back to shop owners</Link>
        </template>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card>
                    <form class="space-y-6 p-6" @submit.prevent="submit">
                        <h3 class="text-base font-semibold text-gray-900">Account details</h3>
                        <OwnerFields :form="form" />
                        <div class="flex justify-end border-t pt-5">
                            <PrimaryButton :disabled="form.processing">Save changes</PrimaryButton>
                        </div>
                    </form>
                </Card>

                <Card>
                    <form class="space-y-5 p-6" @submit.prevent="resetPassword">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">Reset password</h3>
                            <p class="mt-1 text-sm text-gray-500">Set a new password and share it with the shop owner.</p>
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <InputLabel for="new_password" value="New password" />
                                <TextInput id="new_password" v-model="passwordForm.password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                                <InputError class="mt-2" :message="passwordForm.errors.password" />
                            </div>
                            <div>
                                <InputLabel for="new_password_confirmation" value="Confirm new password" />
                                <TextInput id="new_password_confirmation" v-model="passwordForm.password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <PrimaryButton :disabled="passwordForm.processing">Reset password</PrimaryButton>
                        </div>
                    </form>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-gray-900">Shops</h3>
                        <ul v-if="owner.shops.length" class="mt-3 divide-y text-sm">
                            <li v-for="shop in owner.shops" :key="shop.id" class="flex items-center justify-between py-2">
                                <Link
                                    v-if="route().has('admin.shops.edit')"
                                    :href="route('admin.shops.edit', shop.id)"
                                    class="text-indigo-600 hover:text-indigo-800"
                                    >{{ shop.name }}</Link
                                >
                                <span v-else>{{ shop.name }}</span>
                                <StatusBadge :active="shop.is_active" />
                            </li>
                        </ul>
                        <p v-else class="mt-2 text-sm text-gray-500">No shops assigned.</p>
                    </div>
                </Card>

                <Card>
                    <div class="space-y-3 p-6">
                        <h3 class="text-base font-semibold text-red-700">Delete account</h3>
                        <p class="text-sm text-gray-600">
                            Only possible when the owner has no shops. Products they created stay in the shared catalog.
                        </p>
                        <DangerButton type="button" @click="destroy">Delete shop owner</DangerButton>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
