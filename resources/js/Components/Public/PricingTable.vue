<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { AnimatePresence, LayoutGroup, motion } from 'motion-v';
import { MOTION, enterProps } from '../../utils/motion';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
    },
});

const gpuCounts = [8, 4, 2, 1];
const selectedCount = ref(8);

const filteredPlans = computed(() =>
    props.plans.filter((plan) =>
        (plan.offerings ?? []).some((offering) => Number(offering.gpu_count) === selectedCount.value)
    )
);

function offeringFor(plan) {
    return (plan.offerings ?? []).find(
        (offering) => Number(offering.gpu_count) === selectedCount.value
    );
}

function inStock(plan) {
    return Number(offeringFor(plan)?.stock ?? 0) > 0;
}

function price(plan) {
    return Number(plan.price_per_gpu_hour ?? 0).toFixed(2);
}
</script>

<template>
    <section class="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-acid">Pricing</p>
                <h2 class="mt-3 text-2xl font-semibold text-zinc-100 sm:text-3xl">
                    Pick your GPU, pay per second
                </h2>
                <p class="mt-2 text-sm text-zinc-500">
                    No commitments. Scale up or down whenever your workload changes.
                </p>
            </div>

            <LayoutGroup>
                <div class="flex flex-wrap gap-2" role="tablist" aria-label="GPU count">
                    <button
                        v-for="count in gpuCounts"
                        :key="count"
                        type="button"
                        role="tab"
                        :aria-selected="selectedCount === count"
                        class="relative overflow-hidden rounded-md px-4 py-2 text-sm font-semibold transition"
                        :class="
                            selectedCount === count
                                ? 'text-canvas'
                                : 'border border-hairline bg-panel text-zinc-400 hover:border-zinc-600 hover:text-zinc-100'
                        "
                        @click="selectedCount = count"
                    >
                        <motion.span
                            v-if="selectedCount === count"
                            layoutId="gpu-count-tab"
                            class="absolute inset-0 -z-10 rounded-md bg-acid"
                            :transition="{ duration: MOTION.duration, ease: MOTION.ease }"
                        />
                        <span class="relative z-10">{{ count }}× GPU</span>
                    </button>
                </div>
            </LayoutGroup>
        </div>

        <div class="overflow-hidden rounded-xl border border-hairline bg-panel shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-hairline text-[11px] uppercase tracking-widest text-zinc-500">
                            <th scope="col" class="sticky left-0 z-10 w-56 bg-panel px-5 py-4 font-medium">
                                Plan
                            </th>
                            <th scope="col" class="px-4 py-4 font-medium">VRAM/GPU</th>
                            <th scope="col" class="px-4 py-4 font-medium">vCPUs</th>
                            <th scope="col" class="px-4 py-4 font-medium">RAM</th>
                            <th scope="col" class="px-4 py-4 font-medium">Storage</th>
                            <th scope="col" class="px-4 py-4 font-medium">Price/GPU/hr</th>
                            <th scope="col" class="px-5 py-4 font-medium">Availability</th>
                        </tr>
                    </thead>
                    <tbody>
                        <AnimatePresence>
                        <motion.tr
                            v-for="plan in filteredPlans"
                            :key="plan.id"
                            v-bind="enterProps()"
                            :exit="MOTION.exit"
                            class="border-b border-hairline transition last:border-b-0 hover:bg-canvas/40"
                        >
                            <th scope="row" class="sticky left-0 z-10 bg-panel px-5 py-5 text-left font-normal">
                                <div class="font-semibold text-zinc-100">{{ plan.name }}</div>
                                <div class="mt-1 text-xs text-zinc-500">{{ plan.slug }}</div>
                            </th>
                            <td class="px-4 py-5 text-zinc-300">{{ Number(plan.vram_gb ?? 0) }} GB</td>
                            <td class="px-4 py-5 text-zinc-300">{{ plan.vcpus }}</td>
                            <td class="px-4 py-5 text-zinc-300">{{ plan.ram_gb }} GB</td>
                            <td class="px-4 py-5 text-zinc-300">{{ plan.storage }}</td>
                            <td class="whitespace-nowrap px-4 py-5 font-semibold text-zinc-100">
                                ${{ price(plan) }}
                            </td>
                            <td class="whitespace-nowrap px-5 py-5">
                                <span
                                    v-if="inStock(plan)"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-acid/30 bg-acid/10 px-2.5 py-1 text-xs font-medium text-acid"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-acid" aria-hidden="true"></span>
                                    In stock · {{ offeringFor(plan).stock }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 rounded-full border border-hairline px-2.5 py-1 text-xs font-medium text-zinc-500"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-zinc-600" aria-hidden="true"></span>
                                    Out of stock
                                </span>
                            </td>
                        </motion.tr>
                        </AnimatePresence>
                        <tr v-if="filteredPlans.length === 0">
                            <td colspan="7" class="px-5 py-16 text-center">
                                <p class="text-sm font-medium text-zinc-300">
                                    No {{ selectedCount }}× GPU instances available right now.
                                </p>
                                <p class="mt-1 text-xs text-zinc-500">
                                    Try another GPU count or check back soon.
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-hairline px-5 py-4">
                <p class="text-xs text-zinc-500">
                    Prices shown per GPU per hour. Billed by the second.
                </p>
                <Link
                    href="/launch"
                    class="rounded-md bg-acid px-4 py-2 text-xs font-semibold text-canvas transition hover:brightness-95"
                >
                    Launch instance
                </Link>
            </div>
        </div>
    </section>
</template>
