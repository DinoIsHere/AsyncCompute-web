<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SiteHeader from '../../Components/Public/SiteHeader.vue';
import SiteFooter from '../../Components/Public/SiteFooter.vue';
import PricingTable from '../../Components/Public/PricingTable.vue';

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
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(180,255,57,0.10),transparent_55%)]"
                    aria-hidden="true"
                ></div>
                <div class="relative mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-28">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-acid">
                        GPU cloud, simplified
                    </p>
                    <h1
                        class="mt-5 max-w-3xl text-4xl font-semibold leading-tight tracking-tight text-zinc-50 sm:text-6xl"
                    >
                        Compute on demand
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

            <section id="features" class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
                <div class="grid gap-4 md:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-xl border border-hairline bg-panel p-6"
                    >
                        <span class="inline-block h-3 w-3 rotate-45 bg-acid" aria-hidden="true"></span>
                        <h2 class="mt-5 text-base font-semibold text-zinc-100">{{ feature.title }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">{{ feature.body }}</p>
                    </article>
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
