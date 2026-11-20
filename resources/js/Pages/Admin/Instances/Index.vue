<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AnimatePresence, motion } from 'motion-v';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { MOTION, enterProps, hoverProps } from '../../../utils/motion';

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
});

const hasPlans = computed(() => props.plans.length > 0);

function destroy(plan) {
    if (!window.confirm(`Delete ${plan.name}? This cannot be undone.`)) {
        return;
    }

    router.delete(`/admin/instances/${plan.id}`, {
        onFinish: () => router.reload(),
    });
}
</script>

<template>
    <Head title="Instances — admin" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-100">Instances</h1>
                <p class="mt-1 text-sm text-zinc-500">
                    GPU plans and their instance offerings.
                </p>
            </div>
            <Link
                href="/admin/instances/create"
                class="rounded-md bg-acid px-4 py-2 text-sm font-semibold text-canvas transition hover:brightness-95"
            >
                New plan
            </Link>
        </div>

        <div class="mt-8 overflow-x-auto rounded-lg border border-hairline bg-panel">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-hairline text-[11px] uppercase tracking-widest text-zinc-500">
                    <tr>
                        <th class="px-4 py-3 font-medium">Plan</th>
                        <th class="px-4 py-3 font-medium">Price / GPU / hr</th>
                        <th class="px-4 py-3 font-medium">Variants</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-hairline">
                    <tr v-if="!hasPlans">
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500">
                            No plans yet. Create the first one.
                        </td>
                    </tr>

                    <AnimatePresence>
                    <motion.tr
                        v-for="plan in plans"
                        :key="plan.id"
                        v-bind="enterProps()"
                        :exit="MOTION.exit"
                        class="hover:bg-canvas/60"
                    >
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img
                                    v-if="plan.image_url"
                                    :src="plan.image_url"
                                    :alt="plan.name"
                                    class="h-10 w-10 shrink-0 rounded border border-hairline object-cover"
                                />
                                <span
                                    v-else
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded border border-hairline bg-canvas text-[10px] uppercase tracking-widest text-zinc-600"
                                    aria-hidden="true"
                                >
                                    GPU
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-zinc-100">{{ plan.name }}</p>
                                    <p class="truncate text-xs text-zinc-500">{{ plan.slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-zinc-200">
                            ${{ Number(plan.price_per_gpu_hour).toFixed(2) }}
                        </td>
                        <td class="px-4 py-3 text-zinc-300">{{ plan.offerings_count }}</td>
                        <td class="px-4 py-3">
                            <span
                                v-if="plan.is_active"
                                class="rounded border border-hairline px-2 py-0.5 text-xs text-acid"
                            >
                                Active
                            </span>
                            <span
                                v-else
                                class="rounded border border-hairline px-2 py-0.5 text-xs text-zinc-500"
                            >
                                Inactive
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <Link
                                :href="`/admin/instances/${plan.id}/edit`"
                                class="text-sm text-zinc-300 transition hover:text-acid"
                            >
                                Edit
                            </Link>
                            <motion.button
                                type="button"
                                v-bind="hoverProps()"
                                class="ml-4 text-sm text-zinc-500 transition hover:text-red-400"
                                @click="destroy(plan)"
                            >
                                Delete
                            </motion.button>
                        </td>
                    </motion.tr>
                    </AnimatePresence>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
