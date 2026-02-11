<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ToothSelector from '@/Components/Treatment/ToothSelector.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import type { Treatment } from '@/types';

const props = withDefaults(
    defineProps<{
        encounterId: number;
        treatment?: Treatment;
    }>(),
    {
        treatment: undefined,
    },
);

const emit = defineEmits<{
    saved: [];
    cancelled: [];
}>();

const { t } = useI18n();

const isEditing = computed(() => !!props.treatment);
const showToothSelector = ref(false);

const form = useForm({
    tooth_number: props.treatment?.tooth_number ?? '',
    treatment_code: props.treatment?.treatment_code ?? '',
    description: props.treatment?.description ?? '',
    surface: props.treatment?.surface ?? '',
    cost: props.treatment?.cost ?? 0,
    status: props.treatment?.status ?? 'planned',
    notes: props.treatment?.notes ?? '',
});

const surfaces: { value: Treatment['surface'] | ''; label: string }[] = [
    { value: '', label: '—' },
    { value: 'mesial', label: t('treatment.surface_mesial') },
    { value: 'distal', label: t('treatment.surface_distal') },
    { value: 'buccal', label: t('treatment.surface_buccal') },
    { value: 'lingual', label: t('treatment.surface_lingual') },
    { value: 'occlusal', label: t('treatment.surface_occlusal') },
    { value: 'incisal', label: t('treatment.surface_incisal') },
];

const statuses: { value: Treatment['status']; label: string }[] = [
    { value: 'planned', label: t('treatment.status_planned') },
    { value: 'in_progress', label: t('treatment.status_in_progress') },
    { value: 'completed', label: t('treatment.status_completed') },
];

function onToothSelected(value: string | null) {
    form.tooth_number = value ?? '';
    showToothSelector.value = false;
}

function submit() {
    const data = {
        ...form.data(),
        surface: form.surface || null,
        tooth_number: form.tooth_number || null,
        cost: form.cost ? Number(form.cost) : null,
    };

    if (isEditing.value && props.treatment) {
        form.transform(() => data).put(
            route('treatments.update', props.treatment!.id),
            {
                preserveScroll: true,
                onSuccess: () => emit('saved'),
            },
        );
    } else {
        form.transform(() => data).post(
            route('treatments.store', props.encounterId),
            {
                preserveScroll: true,
                onSuccess: () => {
                    form.reset();
                    emit('saved');
                },
            },
        );
    }
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-4">
        <!-- Tooth Number -->
        <div>
            <InputLabel :value="t('treatment.tooth_number')" />
            <div class="mt-1 flex items-center gap-2">
                <input
                    type="text"
                    :value="form.tooth_number"
                    readonly
                    :placeholder="t('treatment.select_tooth')"
                    class="block w-full rounded-md border-gray-300 bg-gray-50 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    @click="showToothSelector = !showToothSelector"
                />
                <button
                    type="button"
                    class="inline-flex shrink-0 items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    @click="showToothSelector = !showToothSelector"
                >
                    {{ t('treatment.select_tooth') }}
                </button>
            </div>
            <InputError :message="form.errors.tooth_number" class="mt-1" />

            <!-- Tooth selector panel -->
            <div v-if="showToothSelector" class="mt-2">
                <ToothSelector
                    :model-value="form.tooth_number || null"
                    @update:model-value="onToothSelected"
                />
            </div>
        </div>

        <!-- Treatment Code -->
        <div>
            <InputLabel :value="t('treatment.treatment_code')" />
            <input
                v-model="form.treatment_code"
                type="text"
                required
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
            <InputError :message="form.errors.treatment_code" class="mt-1" />
        </div>

        <!-- Description -->
        <div>
            <InputLabel :value="t('treatment.description')" />
            <textarea
                v-model="form.description"
                required
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
            <InputError :message="form.errors.description" class="mt-1" />
        </div>

        <!-- Surface and Cost row -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <!-- Surface -->
            <div>
                <InputLabel :value="t('treatment.surface')" />
                <select
                    v-model="form.surface"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option
                        v-for="surface in surfaces"
                        :key="surface.value"
                        :value="surface.value"
                    >
                        {{ surface.label }}
                    </option>
                </select>
                <InputError :message="form.errors.surface" class="mt-1" />
            </div>

            <!-- Cost -->
            <div>
                <InputLabel :value="t('treatment.cost')" />
                <input
                    v-model.number="form.cost"
                    type="number"
                    min="0"
                    step="0.01"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                />
                <InputError :message="form.errors.cost" class="mt-1" />
            </div>
        </div>

        <!-- Status -->
        <div>
            <InputLabel :value="t('treatment.status')" />
            <select
                v-model="form.status"
                required
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </select>
            <InputError :message="form.errors.status" class="mt-1" />
        </div>

        <!-- Notes -->
        <div>
            <InputLabel :value="t('treatment.notes')" />
            <textarea
                v-model="form.notes"
                rows="2"
                class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
            <InputError :message="form.errors.notes" class="mt-1" />
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <button
                type="button"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                @click="emit('cancelled')"
            >
                {{ t('app.cancel') }}
            </button>
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
            >
                {{ form.processing ? t('app.loading') : t('app.save') }}
            </button>
        </div>
    </form>
</template>
