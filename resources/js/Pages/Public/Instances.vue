<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SiteHeader from '../../Components/Public/SiteHeader.vue';
import SiteFooter from '../../Components/Public/SiteFooter.vue';
import PricingTable from '../../Components/Public/PricingTable.vue';
import WebGLBackground from '../../Components/Public/WebGLBackground.vue';
import GlitchText from '../../Components/Public/GlitchText.vue';
import LogoMarquee from '../../Components/Public/LogoMarquee.vue';
import { motion } from 'motion-v';
import { enterProps } from '../../utils/motion';

defineProps({
    plans: {
        type: Array,
        required: true,
    },
});

const features = [
    {
        title: 'Launch in minutes',
        body: 'Pick a GPU, choose a region, and start training without waiting on capacity requests.',
    },
    {
        title: 'Multi-GPU instances',
        body: 'Scale from a single GPU to eight connected accelerators with high-bandwidth NVLink.',
    },
    {
        title: 'Use UI, API, or CLI',
        body: 'Drive your fleet from the dashboard, automate with our API, or stay in the terminal.',
    },
];

const brands = [
    {
        name: 'NVIDIA',
        icon: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12.3 8.6c-.3 0-.6 0-.9.1v-1.6h1.9c2.5 0 4.3 1.1 4.3 3.4 0 2.4-1.9 3.5-4.4 3.5h-1.8v-1.6c.3.1.7.1 1 .1 1.3 0 2.2-.6 2.2-2 0-1.3-.9-1.9-2.3-1.9zm-1.7-.6H8.9v6.9c1 .3 2.1.4 3.2.4 3.4 0 5.7-1.6 5.7-4s-2.3-3.6-5.3-3.6c-1.4 0-2.7.1-3.9.3z"/><path d="M9.5 8.7c-2 .5-3.4 1.5-3.4 3.1 0 1.7 1.6 2.8 3.8 3.2v-1.5c-1.2-.3-2-.8-2-1.7 0-.8.6-1.4 1.6-1.7v-1.4zm5 4.8v1.5c2.3-.4 3.9-1.5 3.9-3.2 0-1.6-1.5-2.7-3.6-3.1v1.5c1 .3 1.8.8 1.8 1.6 0 .8-.8 1.4-2.1 1.7z"/></svg>',
    },
    {
        name: 'PyTorch',
        icon: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 3.2L5.4 9.8a4.7 4.7 0 000 6.6l2.1 2.1V13c0-.9.4-1.8 1-2.4l3.5-3.5 3.5 3.5c.6.6 1 1.5 1 2.4v4.4l-1 1V13a2.5 2.5 0 00-.8-1.8L12 8.4 9.3 11.2c-.5.5-.8 1.1-.8 1.8v6.4l1.6 1.6c1 .9 2.8 1 3.8 0l4.7-4.7a4.7 4.7 0 000-6.6L12 3.2z"/></svg>',
    },
    {
        name: 'TensorFlow',
        icon: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2L3.5 6.9v3.3L10.4 6.3v4.4l-3 1.7v3.2l3-1.7v6.8l1.6.9 1.6-.9v-10l6.9-3.9V4.9L12 2zm1.6 3.4l3.7 2.1-3.7 2.1V5.4z"/></svg>',
    },
    {
        name: 'Hugging Face',
        icon: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm-3.4 6.6c.6 0 1.1.5 1.1 1.1s-.5 1.1-1.1 1.1-1.1-.5-1.1-1.1.5-1.1 1.1-1.1zm6.8 0c.6 0 1.1.5 1.1 1.1s-.5 1.1-1.1 1.1-1.1-.5-1.1-1.1.5-1.1 1.1-1.1zm-6.6 5.2c.9.9 2 1.4 3.2 1.4s2.3-.5 3.2-1.4l1.2 1.2a6.2 6.2 0 01-8.8 0l1.2-1.2z"/></svg>',
    },
    {
        name: 'Meta AI',
        icon: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M6.5 6C4 6 2.5 8.5 2.5 12s1.5 6 4 6c1.6 0 2.8-1.3 4.2-3.4l1.3-2 1.3 2c1.4 2.1 2.6 3.4 4.2 3.4 2.5 0 4-2.5 4-6s-1.5-6-4-6c-1.6 0-2.8 1.3-4.2 3.4l-1.3 2-1.3-2C9.3 7.3 8.1 6 6.5 6zm0 2.2c.7 0 1.6.9 2.7 2.7l1.1 1.7-1.3 2c-1 1.5-1.8 2.2-2.5 2.2-1 0-1.9-1.5-1.9-3.8s.9-4.8 1.9-4.8zm11 0c1 0 1.9 2.5 1.9 4.8s-.9 3.8-1.9 3.8c-.7 0-1.5-.7-2.5-2.2l-1.3-2 1.1-1.7c1.1-1.8 2-2.7 2.7-2.7z"/></svg>',
    },
    {
        name: 'Stability AI',
        icon: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2l2.4 4.8L19 9.2l-4.6 2.4L12 16.4l-2.4-4.8L5 9.2l4.6-2.4L12 2zm0 12.6l1.6-3.2 3.2-1.6-3.2-1.6L12 5l-1.6 3.2L7.2 9.8l3.2 1.6L12 14.6zM5 15l1.4 2.8L9.2 19l-2.8 1.4L5 23l-1.4-2.6L.8 19l2.8-1.2L5 15z"/></svg>',
    },
    {
        name: 'Google Colab',
        icon: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 4.5c-2.8 0-5.1 1.6-6.2 4A4.7 4.7 0 002 13a4.7 4.7 0 004.7 4.7c.8 0 1.6-.2 2.3-.6a6.8 6.8 0 006 0c.7.4 1.5.6 2.3.6A4.7 4.7 0 0022 13a4.7 4.7 0 00-3.8-4.5c-1.1-2.4-3.4-4-6.2-4zm0 2.2c2 0 3.7 1 4.7 2.6a4.6 4.6 0 012.7 4.4 2.5 2.5 0 01-2.5 2.5c-.6 0-1.2-.2-1.7-.6a6.8 6.8 0 01-6.4 0c-.5.4-1.1.6-1.7.6a2.5 2.5 0 01-2.5-2.5c0-2 1.2-3.7 2.7-4.4 1-1.6 2.7-2.6 4.7-2.6z"/></svg>',
    },
    {
        name: 'Kaggle',
        icon: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M8.2 3h3v8.5l6-8.5h3.4l-6.6 9.3L20.8 21h-3.5l-6.1-8.7V21h-3V3z"/></svg>',
    },
    {
        name: 'Modal',
        icon: '<svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M4 4h16v3H4zM4 10.5h16v3H4zM4 17h16v3H4z"/></svg>',
    },
];

