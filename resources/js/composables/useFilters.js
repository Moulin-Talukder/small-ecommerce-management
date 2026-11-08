import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';

/**
 * Keeps list filters in the query string. Any change reloads the current
 * page via Inertia (keeping scroll & component state) and resets to page 1.
 *
 *   const filters = useFilters(props.filters, route('admin.shops.index'));
 */
export function useFilters(initial, url) {
    const filters = reactive({ ...initial });

    watch(
        () => ({ ...filters }),
        (value) => {
            const query = Object.fromEntries(Object.entries(value).filter(([, v]) => v !== '' && v !== null && v !== undefined));
            router.get(url, query, { preserveState: true, preserveScroll: true, replace: true });
        },
    );

    return filters;
}
