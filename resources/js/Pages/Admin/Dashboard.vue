<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    plansCount: Number,
    offeringsCount: Number,
    inactiveCount: Number,
});

const stats = computed(() => [
    { label: 'GPU plans', value: props.plansCount },
    { label: 'Instance offerings', value: props.offeringsCount },
    { label: 'Inactive plans', value: props.inactiveCount },
]);
</script>

<template>
    <Head title="Admin dashboard" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-zinc-100">Dashboard</h1>
                <p class="mt-1 text-sm text-zinc-500">Catalogue health at a glance.</p>
            </div>
            <Link
                href="/admin/instances"
                class="rounded-md border border-hairline px-4 py-2 text-sm font-medium text-zinc-200 transition hover:border-acid hover:text-acid"
            >
                Manage instances
            </Link>
        </div>

        <div class="mt-8 grid gap-4 sm:grid-cols-3">
            <div
                v-for="stat in stats"
                :key="stat.label"
                class="rounded-lg border border-hairline bg-panel p-5"
            >
                <p class="text-xs font-medium uppercase tracking-widest text-zinc-500">
                    {{ stat.label }}
                </p>
                <p class="mt-3 text-3xl font-semibold text-acid">{{ stat.value }}</p>
            </div>
        </div>
    </AdminLayout>
</template>
