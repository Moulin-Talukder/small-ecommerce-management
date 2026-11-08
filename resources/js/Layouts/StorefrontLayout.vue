<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const page = usePage();
// auth may be absent on error pages rendered outside the web middleware.
const user = computed(() => page.props.auth?.user ?? null);
const appName = computed(() => page.props.appName ?? 'Small E-commerce');
</script>

<template>
    <div class="flex min-h-screen flex-col bg-stone-50 text-stone-900">
        <header class="sticky top-0 z-30 border-b border-stone-200/80 bg-white/85 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <Link href="/" class="flex items-center gap-2">
                    <ApplicationLogo class="h-8 w-8 fill-emerald-600 text-emerald-700" />
                    <span class="text-base font-bold tracking-tight sm:text-lg">{{ appName }}</span>
                </Link>

                <nav class="flex items-center gap-1 text-sm font-medium sm:gap-3">
                    <Link href="/#shops" class="rounded-full px-3 py-2 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900">Shops</Link>
                    <Link
                        v-if="user"
                        :href="route('dashboard')"
                        class="rounded-full bg-stone-900 px-4 py-2 text-white transition hover:bg-stone-700"
                    >
                        Dashboard
                    </Link>
                    <Link v-else :href="route('login')" class="rounded-full bg-stone-900 px-4 py-2 text-white transition hover:bg-stone-700">Log in</Link>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-stone-200 bg-white">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-stone-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p>© {{ new Date().getFullYear() }} {{ appName }} · Independent shops, one marketplace.</p>
                <p>Browsing only. No cart or checkout in this demo.</p>
            </div>
        </footer>
    </div>
</template>
