<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue';

const props = defineProps({
    status: { type: Number, required: true },
});

const content = computed(
    () =>
        ({
            403: { title: 'Access denied', message: "You don't have permission to view this page." },
            404: { title: 'Page not found', message: "The page you're looking for doesn't exist or is no longer available." },
            500: { title: 'Something went wrong', message: 'An unexpected error occurred. Please try again in a moment.' },
            503: { title: 'Be right back', message: "We're doing some maintenance. Please check back shortly." },
        })[props.status] ?? { title: 'Error', message: 'Something went wrong.' },
);
</script>

<template>
    <Head :title="content.title" />

    <StorefrontLayout>
        <section class="mx-auto flex max-w-xl flex-col items-center px-4 py-24 text-center sm:py-32">
            <p class="text-7xl font-black tracking-tight text-emerald-600 sm:text-8xl">{{ status }}</p>
            <h1 class="mt-4 text-2xl font-bold tracking-tight sm:text-3xl">{{ content.title }}</h1>
            <p class="mt-3 text-stone-600">{{ content.message }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <Link href="/" class="rounded-full bg-stone-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-stone-700">Browse shops</Link>
                <Link
                    v-if="status === 403 && $page.props.auth?.user"
                    :href="route('dashboard')"
                    class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-semibold hover:border-stone-900"
                    >Go to my dashboard</Link
                >
            </div>
        </section>
    </StorefrontLayout>
</template>
