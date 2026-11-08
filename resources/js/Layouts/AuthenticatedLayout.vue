<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ConfirmDialog from '@/Components/ConfirmDialog.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import FlashMessage from '@/Components/FlashMessage.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);
const showingNavigationDropdown = ref(false);

// `match` is a route-name pattern used to highlight the active section.
const menus = {
    admin: [
        { label: 'Dashboard', route: 'admin.dashboard', match: 'admin.dashboard' },
        { label: 'Shops', route: 'admin.shops.index', match: 'admin.shops.*' },
        { label: 'Catalog', route: 'admin.products.index', match: 'admin.products.*' },
        { label: 'Shop owners', route: 'admin.shop-owners.index', match: 'admin.shop-owners.*' },
    ],
    shop_owner: [
        { label: 'My shops', route: 'owner.dashboard', match: ['owner.dashboard', 'owner.listings.*'] },
        { label: 'Catalog', route: 'owner.catalog.index', match: 'owner.catalog.*' },
        { label: 'My products', route: 'owner.products.index', match: 'owner.products.*' },
    ],
};

// Only show links whose routes exist, so the menu grows with the features.
const navigation = computed(() => (menus[user.value?.role] ?? []).filter((item) => route().has(item.route)));
const isActive = (item) => [].concat(item.match).some((pattern) => route().current(pattern));
const roleLabel = computed(() => (user.value?.role === 'admin' ? 'Admin' : 'Shop owner'));
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <FlashMessage />
        <ConfirmDialog />

        <nav class="border-b border-gray-100 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <div class="flex shrink-0 items-center gap-2">
                            <Link :href="route('dashboard')" class="flex items-center gap-2">
                                <ApplicationLogo class="block h-9 w-auto fill-current text-indigo-600" />
                                <span class="hidden text-sm font-semibold text-gray-900 lg:inline">{{ $page.props.appName }}</span>
                            </Link>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink
                                v-for="item in navigation"
                                :key="item.route"
                                :href="route(item.route)"
                                :active="isActive(item)"
                            >
                                {{ item.label }}
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden sm:ms-6 sm:flex sm:items-center sm:gap-4">
                        <a href="/" target="_blank" class="text-sm text-gray-500 hover:text-gray-800">View storefront ↗</a>

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-600 transition hover:text-gray-800 focus:outline-none"
                                >
                                    <span>{{ user.name }}</span>
                                    <span class="ms-2 rounded bg-indigo-50 px-1.5 py-0.5 text-xs font-medium text-indigo-700">{{ roleLabel }}</span>
                                    <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
                            :aria-expanded="showingNavigationDropdown"
                            aria-label="Toggle navigation"
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path v-if="!showingNavigationDropdown" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-show="showingNavigationDropdown" class="sm:hidden">
                <div class="space-y-1 pb-3 pt-2">
                    <ResponsiveNavLink
                        v-for="item in navigation"
                        :key="item.route"
                        :href="route(item.route)"
                        :active="isActive(item)"
                    >
                        {{ item.label }}
                    </ResponsiveNavLink>
                    <ResponsiveNavLink href="/">View storefront</ResponsiveNavLink>
                </div>

                <div class="border-t border-gray-200 pb-1 pt-4">
                    <div class="px-4">
                        <div class="text-base font-medium text-gray-800">{{ user.name }} · {{ roleLabel }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ user.email }}</div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Profile</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Log Out</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <header v-if="$slots.header" class="bg-white shadow">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-6 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>
