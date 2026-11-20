<script setup>
import { Link } from '@inertiajs/vue3';

const columns = [
    {
        title: 'Product',
        links: [
            { label: 'Instances', href: '/instances' },
            { label: 'Pricing', href: '/instances#pricing' },
            { label: 'Compare', href: '#' },
        ],
    },
    {
        title: 'Docs',
        links: [
            { label: 'Quickstart', href: '#' },
            { label: 'API reference', href: '#' },
            { label: 'CLI guide', href: '#' },
        ],
    },
    {
        title: 'Company',
        links: [
            { label: 'About', href: '#' },
            { label: 'Contact', href: '#' },
            { label: 'Status', href: '#' },
        ],
    },
];
</script>

<template>
    <footer class="border-t border-hairline bg-canvas">
        <div class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
            <div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="inline-block h-3 w-3 rotate-45 bg-acid" aria-hidden="true"></span>
                        <span class="text-sm font-semibold uppercase tracking-[0.2em] text-zinc-100">
                            asyncindo
                        </span>
                    </div>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed text-zinc-500">
                        On-demand GPU compute for training, fine-tuning, and inference.
                    </p>
                </div>

                <div v-for="column in columns" :key="column.title">
                    <h3 class="text-xs font-semibold uppercase tracking-widest text-zinc-200">
                        {{ column.title }}
                    </h3>
                    <ul class="mt-4 space-y-3">
                        <li v-for="link in column.links" :key="link.label">
                            <Link
                                :href="link.href"
                                class="text-sm text-zinc-500 transition hover:text-acid"
                            >
                                {{ link.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-12 border-t border-hairline pt-6 text-xs text-zinc-600">
                © 2026 PT. Asyncindo
            </div>
        </div>
    </footer>
</template>
