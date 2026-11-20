<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const userName = computed(() => page.props.auth?.user?.name ?? 'Admin');

const links = [
    { label: 'Dashboard', href: '/admin' },
    { label: 'Instances', href: '/admin/instances' },
];

function logout() {
    router.post('/admin/logout');
}
</script>

<template>
    <div class="min-h-screen bg-canvas text-zinc-100 md:flex">
        <!-- Sidebar on desktop, top bar on mobile -->
        <aside
            class="flex shrink-0 flex-col justify-between border-b border-hairline bg-panel md:min-h-screen md:w-64 md:border-b-0 md:border-r"
        >
            <div>
                <div class="flex items-center gap-3 px-5 py-4 md:px-6 md:py-6">
                    <span class="inline-block h-3 w-3 rotate-45 bg-acid" aria-hidden="true"></span>
                    <span class="text-sm font-semibold uppercase tracking-[0.2em] text-zinc-100">
                        asyncindo
                    </span>
                    <span
                        class="ml-auto rounded border border-hairline px-2 py-0.5 text-[10px] uppercase tracking-widest text-zinc-400 md:ml-0"
                    >
                        admin
                    </span>
                </div>

                <nav class="flex gap-1 overflow-x-auto px-3 pb-3 md:flex-col md:overflow-visible md:px-3 md:pb-0">
                    <Link
                        v-for="link in links"
                        :key="link.href"
                        :href="link.href"
                        class="whitespace-nowrap rounded-md px-3 py-2 text-sm text-zinc-400 transition hover:bg-canvas hover:text-zinc-100"
                        :class="{ 'bg-canvas text-acid hover:text-acid': $page.url === link.href }"
                    >
                        {{ link.label }}
                    </Link>
                </nav>
            </div>

            <div class="hidden border-t border-hairline px-5 py-4 md:block">
                <p class="truncate text-xs text-zinc-500">{{ userName }}</p>
                <button
                    type="button"
                    class="mt-2 text-sm font-medium text-zinc-300 transition hover:text-acid"
                    @click="logout"
                >
                    Log out
                </button>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-hairline px-5 py-3 md:hidden">
                <span class="truncate text-xs text-zinc-500">{{ userName }}</span>
                <button
                    type="button"
                    class="text-sm font-medium text-zinc-300 transition hover:text-acid"
                    @click="logout"
                >
                    Log out
                </button>
            </header>

            <main class="flex-1 px-5 py-6 sm:px-8 sm:py-10">
                <slot />
            </main>
        </div>
    </div>
</template>
