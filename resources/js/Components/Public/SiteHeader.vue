<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const mobileOpen = ref(false);

const navLinks = [
    { label: 'Instances', href: '/catalog' },
    { label: 'About', href: '/about' },
    { label: 'Launch instance', href: '/launch' },
];
</script>

<template>
    <header class="sticky top-0 z-50 border-b border-hairline bg-canvas/95 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center gap-6 px-5 py-4 sm:px-8">
            <Link href="/" class="flex items-center gap-3" aria-label="Asyncindo home">
                <span class="inline-block h-3 w-3 rotate-45 bg-acid" aria-hidden="true"></span>
                <span class="text-sm font-semibold uppercase tracking-[0.2em] text-zinc-100">
                    asyncindo
                </span>
            </Link>

            <nav class="ml-6 hidden items-center gap-6 md:flex" aria-label="Main navigation">
                <Link
                    v-for="link in navLinks"
                    :key="link.href"
                    :href="link.href"
                    class="text-sm text-zinc-400 transition hover:text-acid"
                >
                    {{ link.label }}
                </Link>
            </nav>

            <div class="ml-auto hidden items-center gap-4 md:flex">
                <Link href="/admin/login" class="text-sm text-zinc-300 transition hover:text-acid">
                    Log in
                </Link>
                <Link
                    href="/launch"
                    class="rounded-md bg-acid px-4 py-2 text-sm font-semibold text-canvas transition hover:brightness-95"
                >
                    Get started
                </Link>
            </div>

            <button
                type="button"
                class="ml-auto inline-flex h-10 w-10 items-center justify-center rounded-md border border-hairline text-zinc-300 transition hover:border-acid hover:text-acid md:hidden"
                :aria-expanded="mobileOpen"
                aria-controls="mobile-menu"
                aria-label="Toggle navigation"
                @click="mobileOpen = !mobileOpen"
            >
                <svg
                    v-if="!mobileOpen"
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
                <svg
                    v-else
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>

        <nav
            v-if="mobileOpen"
            id="mobile-menu"
            class="border-t border-hairline bg-panel px-5 pb-5 pt-2 md:hidden"
            aria-label="Mobile navigation"
        >
            <Link
                v-for="link in navLinks"
                :key="link.href"
                :href="link.href"
                class="block border-b border-hairline py-3 text-sm text-zinc-300 transition hover:text-acid"
                @click="mobileOpen = false"
            >
                {{ link.label }}
            </Link>
            <Link
                href="/admin/login"
                class="block py-3 text-sm text-zinc-300 transition hover:text-acid"
                @click="mobileOpen = false"
            >
                Log in
            </Link>
            <Link
                href="/launch"
                class="mt-3 block rounded-md bg-acid px-4 py-2 text-center text-sm font-semibold text-canvas transition hover:brightness-95"
                @click="mobileOpen = false"
            >
                Get started
            </Link>
        </nav>
    </header>
</template>
