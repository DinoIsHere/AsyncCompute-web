<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SiteHeader from '../../Components/Public/SiteHeader.vue';
import SiteFooter from '../../Components/Public/SiteFooter.vue';

const timeline = [
    {
        year: '2019',
        title: 'Two laptops and one very hot room',
        body: 'Arif and Sinta, then ML engineers at a Jakarta fintech, got tired of waiting three weeks for training runs. They rented a corner of a warehouse in East Jakarta, stacked eight second-hand GTX 1080 Tis, and called it a cluster.',
    },
    {
        year: '2020',
        title: 'PT Asyncindo is incorporated',
        body: 'The side project became a company. Friends from the local data-science community became the first customers, paying in patience while invoices got sorted out.',
    },
    {
        year: '2022',
        title: 'First data center footprint',
        body: 'Asyncindo moved into its own cage in a Jakarta data center, added proper redundancy, and launched multi-GPU instances. The second-hand GPU era officially ended.',
    },
    {
        year: '2024',
        title: 'National scale',
        body: 'Regions opened in Surabaya and Batam, and per-second billing shipped. A small startup training Bahasa Indonesia speech models became our first customer running 24/7 production inference.',
    },
    {
        year: '2026',
        title: 'Today',
        body: 'Over 400 teams across Indonesia and Southeast Asia build on Asyncindo — from university labs fine-tuning small models to companies serving millions of inference requests a day.',
    },
];

const values = [
    {
        title: 'Straight talk',
        body: 'No surprise line items, no "contact sales" walls. Per-second pricing, published uptime, and engineers who answer you directly.',
    },
    {
        title: 'Built here, for here',
        body: 'Data residency in Indonesia, latency that respects Jakarta traffic, and support in Bahasa Indonesia and English — because regional teams deserve regional infrastructure.',
    },
    {
        title: 'Boring reliability',
        body: 'We would rather ship dependable hardware and predictable uptime than chase every new accelerator launch. Your training run should never die at hour nine.',
    },
];

const stats = [
    { value: '400+', label: 'Teams building on Asyncindo' },
    { value: '3', label: 'Regions: Jakarta, Surabaya, Batam' },
    { value: '99.9%', label: 'Uptime over the last 12 months' },
    { value: '2.4M', label: 'GPU-hours delivered in 2025' },
];

const team = [
    {
        name: 'Arif Wibowo',
        role: 'Co-founder & CEO',
        bio: 'Former ML engineer. Writes the pricing pages personally and answers support tickets on weekends.',
    },
    {
        name: 'Sinta Maharani',
        role: 'Co-founder & CTO',
        bio: 'Built the first cluster by hand. Still reviews every data center design before a rack goes in.',
    },
    {
        name: 'Bayu Pratama',
        role: 'Head of Infrastructure',
        bio: 'Ex-telecom network engineer. Believes uptime is a promise, not a metric.',
    },
    {
        name: 'Dewi Anggraini',
        role: 'Head of Developer Experience',
        bio: 'Runs the CLI, the SDK, and the docs. If onboarding takes more than ten minutes, she wants to know why.',
    },
];
</script>

