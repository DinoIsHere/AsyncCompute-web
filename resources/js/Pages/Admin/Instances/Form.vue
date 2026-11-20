<script setup>
import { computed, ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    plan: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const isEditing = computed(() => props.plan !== null);

const existingOfferings = props.plan?.offerings ?? [];

const form = useForm({
    name: props.plan?.name ?? '',
    vram_gb: props.plan?.vram_gb ?? '',
    vcpus: props.plan?.vcpus ?? '',
    ram_gb: props.plan?.ram_gb ?? '',
    storage: props.plan?.storage ?? '',
    price_per_gpu_hour: props.plan?.price_per_gpu_hour ?? '',
    description: props.plan?.description ?? '',
    is_active: props.plan ? Boolean(props.plan.is_active) : true,
    image: null,
    offerings: existingOfferings.length
        ? existingOfferings.map((offering) => ({
              gpu_count: offering.gpu_count,
              region: offering.region,
              stock: offering.stock,
          }))
        : [{ gpu_count: 1, region: 'us-east-1', stock: 0 }],
});

const imageName = ref('');

const gpuCounts = [1, 2, 4, 8];

function error(key) {
    return page.props.errors?.[key];
}

function addOffering() {
    form.offerings.push({ gpu_count: 1, region: 'us-east-1', stock: 0 });
}

function removeOffering(index) {
    form.offerings.splice(index, 1);
}

function onImageChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.image = file;
    imageName.value = file ? file.name : '';
}

function submit() {
    if (isEditing.value) {
        form
            .transform((data) => ({ ...data, _method: 'put' }))
            .post(`/admin/instances/${props.plan.id}`, { forceFormData: true });
        return;
    }

    form.post('/admin/instances', { forceFormData: true });
}
</script>

