<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Form from '@/Pages/Admin/Instances/Form.vue';

defineProps({
    plan: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head :title="`Edit ${plan.name} — admin`" />

    <AdminLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-widest text-zinc-500">Instances</p>
                <h1 class="mt-1 text-2xl font-semibold text-zinc-100">Edit plan</h1>
            </div>
            <Link
                href="/admin/instances"
                class="text-sm text-zinc-400 transition hover:text-acid"
            >
                Back to catalogue
            </Link>
        </div>

        <div class="mt-8">
            <Form :plan="plan" />
        </div>
    </AdminLayout>
</template>
