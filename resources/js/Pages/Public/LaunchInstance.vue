<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import SiteHeader from '../../Components/Public/SiteHeader.vue';
import { motion, AnimatePresence } from 'motion-v';
import { enterProps, hoverProps, staggerProps } from '../../utils/motion';
import SiteFooter from '../../Components/Public/SiteFooter.vue';

const props = defineProps({
    plans: {
        type: Array,
        required: true,
    },
    myInstances: {
        type: Array,
        default: () => [],
    },
});

// ---------------------------------------------------------------------
// Wizard state
// ---------------------------------------------------------------------
const GPU_COUNTS = [1, 2, 4, 8];

const step = ref(1); // 1 = GPU model, 2 = configuration, 3 = review
const selectedPlanId = ref(null);
const selectedCount = ref(1);

const selectedPlan = computed(
    () => props.plans.find((plan) => plan.id === selectedPlanId.value) ?? null
);

// Offerings for the selected plan + count, sorted by price-relevant fields
const selectedOfferings = computed(() =>
    ((selectedPlan.value?.offerings ?? []))
        .filter((offering) => Number(offering.gpu_count) === selectedCount.value)
        .map((offering) => ({
            ...offering,
            hourly: Number(selectedPlan.value.price_per_gpu_hour) * Number(offering.gpu_count),
            daily: Number(selectedPlan.value.price_per_gpu_hour) * Number(offering.gpu_count) * 24,
        }))
);

const selectedOfferingId = ref(null);

const selectedOffering = computed(
    () => selectedOfferings.value.find((offering) => offering.id === selectedOfferingId.value) ?? null
);

// ---------------------------------------------------------------------
// Form
// ---------------------------------------------------------------------
const form = useForm({
    offering_id: null,
    name: '',
    ssh_key_name: '',
});

const generatedName = computed(() => {
    if (!selectedPlan.value) return '';
    const count = selectedCount.value;
    const region = selectedOffering.value?.region?.split('-')[0] ?? 'gpu';
    return `${selectedPlan.value.slug}-${count}x-${region}`;
});

function pickPlan(plan) {
    selectedPlanId.value = plan.id;
    selectedCount.value = 1;
    selectedOfferingId.value = null;
    form.clearErrors();
    step.value = 2;
}

function pickOffering(offering) {
    selectedOfferingId.value = offering.id;
    form.offering_id = offering.id;
    if (!form.name) form.name = generatedName.value;
}

function backToPlans() {
    step.value = 1;
    selectedOfferingId.value = null;
    form.clearErrors();
}

function backToConfig() {
    step.value = 1;
    form.clearErrors();
}

function launch() {
    form.post('/launch', {
        preserveScroll: true,
        onSuccess: () => {
            step.value = 1;
            selectedPlanId.value = null;
            selectedOfferingId.value = null;
            form.reset();
        },
    });
}

function terminate(instance) {
    if (!window.confirm(`Terminate ${instance.name}? This cannot be undone.`)) return;
    router.delete(`/launch/${instance.id}`, { preserveScroll: true });
}

const flash = computed(() => usePage().props.flash?.success ?? null);

function statusClass(status) {
    return {
        provisioning: 'text-amber-400 bg-amber-400/10 border-amber-400/30',
        running: 'text-emerald-400 bg-emerald-400/10 border-emerald-400/30',
        stopped: 'text-zinc-400 bg-zinc-400/10 border-zinc-500/40',
        terminated: 'text-zinc-600 bg-zinc-600/10 border-zinc-700',
    }[status] ?? 'text-zinc-400 border-hairline';
}

const ESTIMATES = [
    { label: 'per hour', factor: 1 },
    { label: 'per day', factor: 24 },
    { label: 'per month (50% util)', factor: 360 },
];
</script>