<template>
    <form class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]" @submit.prevent="submit">
        <!-- Plan details -->
        <section class="rounded-lg border border-hairline bg-panel p-5">
            <h2 class="text-sm font-semibold uppercase tracking-widest text-zinc-400">Plan</h2>

            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500">
                        Name
                    </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                    />
                    <p v-if="error('name')" class="mt-1 text-xs text-red-400">{{ error('name') }}</p>
                </div>

                <div>
                    <label for="vram_gb" class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500">
                        VRAM / GPU (GB)
                    </label>
                    <input
                        id="vram_gb"
                        v-model="form.vram_gb"
                        type="number"
                        step="0.01"
                        min="1"
                        class="w-full rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                    />
                    <p v-if="error('vram_gb')" class="mt-1 text-xs text-red-400">{{ error('vram_gb') }}</p>
                </div>

                <div>
                    <label for="vcpus" class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500">
                        vCPUs
                    </label>
                    <input
                        id="vcpus"
                        v-model="form.vcpus"
                        type="number"
                        min="1"
                        class="w-full rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                    />
                    <p v-if="error('vcpus')" class="mt-1 text-xs text-red-400">{{ error('vcpus') }}</p>
                </div>

                <div>
                    <label for="ram_gb" class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500">
                        RAM (GB)
                    </label>
                    <input
                        id="ram_gb"
                        v-model="form.ram_gb"
                        type="number"
                        min="1"
                        class="w-full rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                    />
                    <p v-if="error('ram_gb')" class="mt-1 text-xs text-red-400">{{ error('ram_gb') }}</p>
                </div>

                <div>
                    <label for="price_per_gpu_hour" class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500">
                        Price / GPU / hr
                    </label>
                    <input
                        id="price_per_gpu_hour"
                        v-model="form.price_per_gpu_hour"
                        type="number"
                        step="0.01"
                        min="0"
                        class="w-full rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                    />
                    <p v-if="error('price_per_gpu_hour')" class="mt-1 text-xs text-red-400">
                        {{ error('price_per_gpu_hour') }}
                    </p>
                </div>

                <div class="sm:col-span-2">
                    <label for="storage" class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500">
                        Storage
                    </label>
                    <input
                        id="storage"
                        v-model="form.storage"
                        type="text"
                        placeholder="2x 3.84 TB NVMe"
                        class="w-full rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                    />
                    <p v-if="error('storage')" class="mt-1 text-xs text-red-400">{{ error('storage') }}</p>
                </div>

                <div class="sm:col-span-2">
                    <label for="description" class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500">
                        Description
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                    ></textarea>
                    <p v-if="error('description')" class="mt-1 text-xs text-red-400">{{ error('description') }}</p>
                </div>

                <div class="sm:col-span-2">
                    <label for="image" class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500">
                        {{ isEditing ? 'Replace image' : 'Image' }}
                    </label>

                    <img
                        v-if="plan?.image_url"
                        :src="plan.image_url"
                        :alt="plan.name"
                        class="mb-3 h-20 w-20 rounded border border-hairline object-cover"
                    />

                    <input
                        id="image"
                        type="file"
                        accept="image/png,image/jpeg,image/webp"
                        class="block w-full cursor-pointer rounded-md border border-hairline bg-canvas px-3 py-2 text-sm text-zinc-400 file:mr-3 file:rounded file:border-0 file:bg-hairline file:px-3 file:py-1 file:text-xs file:font-medium file:text-zinc-100"
                        @change="onImageChange"
                    />
                    <p v-if="imageName" class="mt-1 text-xs text-zinc-500">{{ imageName }}</p>
                    <p v-if="error('image')" class="mt-1 text-xs text-red-400">{{ error('image') }}</p>
                </div>

                <div class="sm:col-span-2">
                    <!-- Unchecked checkboxes send nothing; keep a "0" for the boolean rule. -->
                    <input type="hidden" name="is_active" value="0" />
                    <label class="flex items-center gap-2 text-sm text-zinc-300">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-hairline bg-canvas text-acid focus:ring-acid"
                        />
                        Active — visible on the public instances page
                    </label>
                    <p v-if="error('is_active')" class="mt-1 text-xs text-red-400">{{ error('is_active') }}</p>
                </div>
            </div>
        </section>

        <!-- Offerings -->
        <section class="rounded-lg border border-hairline bg-panel p-5">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold uppercase tracking-widest text-zinc-400">
                    Instance offerings
                </h2>
                <button
                    type="button"
                    class="rounded-md border border-hairline px-3 py-1.5 text-xs font-medium text-zinc-200 transition hover:border-acid hover:text-acid"
                    @click="addOffering"
                >
                    Add offering
                </button>
            </div>

            <p v-if="error('offerings')" class="mt-3 text-xs text-red-400">{{ error('offerings') }}</p>

            <div class="mt-4 space-y-4">
                <div
                    v-for="(offering, index) in form.offerings"
                    :key="index"
                    class="rounded-md border border-hairline bg-canvas p-4"
                >
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label
                                :for="`gpu_count_${index}`"
                                class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500"
                            >
                                GPUs
                            </label>
                            <select
                                :id="`gpu_count_${index}`"
                                v-model="offering.gpu_count"
                                class="w-full rounded-md border border-hairline bg-panel px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                            >
                                <option v-for="count in gpuCounts" :key="count" :value="count">
                                    {{ count }}x
                                </option>
                            </select>
                            <p v-if="error(`offerings.${index}.gpu_count`)" class="mt-1 text-xs text-red-400">
                                {{ error(`offerings.${index}.gpu_count`) }}
                            </p>
                        </div>

                        <div>
                            <label
                                :for="`region_${index}`"
                                class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500"
                            >
                                Region
                            </label>
                            <input
                                :id="`region_${index}`"
                                v-model="offering.region"
                                type="text"
                                placeholder="us-east-1"
                                class="w-full rounded-md border border-hairline bg-panel px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                            />
                            <p v-if="error(`offerings.${index}.region`)" class="mt-1 text-xs text-red-400">
                                {{ error(`offerings.${index}.region`) }}
                            </p>
                        </div>

                        <div>
                            <label
                                :for="`stock_${index}`"
                                class="mb-1 block text-xs font-medium uppercase tracking-widest text-zinc-500"
                            >
                                Stock
                            </label>
                            <input
                                :id="`stock_${index}`"
                                v-model="offering.stock"
                                type="number"
                                min="0"
                                class="w-full rounded-md border border-hairline bg-panel px-3 py-2 text-sm text-zinc-100 outline-none transition focus:border-acid"
                            />
                            <p v-if="error(`offerings.${index}.stock`)" class="mt-1 text-xs text-red-400">
                                {{ error(`offerings.${index}.stock`) }}
                            </p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="mt-3 text-xs text-zinc-500 transition hover:text-red-400 disabled:opacity-40"
                        :disabled="form.offerings.length === 1"
                        @click="removeOffering(index)"
                    >
                        Remove
                    </button>
                </div>
            </div>
        </section>

        <div class="flex items-center justify-end gap-4 lg:col-span-2">
            <p v-if="form.hasErrors" class="text-xs text-red-400">Fix the highlighted fields.</p>
            <button
                type="submit"
                class="rounded-md bg-acid px-5 py-2 text-sm font-semibold text-canvas transition hover:brightness-95 disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ isEditing ? 'Save changes' : 'Create plan' }}
            </button>
        </div>
    </form>
</template>
