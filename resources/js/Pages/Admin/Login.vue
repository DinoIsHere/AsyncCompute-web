<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
});

function submit() {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Admin log in" />

    <div class="flex min-h-screen items-center justify-center bg-canvas px-5 py-12">
        <div class="w-full max-w-sm rounded-lg border border-hairline bg-panel p-7 shadow-2xl">
            <div class="mb-7 flex items-center gap-3">
                <span class="inline-block h-3 w-3 rotate-45 bg-acid" aria-hidden="true"></span>
                <span class="text-sm font-semibold uppercase tracking-[0.2em] text-zinc-100">
                    asyncindo
                </span>
            </div>

            <h1 class="text-xl font-semibold text-zinc-100">Admin log in</h1>
            <p class="mt-1 text-sm text-zinc-500">Manage the GPU catalogue.</p>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label
                        for="email"
                        class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-400"
                    >
                        Email
                    </label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="username"
                        autofocus
                        class="w-full rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-400">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label
                        for="password"
                        class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-400"
                    >
                        Password
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        autocomplete="current-password"
                        class="w-full rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-400">
                        {{ form.errors.password }}
                    </p>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-md bg-acid px-4 py-2 text-sm font-semibold text-canvas transition hover:brightness-95 disabled:opacity-60"
                    :disabled="form.processing"
                >
                    Log in
                </button>
            </form>
        </div>
    </div>
</template>
