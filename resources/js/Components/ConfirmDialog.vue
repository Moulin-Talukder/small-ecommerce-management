<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useConfirm } from '@/composables/useConfirm';

const { state, close } = useConfirm();
</script>

<template>
    <Modal :show="state.show" max-width="md" @close="close(false)">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900">{{ state.title }}</h2>
            <p v-if="state.message" class="mt-2 text-sm text-gray-600">{{ state.message }}</p>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="close(false)">Cancel</SecondaryButton>
                <DangerButton v-if="state.danger" @click="close(true)">{{ state.confirmText }}</DangerButton>
                <PrimaryButton v-else @click="close(true)">{{ state.confirmText }}</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