<template>
    <Head title="About — Asyncindo" />

    <div class="min-h-screen bg-canvas text-zinc-100">
        <SiteHeader />

        <main>
            <!-- Hero -->
            <section class="relative overflow-hidden border-b border-hairline">
                <div
                    class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top,rgba(180,255,57,0.10),transparent_55%)]"
                    aria-hidden="true"
                ></div>
                <div class="relative mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-28">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-acid">
                        About PT Asyncindo
                    </p>
                    <h1
                        class="mt-5 max-w-3xl text-4xl font-semibold leading-tight tracking-tight text-zinc-50 sm:text-6xl"
                    >
                        Regional compute, without the waiting list
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-relaxed text-zinc-400 sm:text-lg">
                        We started Asyncindo because training a model in Southeast Asia meant
                        sending your data somewhere else and waiting. It shouldn't.
                    </p>
                </div>
            </section>

            <!-- Story / timeline -->
            <section class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
                <div class="grid gap-12 lg:grid-cols-[1fr_2fr]">
                    <div>
                        <h2 class="text-2xl font-semibold text-zinc-50 sm:text-3xl">Our story</h2>
                        <p class="mt-4 text-sm leading-relaxed text-zinc-500">
                            From eight second-hand GPUs in a hot warehouse to infrastructure that
                            serves thousands of jobs a day.
                        </p>
                    </div>
                    <ol class="relative space-y-10 border-l border-hairline pl-8">
                        <li v-for="item in timeline" :key="item.year" class="relative">
                            <span
                                class="absolute -left-[37px] top-1.5 h-3 w-3 rotate-45 border border-acid bg-canvas"
                                aria-hidden="true"
                            ></span>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-acid">
                                {{ item.year }}
                            </p>
                            <h3 class="mt-2 text-base font-semibold text-zinc-100">
                                {{ item.title }}
                            </h3>
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-zinc-500">
                                {{ item.body }}
                            </p>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- Stats -->
            <section class="border-y border-hairline bg-panel">
                <div
                    class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-5 py-12 sm:px-8 lg:grid-cols-4"
                >
                    <div v-for="stat in stats" :key="stat.label">
                        <p class="text-3xl font-semibold text-acid">{{ stat.value }}</p>
                        <p class="mt-2 text-sm text-zinc-500">{{ stat.label }}</p>
                    </div>
                </div>
            </section>

            <!-- Values -->
            <section class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
                <h2 class="text-2xl font-semibold text-zinc-50 sm:text-3xl">What we believe</h2>
                <div class="mt-8 grid gap-4 md:grid-cols-3">
                    <article
                        v-for="value in values"
                        :key="value.title"
                        class="rounded-xl border border-hairline bg-panel p-6"
                    >
                        <span class="inline-block h-3 w-3 rotate-45 bg-acid" aria-hidden="true"></span>
                        <h3 class="mt-5 text-base font-semibold text-zinc-100">
                            {{ value.title }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-zinc-500">
                            {{ value.body }}
                        </p>
                    </article>
                </div>
            </section>

            <!-- Team -->
            <section class="border-t border-hairline">
                <div class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
                    <h2 class="text-2xl font-semibold text-zinc-50 sm:text-3xl">The team</h2>
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-zinc-500">
                        Small, senior, and hands-on. The people who build the platform also run it.
                    </p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <article
                            v-for="member in team"
                            :key="member.name"
                            class="rounded-xl border border-hairline bg-panel p-6"
                        >
                            <div
                                class="grid h-12 w-12 place-items-center rounded-full border border-hairline bg-canvas text-sm font-semibold text-acid"
                                aria-hidden="true"
                            >
                                {{ member.name.split(' ').map((n) => n[0]).join('') }}
                            </div>
                            <h3 class="mt-4 text-base font-semibold text-zinc-100">
                                {{ member.name }}
                            </h3>
                            <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">
                                {{ member.role }}
                            </p>
                            <p class="mt-3 text-sm leading-relaxed text-zinc-500">
                                {{ member.bio }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <!-- CTA -->
            <section class="border-t border-hairline">
                <div class="mx-auto max-w-7xl px-5 py-20 sm:px-8 sm:py-24">
                    <div
                        class="flex flex-col items-start justify-between gap-6 rounded-xl border border-hairline bg-panel p-8 sm:flex-row sm:items-center sm:p-10"
                    >
                        <div>
                            <h2 class="text-2xl font-semibold text-zinc-50 sm:text-3xl">
                                Come build with us
                            </h2>
                            <p class="mt-2 text-sm text-zinc-500">
                                Spin up your first GPU instance in minutes and see why teams stay.
                            </p>
                        </div>
                        <Link
                            href="/instances#pricing"
                            class="shrink-0 rounded-md bg-acid px-5 py-3 text-sm font-semibold text-canvas transition hover:brightness-95"
                        >
                            Launch instance
                        </Link>
                    </div>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
