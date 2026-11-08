import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

// Single, app-wide dialog state rendered by <ConfirmDialog /> in the layouts.
const state = reactive({
    show: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    danger: true,
    resolve: null,
});

function close(result) {
    state.show = false;
    state.resolve?.(result);
    state.resolve = null;
}

/**
 * Promise-based confirm dialog:
 *   if (await confirm({ title: 'Delete shop?', message: '...' })) { ... }
 */
function confirm({ title = 'Are you sure?', message = '', confirmText = 'Confirm', danger = true } = {}) {
    // Resolve any dialog that is still open before reusing the state.
    state.resolve?.(false);

    Object.assign(state, { show: true, title, message, confirmText, danger });

    return new Promise((resolve) => {
        state.resolve = resolve;
    });
}

/** Confirm, then send an Inertia DELETE request. */
async function confirmDelete(url, { title = 'Delete this item?', message = 'This action cannot be undone.', confirmText = 'Delete' } = {}) {
    if (await confirm({ title, message, confirmText, danger: true })) {
        router.delete(url, { preserveScroll: true });
    }
}

export function useConfirm() {
    return { state, confirm, confirmDelete, close };
}
