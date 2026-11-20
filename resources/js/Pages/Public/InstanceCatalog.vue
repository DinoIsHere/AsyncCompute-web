<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import SiteHeader from '../../Components/Public/SiteHeader.vue';
import SiteFooter from '../../Components/Public/SiteFooter.vue';
import { motion, AnimatePresence } from 'motion-v';
import { enterProps, staggerProps } from '../../utils/motion';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
    },
});

// ---------------------------------------------------------------------
// Filters
// ---------------------------------------------------------------------
const REGIONS = [...new Set(props.plans.flatMap((plan) => (plan.offerings ?? []).map((o) => o.region)))].sort();

const filters = ref({
    search: '',
    minVram: 0,
    region: 'all',
    inStockOnly: false,
    maxGpuCount: 8,
});

const MIN_VRAM_OPTIONS = [0, 16, 40, 80, 96, 180];
const GPU_COUNT_OPTIONS = [1, 2, 4, 8];

function planMaxGpuCount(plan) {
    return Math.max(0, ...(plan.offerings ?? []).map((o) => Number(o.gpu_count)));
}

function planRegions(plan) {
    return [...new Set((plan.offerings ?? []).map((o) => o.region))];
}

function planTotalStock(plan) {
    return (plan.offerings ?? []).reduce((sum, o) => sum + Number(o.stock ?? 0), 0);
}

const filteredPlans = computed(() =>
    props.plans.filter((plan) => {
        const q = filters.value.search.trim().toLowerCase();
        if (q && !`${plan.name} ${plan.slug} ${plan.description ?? ''}`.toLowerCase().includes(q)) {
            return false;
        }
        if (Number(plan.vram_gb) < filters.value.minVram) return false;
        if (filters.value.region !== 'all' && !planRegions(plan).includes(filters.value.region)) {
            return false;
        }
        if (planMaxGpuCount(plan) < filters.value.maxGpuCount) return false;
        if (filters.value.inStockOnly && planTotalStock(plan) === 0) return false;
        return true;
    })
);

const hasFilters = computed(
    () =>
        filters.value.search !== '' ||
        filters.value.minVram !== 0 ||
        filters.value.region !== 'all' ||
        filters.value.inStockOnly ||
        filters.value.maxGpuCount !== 8
);

function resetFilters() {
    filters.value = {
        search: '',
        minVram: 0,
        region: 'all',
        inStockOnly: false,
        maxGpuCount: 8,
    };
}

// ---------------------------------------------------------------------
// Derived helpers
// ---------------------------------------------------------------------
function price(plan) {
    return Number(plan.price_per_gpu_hour ?? 0).toFixed(2);
}

function monthlyEstimate(plan) {
    // Rough estimate: 1 GPU, 50% utilization for a month (720 h * 0.5)
    return (Number(plan.price_per_gpu_hour ?? 0) * 360).toFixed(0);
}

function offeringsFor(plan, gpuCount) {
    return (plan.offerings ?? []).filter((o) => Number(o.gpu_count) === gpuCount);
}

function regionStock(plan, region) {
    return (plan.offerings ?? [])
        .filter((o) => o.region === region)
        .reduce((sum, o) => sum + Number(o.stock ?? 0), 0);
}

const REGION_LABELS = {
    'ap-south-1': 'Jakarta (ap-south-1)',
    'us-east-1': 'Virginia (us-east-1)',
    'eu-west-1': 'Dublin (eu-west-1)',
    'eu-central-1': 'Frankfurt (eu-central-1)',
    'us-west-2': 'Oregon (us-west-2)',
};
</script>

