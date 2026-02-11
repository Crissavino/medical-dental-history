<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useToothNotation } from '@/Composables/useToothNotation';
import type { Encounter, Patient } from '@/types';
import {
    PlusIcon,
    TrashIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const page = usePage();
const { allTeeth, upperTeeth, lowerTeeth, getToothName, quadrants } = useToothNotation();

const props = defineProps<{
    encounter?: Encounter;
    patient?: Patient;
}>();

const isEditing = computed(() => !!props.encounter);

const patientFromRoute = computed(() => {
    return props.patient || props.encounter?.patient;
});

interface TreatmentForm {
    id?: number;
    tooth_number: string;
    treatment_code: string;
    description: string;
    notes: string;
    surface: string;
    cost: string;
    status: 'planned' | 'in_progress' | 'completed';
}

const form = useForm({
    patient_id: props.encounter?.patient_id || props.patient?.id || (page.props as Record<string, unknown>).patient_id || '',
    encounter_date: props.encounter?.encounter_date?.substring(0, 10) || new Date().toISOString().substring(0, 10),
    status: props.encounter?.status || ('scheduled' as const),
    chief_complaint: props.encounter?.chief_complaint || '',
    clinical_notes: props.encounter?.clinical_notes || '',
    diagnosis: props.encounter?.diagnosis || '',
    treatments: (props.encounter?.treatments || []).map((t) => ({
        id: t.id,
        tooth_number: t.tooth_number || '',
        treatment_code: t.treatment_code,
        description: t.description,
        notes: t.notes || '',
        surface: t.surface || '',
        cost: t.cost !== undefined && t.cost !== null ? String(t.cost) : '',
        status: t.status,
    })) as TreatmentForm[],
});

const showToothSelector = ref(false);
const currentTreatmentIndex = ref<number | null>(null);

function addTreatment() {
    form.treatments.push({
        tooth_number: '',
        treatment_code: '',
        description: '',
        notes: '',
        surface: '',
        cost: '',
        status: 'planned',
    });
}

function removeTreatment(index: number) {
    form.treatments.splice(index, 1);
}

function openToothSelector(index: number) {
    currentTreatmentIndex.value = index;
    showToothSelector.value = true;
}

function selectTooth(toothNumber: string) {
    if (currentTreatmentIndex.value !== null) {
        form.treatments[currentTreatmentIndex.value].tooth_number = toothNumber;
    }
    showToothSelector.value = false;
    currentTreatmentIndex.value = null;
}

function submit() {
    if (isEditing.value && props.encounter) {
        form.put(route('encounters.update', props.encounter.id));
    } else {
        form.post(route('patient.encounters.store', form.patient_id as any));
    }
}

const surfaces = [
    { value: 'mesial', label: () => t('treatment.surface_mesial') },
    { value: 'distal', label: () => t('treatment.surface_distal') },
    { value: 'buccal', label: () => t('treatment.surface_buccal') },
    { value: 'lingual', label: () => t('treatment.surface_lingual') },
    { value: 'occlusal', label: () => t('treatment.surface_occlusal') },
    { value: 'incisal', label: () => t('treatment.surface_incisal') },
];
</script>

<template>
    <Head :title="isEditing ? t('encounter.edit') : t('encounter.new')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="isEditing && encounter ? route('encounters.show', encounter.id) : route('patients.show', form.patient_id as any)"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h1 class="text-lg font-semibold text-gray-900">
                    {{ isEditing ? t('encounter.edit') : t('encounter.new') }}
                    <span v-if="patientFromRoute" class="text-gray-500">
                        - {{ patientFromRoute.full_name || `${patientFromRoute.first_name} ${patientFromRoute.last_name}` }}
                    </span>
                </h1>
            </div>
        </template>

        <div class="mx-auto max-w-4xl">
            <form @submit.prevent="submit" class="space-y-8">
                <!-- Encounter details -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">{{ t('encounter.details') }}</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel :value="t('encounter.date')" />
                            <input
                                v-model="form.encounter_date"
                                type="date"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                required
                            />
                            <InputError :message="form.errors.encounter_date" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel :value="t('encounter.status')" />
                            <select
                                v-model="form.status"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                required
                            >
                                <option value="scheduled">{{ t('encounter.status_scheduled') }}</option>
                                <option value="in_progress">{{ t('encounter.status_in_progress') }}</option>
                                <option value="completed">{{ t('encounter.status_completed') }}</option>
                                <option value="cancelled">{{ t('encounter.status_cancelled') }}</option>
                            </select>
                            <InputError :message="form.errors.status" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel :value="t('encounter.chief_complaint')" />
                            <input
                                v-model="form.chief_complaint"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <InputError :message="form.errors.chief_complaint" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel :value="t('encounter.clinical_notes')" />
                            <textarea
                                v-model="form.clinical_notes"
                                rows="4"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <InputError :message="form.errors.clinical_notes" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel :value="t('encounter.diagnosis')" />
                            <textarea
                                v-model="form.diagnosis"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <InputError :message="form.errors.diagnosis" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Treatments -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">{{ t('treatment.title') }}</h2>
                        <button
                            type="button"
                            @click="addTreatment"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            {{ t('encounter.add_treatment') }}
                        </button>
                    </div>

                    <div v-if="form.treatments.length > 0" class="divide-y divide-gray-100">
                        <div
                            v-for="(treatment, index) in form.treatments"
                            :key="index"
                            class="p-6"
                        >
                            <div class="mb-4 flex items-center justify-between">
                                <span class="text-sm font-semibold text-gray-700">
                                    {{ t('treatment.title') }} #{{ index + 1 }}
                                </span>
                                <button
                                    type="button"
                                    @click="removeTreatment(index)"
                                    class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors"
                                >
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <InputLabel :value="t('treatment.tooth_number')" />
                                    <div class="mt-1 flex gap-2">
                                        <input
                                            v-model="treatment.tooth_number"
                                            type="text"
                                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                            :placeholder="t('treatment.tooth_number')"
                                        />
                                        <button
                                            type="button"
                                            @click="openToothSelector(index)"
                                            class="shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                                        >
                                            {{ t('treatment.select_tooth') }}
                                        </button>
                                    </div>
                                    <p v-if="treatment.tooth_number" class="mt-1 text-xs text-gray-500">
                                        {{ getToothName(treatment.tooth_number) }}
                                    </p>
                                </div>
                                <div>
                                    <InputLabel :value="t('treatment.treatment_code')" />
                                    <input
                                        v-model="treatment.treatment_code"
                                        type="text"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                        required
                                    />
                                </div>
                                <div>
                                    <InputLabel :value="t('treatment.status')" />
                                    <select
                                        v-model="treatment.status"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                    >
                                        <option value="planned">{{ t('treatment.status_planned') }}</option>
                                        <option value="in_progress">{{ t('treatment.status_in_progress') }}</option>
                                        <option value="completed">{{ t('treatment.status_completed') }}</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <InputLabel :value="t('treatment.description')" />
                                    <input
                                        v-model="treatment.description"
                                        type="text"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                        required
                                    />
                                </div>
                                <div>
                                    <InputLabel :value="t('treatment.surface')" />
                                    <select
                                        v-model="treatment.surface"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                    >
                                        <option value="">---</option>
                                        <option v-for="s in surfaces" :key="s.value" :value="s.value">{{ s.label() }}</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel :value="t('treatment.cost')" />
                                    <input
                                        v-model="treatment.cost"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                    />
                                </div>
                                <div class="sm:col-span-2">
                                    <InputLabel :value="t('treatment.notes')" />
                                    <input
                                        v-model="treatment.notes"
                                        type="text"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-6 text-center text-sm text-gray-500">
                        {{ t('treatment.no_treatments') }}
                    </div>
                </div>

                <!-- Form actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="isEditing && encounter ? route('encounters.show', encounter.id) : route('patients.show', form.patient_id as any)"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        {{ t('app.cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ t('app.save') }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Tooth Selector Modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showToothSelector" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                    <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ t('treatment.select_tooth') }}</h3>
                            <button
                                type="button"
                                @click="showToothSelector = false"
                                class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                            >
                                <XMarkIcon class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Dental chart -->
                        <div class="space-y-6">
                            <!-- Upper jaw -->
                            <div>
                                <p class="mb-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Upper</p>
                                <div class="flex justify-center gap-1">
                                    <!-- Upper Right (Q1) -->
                                    <div class="flex gap-1">
                                        <button
                                            v-for="tooth in quadrants.upper_right"
                                            :key="tooth"
                                            type="button"
                                            @click="selectTooth(tooth)"
                                            :class="[
                                                currentTreatmentIndex !== null && form.treatments[currentTreatmentIndex]?.tooth_number === tooth
                                                    ? 'bg-primary-600 text-white border-primary-600'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-primary-50 hover:border-primary-300',
                                                'flex h-10 w-10 items-center justify-center rounded-md border text-xs font-medium transition-colors',
                                            ]"
                                            :title="getToothName(tooth)"
                                        >
                                            {{ tooth }}
                                        </button>
                                    </div>
                                    <div class="w-px bg-gray-300" />
                                    <!-- Upper Left (Q2) -->
                                    <div class="flex gap-1">
                                        <button
                                            v-for="tooth in quadrants.upper_left"
                                            :key="tooth"
                                            type="button"
                                            @click="selectTooth(tooth)"
                                            :class="[
                                                currentTreatmentIndex !== null && form.treatments[currentTreatmentIndex]?.tooth_number === tooth
                                                    ? 'bg-primary-600 text-white border-primary-600'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-primary-50 hover:border-primary-300',
                                                'flex h-10 w-10 items-center justify-center rounded-md border text-xs font-medium transition-colors',
                                            ]"
                                            :title="getToothName(tooth)"
                                        >
                                            {{ tooth }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-gray-200" />

                            <!-- Lower jaw -->
                            <div>
                                <div class="flex justify-center gap-1">
                                    <!-- Lower Left (Q3) -->
                                    <div class="flex gap-1">
                                        <button
                                            v-for="tooth in quadrants.lower_left"
                                            :key="tooth"
                                            type="button"
                                            @click="selectTooth(tooth)"
                                            :class="[
                                                currentTreatmentIndex !== null && form.treatments[currentTreatmentIndex]?.tooth_number === tooth
                                                    ? 'bg-primary-600 text-white border-primary-600'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-primary-50 hover:border-primary-300',
                                                'flex h-10 w-10 items-center justify-center rounded-md border text-xs font-medium transition-colors',
                                            ]"
                                            :title="getToothName(tooth)"
                                        >
                                            {{ tooth }}
                                        </button>
                                    </div>
                                    <div class="w-px bg-gray-300" />
                                    <!-- Lower Right (Q4) -->
                                    <div class="flex gap-1">
                                        <button
                                            v-for="tooth in quadrants.lower_right"
                                            :key="tooth"
                                            type="button"
                                            @click="selectTooth(tooth)"
                                            :class="[
                                                currentTreatmentIndex !== null && form.treatments[currentTreatmentIndex]?.tooth_number === tooth
                                                    ? 'bg-primary-600 text-white border-primary-600'
                                                    : 'bg-white text-gray-700 border-gray-300 hover:bg-primary-50 hover:border-primary-300',
                                                'flex h-10 w-10 items-center justify-center rounded-md border text-xs font-medium transition-colors',
                                            ]"
                                            :title="getToothName(tooth)"
                                        >
                                            {{ tooth }}
                                        </button>
                                    </div>
                                </div>
                                <p class="mt-2 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Lower</p>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