const valueProps = [
    {
        title: 'Transparent pricing',
        body: 'Per-second billing with no hidden fees. The price you see is the price you pay.',
    },
    {
        title: 'Performance you can trust',
        body: 'Modern NVIDIA accelerators, fast local NVMe, and networks built for distributed jobs.',
    },
    {
        title: 'Built in Indonesia',
        body: 'Local support and data residency options for teams building across the region.',
    },
];
</script>

<template>
    <Head title="GPU Instances — Asyncindo" />

    <div class="min-h-screen bg-canvas text-zinc-100">
        <SiteHeader />

        <main>
            <section class="relative overflow-hidden border-b border-hairline">
                <WebGLBackground />
                <div class="relative mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-28">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-acid">
                        GPU cloud, simplified
                    </p>
                    <h1
                        class="mt-5 max-w-3xl text-4xl font-semibold leading-tight tracking-tight text-zinc-50 sm:text-6xl"
                    >
                        <GlitchText text="Compute on demand" :radius="180" />
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-zinc-400 sm:text-lg">
                        Train, fine-tune, and serve on 1–8 GPU instances. Provision in minutes, pay
                        only for what you use, and scale when your models demand it.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link
                            href="/instances#pricing"
                            class="rounded-md bg-acid px-5 py-3 text-sm font-semibold text-canvas transition hover:brightness-95"
                        >
                            Launch instance
                        </Link>
                        <Link
                            href="#features"
                            class="rounded-md border border-hairline px-5 py-3 text-sm font-semibold text-zinc-200 transition hover:border-zinc-600"
                        >
                            Explore the platform
                        </Link>
                    </div>
                </div>
            </section>

            <LogoMarquee :brands="brands" />

            <section id="features" class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
                <div class="grid gap-4 md:grid-cols-3">
                    <motion.article
                        v-for="(feature, index) in features"
                        :key="feature.title"
                        v-bind="enterProps({ delay: index * 0.05 })"
                        class="rounded-xl border border-hairline bg-panel p-6"
                    >
                        <span class="inline-block h-3 w-3 rotate-45 bg-acid" aria-hidden="true"></span>
                        <h2 class="mt-5 text-base font-semibold text-zinc-100">{{ feature.title }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">{{ feature.body }}</p>
                    </motion.article>
                </div>
            </section>

            <PricingTable :plans="plans" id="pricing" />

            <section class="border-y border-hairline bg-panel">
                <div class="mx-auto grid max-w-7xl gap-8 px-5 py-16 sm:px-8 md:grid-cols-3">
                    <div v-for="prop in valueProps" :key="prop.title">
                        <h2 class="text-base font-semibold text-zinc-100">{{ prop.title }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">{{ prop.body }}</p>
                    </div>
                </div>
            </section>

            <section class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-24">
                <div
                    class="flex flex-col items-start justify-between gap-6 rounded-xl border border-hairline bg-panel p-8 sm:flex-row sm:items-center sm:p-10"
                >
                    <div>
                        <h2 class="text-2xl font-semibold text-zinc-50 sm:text-3xl">
                            Ready to run your first job?
                        </h2>
                        <p class="mt-2 text-sm text-zinc-500">
                            Choose a GPU above and deploy in minutes. No reservation required.
                        </p>
                    </div>
                    <Link
                        href="/instances#pricing"
                        class="shrink-0 rounded-md bg-acid px-5 py-3 text-sm font-semibold text-canvas transition hover:brightness-95"
                    >
                        Get started
                    </Link>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
