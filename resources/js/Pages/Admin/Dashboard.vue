<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CountUp from '@/Components/CountUp.vue';
import LiveDot from '@/Components/LiveDot.vue';
import { motion } from 'motion-v';
import { staggerProps } from '../../utils/motion';

const props = defineProps({
    stats: { type: Array, default: () => [] },
    revenueSeries: { type: Array, default: () => [] },
    revenueMax: { type: Number, default: 1 },
    jobsByType: { type: Array, default: () => [] },
    regionDemand: { type: Array, default: () => [] },
    nodes: { type: Array, default: () => [] },
    fleetSummary: { type: Object, default: () => ({}) },
    clusters: { type: Array, default: () => [] },
    recentJobs: { type: Array, default: () => [] },
    topCustomers: { type: Array, default: () => [] },
    customersByTier: { type: Object, default: () => ({}) },
});

// ---------------------------------------------------------------------
// Chart helpers (pure SVG, no chart library)
// ---------------------------------------------------------------------
const CHART_W = 720;
const CHART_H = 200;
const CHART_PAD = 4;

function revenuePoints(series) {
    if (!series.length) return '';
    const step = (CHART_W - CHART_PAD * 2) / Math.max(1, series.length - 1);
    return series
        .map((point, i) => {
            const x = CHART_PAD + i * step;
            const y = CHART_H - (point.revenue / props.revenueMax) * (CHART_H - 10);
            return `${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');
}

function revenueAreaPoints(series) {
    if (!series.length) return '';
    return `0,${CHART_H} ${revenuePoints(series)} ${CHART_W},${CHART_H}`;
}

function statusClass(status) {
    return {
        online: 'text-emerald-400 bg-emerald-400/10 border-emerald-400/30',
        healthy: 'text-emerald-400 bg-emerald-400/10 border-emerald-400/30',
        running: 'text-emerald-400 bg-emerald-400/10 border-emerald-400/30',
        draining: 'text-amber-400 bg-amber-400/10 border-amber-400/30',
        maintenance: 'text-amber-400 bg-amber-400/10 border-amber-400/30',
        degraded: 'text-amber-400 bg-amber-400/10 border-amber-400/30',
        queued: 'text-amber-400 bg-amber-400/10 border-amber-400/30',
        offline: 'text-red-400 bg-red-400/10 border-red-400/30',
        critical: 'text-red-400 bg-red-400/10 border-red-400/30',
        failed: 'text-red-400 bg-red-400/10 border-red-400/30',
        completed: 'text-zinc-400 bg-zinc-400/10 border-zinc-500/40',
    }[status] ?? 'text-zinc-400 bg-zinc-400/10 border-zinc-500/40';
}

function utilColor(value) {
    if (value >= 85) return 'bg-acid';
    if (value >= 60) return 'bg-emerald-400';
    if (value >= 30) return 'bg-amber-400';
    return 'bg-zinc-500';
}

const TYPE_COLORS = {
    training: '#B4FF39',
    'fine-tuning': '#5EEAD4',
    inference: '#818CF8',
    benchmark: '#FBBF24',
};

const JOB_TYPE_TOTALS = () => props.jobsByType.reduce((sum, t) => sum + t.count, 0) || 1;

// Donut chart geometry
const DONUT_R = 52;
const DONUT_C = 2 * Math.PI * DONUT_R;

function donutSegments() {
    const total = JOB_TYPE_TOTALS();
    let offset = 0;
    return props.jobsByType.map((type) => {
        const fraction = type.count / total;
        const segment = {
            ...type,
            color: TYPE_COLORS[type.type] ?? '#71717A',
            dash: fraction * DONUT_C,
            offset: -offset * DONUT_C,
            pct: Math.round(fraction * 100),
        };
        offset += fraction;
        return segment;
    });
}
</script>

<template>
    <Head title="Admin dashboard" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-100">Dashboard</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    Fleet, revenue, and customers at a glance.
                </p>
            </div>
            <Link
                href="/admin/instances"
                class="rounded-md border border-hairline px-4 py-2 text-sm font-medium text-zinc-200 transition hover:border-acid hover:text-acid"
            >
                Manage catalogue
            </Link>
        </div>

        <!-- KPI stats -->
        <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-lg border border-hairline bg-panel p-5"
            >
                <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">
                    {{ stat.label }}
                </p>
                <p class="mt-3 text-3xl font-semibold text-zinc-50">{{ stat.value }}</p>
                <p class="mt-2 text-xs text-zinc-500">
                    <span
                        v-if="stat.delta !== null"
                        :class="stat.delta >= 0 ? 'text-emerald-400' : 'text-red-400'"
                        class="font-semibold"
                    >
                        {{ stat.delta >= 0 ? '▲' : '▼' }} {{ Math.abs(stat.delta) }}%
                    </span>
                    {{ stat.deltaLabel }}
                </p>
            </div>
        </div>

        <!-- Charts row -->
        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <!-- Revenue chart -->
            <div class="rounded-lg border border-hairline bg-panel p-5 lg:col-span-2">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-sm font-semibold text-zinc-100">Revenue — last 30 days</h2>
                    <p class="text-xs text-zinc-500">
                        peak ${{ Number(revenueMax).toLocaleString() }}
                    </p>
                </div>
                <svg
                    :viewBox="`0 0 ${CHART_W} ${CHART_H}`"
                    class="mt-4 h-44 w-full"
                    preserveAspectRatio="none"
                    role="img"
                    aria-label="Daily revenue for the last 30 days"
                >
                    <defs>
                        <linearGradient id="rev-fill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#B4FF39" stop-opacity="0.25" />
                            <stop offset="100%" stop-color="#B4FF39" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    <polygon :points="revenueAreaPoints(revenueSeries)" fill="url(#rev-fill)" />
                    <polyline
                        :points="revenuePoints(revenueSeries)"
                        fill="none"
                        stroke="#B4FF39"
                        stroke-width="2"
                        stroke-linejoin="round"
                    />
                </svg>
                <div class="mt-2 flex justify-between text-[10px] uppercase tracking-widest text-zinc-600">
                    <span>{{ revenueSeries[0]?.date }}</span>
                    <span>{{ revenueSeries[Math.floor(revenueSeries.length / 2)]?.date }}</span>
                    <span>{{ revenueSeries[revenueSeries.length - 1]?.date }}</span>
                </div>
            </div>

            <!-- Jobs by type donut -->
            <div class="rounded-lg border border-hairline bg-panel p-5">
                <h2 class="text-sm font-semibold text-zinc-100">Jobs by type</h2>
                <div class="mt-4 flex items-center gap-6">
                    <svg viewBox="0 0 140 140" class="h-32 w-32 shrink-0 -rotate-90">
                        <circle
                            cx="70" cy="70" :r="DONUT_R" fill="none"
                            stroke="#2A2A2E" stroke-width="16"
                        />
                        <circle
                            v-for="segment in donutSegments()"
                            :key="segment.type"
                            cx="70" cy="70" :r="DONUT_R" fill="none"
                            :stroke="segment.color" stroke-width="16"
                            :stroke-dasharray="`${segment.dash} ${DONUT_C - segment.dash}`"
                            :stroke-dashoffset="segment.offset"
                        />
                    </svg>
                    <ul class="min-w-0 flex-1 space-y-2 text-xs">
                        <li
                            v-for="segment in donutSegments()"
                            :key="`legend-${segment.type}`"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="h-2.5 w-2.5 shrink-0 rounded-sm"
                                :style="{ background: segment.color }"
                            ></span>
                            <span class="capitalize text-zinc-300">{{ segment.type }}</span>
                            <span class="ml-auto text-zinc-500">{{ segment.pct }}%</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Fleet + region demand -->
        <div class="mt-6 grid gap-4 lg:grid-cols-3">
            <!-- Hardware nodes -->
            <div class="rounded-lg border border-hairline bg-panel p-5 lg:col-span-2">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-sm font-semibold text-zinc-100">Hardware — live nodes</h2>
                    <p class="text-xs text-zinc-500">
                        {{ fleetSummary.online }} online · {{ fleetSummary.draining }} draining ·
                        {{ fleetSummary.maintenance }} maint · {{ fleetSummary.offline }} offline ·
                        {{ fleetSummary.totalPowerKw }} kW draw
                    </p>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[640px] text-left text-xs">
                        <thead class="text-[10px] uppercase tracking-widest text-zinc-500">
                            <tr class="border-b border-hairline">
                                <th class="pb-2 pr-3 font-medium">Node</th>
                                <th class="pb-2 pr-3 font-medium">Model</th>
                                <th class="pb-2 pr-3 font-medium">Status</th>
                                <th class="pb-2 pr-3 font-medium">GPU</th>
                                <th class="pb-2 pr-3 font-medium">Temp</th>
                                <th class="pb-2 pr-3 font-medium">Power</th>
                                <th class="pb-2 font-medium w-40">Utilization</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-hairline text-zinc-300">
                            <tr v-for="node in nodes" :key="node.id">
                                <td class="py-2.5 pr-3 font-mono">{{ node.hostname }}</td>
                                <td class="py-2.5 pr-3">{{ node.model }}</td>
                                <td class="py-2.5 pr-3">
                                    <span
                                        class="rounded border px-1.5 py-0.5 text-[10px] capitalize"
                                        :class="statusClass(node.status)"
                                    >
                                        {{ node.status }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-3">×{{ node.gpu_count }}</td>
                                <td class="py-2.5 pr-3" :class="node.temp_c > 75 ? 'text-red-400' : ''">
                                    {{ node.temp_c }}°C
                                </td>
                                <td class="py-2.5 pr-3">{{ (node.power_w / 1000).toFixed(1) }} kW</td>
                                <td class="py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-canvas">
                                            <div
                                                class="h-full rounded-full"
                                                :class="utilColor(node.gpu_util)"
                                                :style="{ width: node.gpu_util + '%' }"
                                            ></div>
                                        </div>
                                        <span class="tabular-nums text-zinc-500">{{ node.gpu_util }}%</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Region demand bars -->
            <div class="rounded-lg border border-hairline bg-panel p-5">
                <h2 class="text-sm font-semibold text-zinc-100">Utilization by region</h2>
                <div class="mt-5 space-y-5">
                    <div v-for="region in regionDemand" :key="region.region">
                        <div class="flex justify-between text-xs">
                            <span class="font-mono text-zinc-300">{{ region.region }}</span>
                            <span class="text-zinc-500">
                                {{ region.nodes }} nodes · {{ region.avg_util }}%
                            </span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-canvas">
                            <div
                                class="h-full rounded-full bg-acid transition-all"
                                :style="{ width: region.avg_util + '%' }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Clusters -->
        <div class="mt-6 rounded-lg border border-hairline bg-panel p-5">
            <h2 class="text-sm font-semibold text-zinc-100">Clusters</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <div
                    v-for="cluster in clusters"
                    :key="cluster.id"
                    class="rounded-lg border border-hairline bg-canvas p-4"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-mono text-sm text-zinc-100">{{ cluster.name }}</p>
                        <span
                            class="rounded border px-1.5 py-0.5 text-[10px] capitalize"
                            :class="statusClass(cluster.status)"
                        >
                            {{ cluster.status }}
                        </span>
                    </div>
                    <p class="mt-1.5 text-xs text-zinc-500">
                        {{ cluster.model }} · {{ cluster.region }} · {{ cluster.node_count }} nodes /
                        {{ cluster.gpu_total }} GPUs
                    </p>
                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-panel">
                        <div
                            class="h-full rounded-full"
                            :class="utilColor(cluster.utilization)"
                            :style="{ width: cluster.utilization + '%' }"
                        ></div>
                    </div>
                    <p class="mt-1.5 text-right text-[10px] text-zinc-500">
                        {{ cluster.utilization }}% utilized
                    </p>
                </div>
            </div>
        </div>

        <!-- Customers + recent jobs -->
        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            <!-- Top customers -->
            <div class="rounded-lg border border-hairline bg-panel p-5">
                <h2 class="text-sm font-semibold text-zinc-100">Top customers</h2>
                <table class="mt-4 w-full text-left text-xs">
                    <thead class="text-[10px] uppercase tracking-widest text-zinc-500">
                        <tr class="border-b border-hairline">
                            <th class="pb-2 pr-3 font-medium">Company</th>
                            <th class="pb-2 pr-3 font-medium">Tier</th>
                            <th class="pb-2 pr-3 font-medium">Spend</th>
                            <th class="pb-2 font-medium">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline text-zinc-300">
                        <tr v-for="customer in topCustomers" :key="customer.id">
                            <td class="py-2.5 pr-3">
                                {{ customer.company }}
                                <span class="block text-[10px] text-zinc-600">{{ customer.country }}</span>
                            </td>
                            <td class="py-2.5 pr-3">
                                <span class="capitalize text-zinc-400">{{ customer.tier }}</span>
                            </td>
                            <td class="py-2.5 pr-3 tabular-nums text-acid">
                                ${{ Number(customer.spend).toLocaleString() }}
                            </td>
                            <td class="py-2.5 tabular-nums text-zinc-500">
                                ${{ Number(customer.balance).toLocaleString() }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="mt-3 text-[10px] uppercase tracking-widest text-zinc-600">
                    Tiers —
                    <span v-for="(count, tier) in customersByTier" :key="tier" class="mr-2">
                        {{ tier }}: {{ count }}
                    </span>
                </p>
            </div>

            <!-- Recent jobs -->
            <div class="rounded-lg border border-hairline bg-panel p-5">
                <h2 class="text-sm font-semibold text-zinc-100">Recent jobs</h2>
                <table class="mt-4 w-full text-left text-xs">
                    <thead class="text-[10px] uppercase tracking-widest text-zinc-500">
                        <tr class="border-b border-hairline">
                            <th class="pb-2 pr-3 font-medium">Job</th>
                            <th class="pb-2 pr-3 font-medium">Customer</th>
                            <th class="pb-2 pr-3 font-medium">Type</th>
                            <th class="pb-2 pr-3 font-medium">Status</th>
                            <th class="pb-2 font-medium text-right">Cost</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline text-zinc-300">
                        <tr v-for="job in recentJobs" :key="job.id">
                            <td class="py-2.5 pr-3 font-mono text-[11px]">{{ job.name }}</td>
                            <td class="py-2.5 pr-3">{{ job.customer }}</td>
                            <td class="py-2.5 pr-3 capitalize text-zinc-400">{{ job.type }}</td>
                            <td class="py-2.5 pr-3">
                                <span
                                    class="rounded border px-1.5 py-0.5 text-[10px] capitalize"
                                    :class="statusClass(job.status)"
                                >
                                    {{ job.status }}
                                </span>
                            </td>
                            <td class="py-2.5 text-right tabular-nums">
                                ${{ job.cost.toFixed(2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