<template>
    <Head title="Launch instance — Asyncindo" />

    <div class="min-h-screen bg-canvas text-zinc-100">
        <SiteHeader />

        <main class="mx-auto max-w-7xl px-5 py-12 sm:px-8 sm:py-16">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-acid">
                Deploy
            </p>
            <motion.h1 v-bind="enterProps()" class="mt-3 text-3xl font-semibold tracking-tight text-zinc-50 sm:text-4xl">
                Launch a GPU instance
            </motion.h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-zinc-400">
                Pick a accelerator, choose a configuration and region, and go live in minutes.
                Billed per second while the instance runs.
            </p>

            <!-- Flash -->
            <div
                v-if="flash"
                class="mt-6 rounded-md border border-acid/30 bg-acid/10 px-4 py-3 text-sm text-acid"
                role="status"
            >
                {{ flash }}
            </div>

            <!-- Stepper -->
            <ol class="mt-10 flex flex-wrap items-center gap-3 text-xs" aria-label="Launch steps">
                <li
                    v-for="(label, i) in ['Choose GPU', 'Configure', 'Review']"
                    :key="label"
                    class="flex items-center gap-2"
                >
                    <span
                        class="grid h-6 w-6 place-items-center rounded-full border text-[11px] font-semibold"
                        :class="
                            step > i
                                ? 'border-acid bg-acid text-canvas'
                                : step === i + 1
                                    ? 'border-acid text-acid'
                                    : 'border-hairline text-zinc-500'
                        "
                    >
                        {{ step > i + 1 ? '✓' : i + 1 }}
                    </span>
                    <span :class="step >= i + 1 ? 'text-zinc-200' : 'text-zinc-500'">{{ label }}</span>
                    <span v-if="i < 2" class="mx-1 text-zinc-700">—</span>
                </li>
            </ol>

            <!-- STEP 1: choose GPU -->
            <section v-if="step === 1" class="mt-8">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <motion.button
                        v-for="(plan, i) in plans"
                        :key="plan.id"
                        v-bind="staggerProps(i)"
                        type="button"
                        class="rounded-xl border border-hairline bg-panel p-6 text-left transition hover:border-acid/60"
                        :class="{ 'border-acid': selectedPlanId === plan.id }"
                        @click="pickPlan(plan)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-base font-semibold text-zinc-50">{{ plan.name }}</h2>
                            <p class="whitespace-nowrap text-sm font-semibold text-acid">
                                ${{ Number(plan.price_per_gpu_hour).toFixed(2) }}<span class="text-[10px] font-normal text-zinc-500">/gpu/hr</span>
                            </p>
                        </div>
                        <p class="mt-2 text-xs leading-relaxed text-zinc-500">{{ plan.description }}</p>
                        <dl class="mt-4 grid grid-cols-3 gap-2 text-[11px]">
                            <div>
                                <dt class="text-zinc-500">VRAM</dt>
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
                        </dl>
                        <p class="mt-4 inline-flex items-center gap-1 text-xs font-medium text-acid">
                            Select →
                        </p>
                    </motion.button>
                </div>
            </section>

            <!-- STEP 2: configure -->
            <section v-else-if="step === 2 && selectedPlan" class="mt-8 grid gap-6 lg:grid-cols-[3fr_2fr]">
                <motion.div v-bind="enterProps()" class="rounded-xl border border-hairline bg-panel p-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-zinc-100">
                            Configuration — <span class="text-acid">{{ selectedPlan.name }}</span>
                        </h2>
                        <button
                            type="button"
                            class="text-xs text-zinc-400 transition hover:text-acid"
                            @click="backToPlans"
                        >
                            ← Change GPU
                        </button>
                    </div>

                    <!-- GPU count -->
                    <p class="mt-5 text-xs font-medium uppercase tracking-widest text-zinc-500">GPU count</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="count in GPU_COUNTS"
                            :key="count"
                            type="button"
                            class="rounded-md border px-4 py-2 text-sm font-semibold transition"
                            :class="
                                selectedCount === count
                                    ? 'border-acid bg-acid/10 text-acid'
                                    : 'border-hairline text-zinc-400 hover:border-zinc-600 hover:text-zinc-100'
                            "
                            @click="selectedCount = count; selectedOfferingId = null; form.offering_id = null"
                        >
                            {{ count }}× GPU
                        </button>
                    </div>

                    <!-- Region -->
                    <p class="mt-6 text-xs font-medium uppercase tracking-widest text-zinc-500">Region</p>
                    <div class="mt-2 space-y-2">
                        <motion.button
                            v-for="(offering, i) in selectedOfferings"
                            :key="offering.id"
                            v-bind="staggerProps(i, { duration: 0.15 })"
                            type="button"
                            :disabled="offering.stock < 1"
                            class="flex w-full items-center justify-between rounded-lg border px-4 py-3 text-left transition disabled:cursor-not-allowed disabled:opacity-50"
                            :class="
                                selectedOfferingId === offering.id
                                    ? 'border-acid bg-acid/5'
                                    : 'border-hairline hover:border-zinc-600'
                            "
                            @click="pickOffering(offering)"
                        >
                            <span>
                                <span class="block text-sm font-medium text-zinc-100">{{ offering.region }}</span>
                                <span class="text-[11px] text-zinc-500">
                                    {{ offering.stock > 0 ? `${offering.stock} available` : 'out of stock' }}
                                </span>
                            </span>
                            <span class="text-right">
                                <span class="block text-sm font-semibold text-zinc-100">${{ offering.hourly.toFixed(2) }}/hr</span>
                                <span class="text-[11px] text-zinc-500">${{ offering.daily.toFixed(2) }}/day</span>
                            </span>
                        </motion.button>
                        <p v-if="selectedOfferings.length === 0" class="rounded-lg border border-hairline bg-canvas px-4 py-6 text-center text-xs text-zinc-500">
                            No {{ selectedCount }}× GPU configurations for this model. Try another count.
                        </p>
                    </div>

                    <!-- Instance name + SSH key -->
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="instance-name" class="text-xs font-medium uppercase tracking-widest text-zinc-500">
                                Instance name
                            </label>
                            <input
                                id="instance-name"
                                v-model="form.name"
                                type="text"
                                maxlength="64"
                                class="mt-2 w-full rounded-md border bg-canvas px-3 py-2 font-mono text-sm outline-none transition focus:border-acid"
                                :class="form.errors.name ? 'border-red-500' : 'border-hairline text-zinc-200'"
                                placeholder="my-training-run"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-[11px] text-red-400">{{ form.errors.name }}</p>
                            <p v-else class="mt-1 text-[11px] text-zinc-600">Lowercase letters, numbers, hyphens.</p>
                        </div>
                        <div>
                            <label for="ssh-key" class="text-xs font-medium uppercase tracking-widest text-zinc-500">
                                SSH key <span class="normal-case text-zinc-600">(optional)</span>
                            </label>
                            <input
                                id="ssh-key"
                                v-model="form.ssh_key_name"
                                type="text"
                                maxlength="64"
                                class="mt-2 w-full rounded-md border bg-canvas px-3 py-2 text-sm outline-none transition focus:border-acid"
                                :class="form.errors.ssh_key_name ? 'border-red-500' : 'border-hairline text-zinc-200'"
                                placeholder="my-laptop-key"
                            />
                            <p v-if="form.errors.ssh_key_name" class="mt-1 text-[11px] text-red-400">{{ form.errors.ssh_key_name }}</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        :disabled="!selectedOfferingId || form.processing"
                        class="mt-6 w-full rounded-md bg-acid px-5 py-3 text-sm font-semibold text-canvas transition hover:brightness-95 disabled:cursor-not-allowed disabled:opacity-40"
                        @click="step = 3"
                    >
                        Review & launch →
                    </button>
                </motion.div>

                <!-- Live cost summary -->
                <motion.aside v-bind="enterProps({ delay: 0.1 })" class="h-fit rounded-xl border border-hairline bg-panel p-6 lg:sticky lg:top-24">
                    <h2 class="text-sm font-semibold text-zinc-100">Cost estimate</h2>
                    <template v-if="selectedOffering">
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-zinc-500">{{ selectedPlan.name }} × {{ selectedOffering.gpu_count }}</span>
                                <span class="text-zinc-300">${{ selectedOffering.hourly.toFixed(2) }}/hr</span>
                            </div>
                            <div class="flex justify-between border-t border-hairline pt-3 text-xs">
                                <span class="text-zinc-500">Region</span>
                                <span class="font-mono text-zinc-300">{{ selectedOffering.region }}</span>
                            </div>
                            <div v-for="estimate in ESTIMATES" :key="estimate.label" class="flex justify-between text-xs">
                                <span class="text-zinc-500">{{ estimate.label }}</span>
                                <span class="tabular-nums text-zinc-300">
                                    ${{ (selectedOffering.hourly * estimate.factor).toFixed(2) }}
                                </span>
                            </div>
                        </div>
                        <p class="mt-4 rounded-md bg-canvas px-3 py-2 text-[11px] leading-relaxed text-zinc-500">
                            Billed per second while running. Stop or terminate anytime — you only pay for what you use.
                        </p>
                    </template>
                    <p v-else class="mt-4 text-xs text-zinc-500">
                        Select a GPU count and region to see pricing.
                    </p>
                </motion.aside>
            </section>

            <!-- STEP 3: review -->
            <section v-else-if="step === 3 && selectedPlan && selectedOffering" class="mt-8 max-w-2xl">
                <motion.div v-bind="enterProps()" class="rounded-xl border border-hairline bg-panel p-6 sm:p-8">
                    <h2 class="text-lg font-semibold text-zinc-50">Review your launch</h2>
                    <dl class="mt-6 divide-y divide-hairline text-sm">
                        <div class="flex justify-between py-3">
                            <dt class="text-zinc-500">GPU model</dt>
                            <dd class="font-medium text-zinc-100">{{ selectedPlan.name }}</dd>
                        </div>
                        <div class="flex justify-between py-3">
                            <dt class="text-zinc-500">Configuration</dt>
                            <dd class="font-medium text-zinc-100">{{ selectedOffering.gpu_count }}× GPU · {{ selectedPlan.vram_gb }} GB VRAM each</dd>
                        </div>
                        <div class="flex justify-between py-3">
                            <dt class="text-zinc-500">Region</dt>
                            <dd class="font-mono text-zinc-100">{{ selectedOffering.region }}</dd>
                        </div>
                        <div class="flex justify-between py-3">
                            <dt class="text-zinc-500">Instance name</dt>
                            <dd class="font-mono text-zinc-100">{{ form.name }}</dd>
                        </div>
                        <div v-if="form.ssh_key_name" class="flex justify-between py-3">
                            <dt class="text-zinc-500">SSH key</dt>
                            <dd class="font-mono text-zinc-100">{{ form.ssh_key_name }}</dd>
                        </div>
                        <div class="flex justify-between py-3">
                            <dt class="text-zinc-500">Price</dt>
                            <dd class="font-semibold text-acid">${{ selectedOffering.hourly.toFixed(2) }} / hour</dd>
                        </div>
                    </dl>

                    <p v-if="form.errors.offering_id" class="mt-4 rounded-md border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-400">
                        {{ form.errors.offering_id }}
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <button
                            type="button"
                            :disabled="form.processing"
                            class="rounded-md bg-acid px-6 py-3 text-sm font-semibold text-canvas transition hover:brightness-95 disabled:opacity-40"
                            @click="launch"
                        >
                            {{ form.processing ? 'Provisioning…' : 'Launch instance' }}
                        </button>
                        <button
                            type="button"
                            class="rounded-md border border-hairline px-5 py-3 text-sm font-medium text-zinc-300 transition hover:border-zinc-600"
                            @click="backToConfig"
                        >
                            ← Back
                        </button>
                    </div>
                </motion.div>
                <p class="mt-4 text-xs text-zinc-600">
                    By launching you agree to the Asyncindo terms. Per-second billing starts when the instance is running.
                </p>
            </section>

            <!-- My instances -->
            <motion.section v-bind="enterProps({ delay: 0.1 })" v-if="myInstances.length" class="mt-16">
                <h2 class="text-lg font-semibold text-zinc-50">Your instances</h2>
                <div class="mt-4 overflow-hidden rounded-xl border border-hairline bg-panel">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[720px] text-left text-sm">
                            <thead class="text-[10px] uppercase tracking-widest text-zinc-500">
                                <tr class="border-b border-hairline">
                                    <th class="px-5 py-3 font-medium">Name</th>
                                    <th class="px-4 py-3 font-medium">GPU</th>
                                    <th class="px-4 py-3 font-medium">Region</th>
                                    <th class="px-4 py-3 font-medium">IP</th>
                                    <th class="px-4 py-3 font-medium">Status</th>
                                    <th class="px-4 py-3 font-medium">Launched</th>
                                    <th class="px-5 py-3 text-right font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-hairline text-zinc-300">
                                <motion.tr
                                    v-for="(instance, i) in myInstances"
                                    :key="instance.id"
                                    v-bind="staggerProps(i)"
                                >
                                    <td class="px-5 py-3.5 font-mono text-[13px]">{{ instance.name }}</td>
                                    <td class="px-4 py-3.5">{{ instance.model }} ×{{ instance.gpu_count }}</td>
                                    <td class="px-4 py-3.5 font-mono text-xs">{{ instance.region }}</td>
                                    <td class="px-4 py-3.5 font-mono text-xs">{{ instance.public_ip ?? '—' }}</td>
                                    <td class="px-4 py-3.5">
                                        <span
                                            class="rounded border px-1.5 py-0.5 text-[10px] capitalize"
                                            :class="statusClass(instance.status)"
                                        >
                                            {{ instance.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-xs text-zinc-500">{{ instance.launched_at }}</td>
                                    <td class="px-5 py-3.5 text-right">
                                        <button
                                            v-if="instance.status === 'running'"
                                            type="button"
                                            class="mr-3 text-xs text-zinc-400 transition hover:text-amber-400"
                                            @click="router.put(`/launch/${instance.id}/stop`, {}, { preserveScroll: true })"
                                        >
                                            Stop
                                        </button>
                                        <button
                                            v-if="instance.status !== 'terminated'"
                                            type="button"
                                            class="text-xs text-zinc-400 transition hover:text-red-400"
                                            @click="terminate(instance)"
                                        >
                                            Terminate
                                        </button>
                                    </td>
                                </motion.tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </motion.section>
        </main>

        <SiteFooter />
    </div>
</template>