<template>
    <Head title="GPU Instances — Asyncindo" />

    <div class="min-h-screen bg-canvas text-zinc-100">
        <SiteHeader />

        <main>
            <!-- Hero -->
            <section class="border-b border-hairline">
                <motion.div v-bind="staggerProps(0)" class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20">
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-acid">
                        Instance catalogue
                    </p>
                    <h1 class="mt-4 max-w-3xl text-4xl font-semibold tracking-tight text-zinc-50 sm:text-5xl">
                        Every GPU, every region, one list
                    </h1>
                    <p class="mt-4 max-w-2xl text-base leading-relaxed text-zinc-400">
                        Live availability across all regions. Filter by VRAM, GPU count, or
                        location — then deploy from the dashboard, API, or CLI.
                    </p>
                </motion.div>
            </section>

            <!-- Filters -->
            <section class="sticky top-[65px] z-30 border-b border-hairline bg-canvas/95 backdrop-blur">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-5 py-4 sm:px-8">
                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="Search models…"
                        class="w-44 rounded-md border border-hairline bg-panel px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 outline-none transition focus:border-acid"
                        aria-label="Search GPU models"
                    />

                    <select
                        v-model.number="filters.minVram"
                        class="rounded-md border border-hairline bg-panel px-3 py-2 text-sm text-zinc-300 outline-none transition focus:border-acid"
                        aria-label="Minimum VRAM"
                    >
                        <option v-for="option in MIN_VRAM_OPTIONS" :key="option" :value="option">
                            {{ option === 0 ? 'Any VRAM' : `≥ ${option} GB VRAM` }}
                        </option>
                    </select>

                    <select
                        v-model="filters.region"
                        class="rounded-md border border-hairline bg-panel px-3 py-2 text-sm text-zinc-300 outline-none transition focus:border-acid"
                        aria-label="Region"
                    >
                        <option value="all">All regions</option>
                        <option v-for="region in REGIONS" :key="region" :value="region">
                            {{ REGION_LABELS[region] ?? region }}
                        </option>
                    </select>

                    <select
                        v-model.number="filters.maxGpuCount"
                        class="rounded-md border border-hairline bg-panel px-3 py-2 text-sm text-zinc-300 outline-none transition focus:border-acid"
                        aria-label="Minimum GPU count"
                    >
                        <option v-for="count in GPU_COUNT_OPTIONS" :key="count" :value="count">
                            {{ count }}× GPU+ available
                        </option>
                    </select>

                    <label class="flex cursor-pointer select-none items-center gap-2 text-sm text-zinc-400">
                        <input
                            v-model="filters.inStockOnly"
                            type="checkbox"
                            class="h-4 w-4 rounded border-hairline bg-panel accent-[#B4FF39]"
                        />
                        In stock only
                    </label>

                    <button
                        v-if="hasFilters"
                        type="button"
                        class="ml-auto text-xs font-medium text-zinc-400 underline-offset-4 transition hover:text-acid hover:underline"
                        @click="resetFilters"
                    >
                        Reset filters
                    </button>
                </div>
            </section>

            <!-- Catalogue -->
            <section class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
                <p class="text-xs text-zinc-500" role="status">
                    Showing {{ filteredPlans.length }} of {{ plans.length }} plans
                </p>

                <div class="mt-4 space-y-4">
                    <AnimatePresence>
                    <motion.article
                        v-for="plan in filteredPlans"
                        :key="plan.id"
                        v-bind="enterProps()"
                        class="overflow-hidden rounded-xl border border-hairline bg-panel transition hover:border-zinc-600"
                    >
                        <div class="grid gap-6 p-6 lg:grid-cols-[2fr_3fr]">
                            <!-- Left: identity + specs + price -->
                            <div>
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 class="text-lg font-semibold text-zinc-50">{{ plan.name }}</h2>
                                        <p class="mt-0.5 font-mono text-xs text-zinc-500">{{ plan.slug }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-semibold text-acid">${{ price(plan) }}</p>
                                        <p class="text-[11px] text-zinc-500">per GPU / hour</p>
                                    </div>
                                </div>

                                <p class="mt-3 text-sm leading-relaxed text-zinc-400">
                                    {{ plan.description }}
                                </p>

                                <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-xs sm:grid-cols-4">
                                    <div>
                                        <dt class="text-zinc-500">VRAM/GPU</dt>
                                        <dd class="mt-0.5 font-medium text-zinc-200">{{ plan.vram_gb }} GB</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500">vCPUs</dt>
                                        <dd class="mt-0.5 font-medium text-zinc-200">{{ plan.vcpus }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500">RAM</dt>
                                        <dd class="mt-0.5 font-medium text-zinc-200">{{ plan.ram_gb }} GB</dd>
                                    </div>
                                    <div>
                                        <dt class="text-zinc-500">Storage</dt>
                                        <dd class="mt-0.5 font-medium text-zinc-200">{{ plan.storage }}</dd>
                                    </div>
                                </dl>

                                <p class="mt-4 text-[11px] text-zinc-600">
                                    ≈ ${{ monthlyEstimate(plan) }}/mo at 1× GPU, 50% utilization
                                </p>
                            </div>

                            <!-- Right: per-region availability -->
                            <div class="rounded-lg border border-hairline bg-canvas p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-zinc-500">
                                    Availability by configuration & region
                                </p>
                                <div class="mt-3 space-y-3">
                                    <div
                                        v-for="count in GPU_COUNT_OPTIONS"
                                        :key="count"
                                    >
                                        <template v-if="offeringsFor(plan, count).length">
                                            <p class="text-xs font-medium text-zinc-300">{{ count }}× GPU</p>
                                            <div class="mt-1.5 flex flex-wrap gap-2">
                                                <span
                                                    v-for="offering in offeringsFor(plan, count)"
                                                    :key="offering.id"
                                                    class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px]"
                                                    :class="
                                                        offering.stock > 0
                                                            ? 'border-acid/30 bg-acid/10 text-acid'
                                                            : 'border-hairline text-zinc-500'
                                                    "
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5 rounded-full"
                                                        :class="offering.stock > 0 ? 'bg-acid' : 'bg-zinc-600'"
                                                        aria-hidden="true"
                                                    ></span>
                                                    {{ offering.region }}
                                                    <span class="text-zinc-500">
                                                        {{ offering.stock > 0 ? `· ${offering.stock} available` : '· waitlist' }}
                                                    </span>
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </motion.article>
                    </AnimatePresence>

                    <!-- Empty state -->
                    <motion.div
                        v-if="filteredPlans.length === 0"
                        v-bind="enterProps()"
                        class="rounded-xl border border-hairline bg-panel px-6 py-20 text-center"
                    >
                        <p class="text-sm font-medium text-zinc-300">No plans match your filters.</p>
                        <p class="mt-1 text-xs text-zinc-500">
                            Try widening the search or resetting the filters.
                        </p>
                        <button
                            type="button"
                            class="mt-4 rounded-md border border-hairline px-4 py-2 text-xs font-medium text-zinc-200 transition hover:border-acid hover:text-acid"
                            @click="resetFilters"
                        >
                            Reset filters
                        </button>
                    </motion.div>
                </div>
            </section>

            <!-- Cross-link back to pricing -->
            <section class="border-t border-hairline bg-panel">
                <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-4 px-5 py-10 sm:flex-row sm:items-center sm:px-8">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-50">Just want the prices?</h2>
                        <p class="mt-1 text-sm text-zinc-500">
                            The quick pricing comparison lives on the homepage.
                        </p>
                    </div>
                    <Link
                        href="/launch"
                        class="rounded-md bg-acid px-5 py-2.5 text-sm font-semibold text-canvas transition hover:brightness-95"
                    >
                        Go to pricing
                    </Link>
                </div>
            </section>
        </main>

        <SiteFooter />
    </div>
</template>
