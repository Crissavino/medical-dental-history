<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import AttachmentUploader from '@/Components/Attachment/AttachmentUploader.vue';
import type { Patient, AnamnesisVersion } from '@/types';
import {
    PencilSquareIcon,
    TrashIcon,
    ArrowDownTrayIcon,
    PlusIcon,
    PaperClipIcon,
    CalendarDaysIcon,
    DocumentTextIcon,
    ChevronRightIcon,
    ClockIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps<{
    patient: Patient;
    anamnesisVersions: AnamnesisVersion[];
    selectedAnamnesis?: AnamnesisVersion;
}>();

const showDeleteModal = ref(false);
const showUploadModal = ref(false);
const pdfMenuOpenId = ref<number | null>(null);

function togglePdfMenu(versionId: number) {
    pdfMenuOpenId.value = pdfMenuOpenId.value === versionId ? null : versionId;
}

function closePdfMenu() {
    pdfMenuOpenId.value = null;
}

function closePdfMenuDelayed() {
    setTimeout(() => closePdfMenu(), 150);
}
const expandedAnamnesisId = ref<number | null>(props.selectedAnamnesis?.id ?? null);
const currentTab = ref(expandedAnamnesisId.value ? 1 : 0);

const diseaseCategories = computed(() => [
    { key: 'heart', label: t('anamnesis.diseases_heart'), fields: [
        { key: 'angina_pectoris', label: t('anamnesis.heart_angina_pectoris') },
        { key: 'myocardial_infarction', label: t('anamnesis.heart_myocardial_infarction') },
        { key: 'arrhythmias', label: t('anamnesis.heart_arrhythmias') },
        { key: 'blocks', label: t('anamnesis.heart_blocks') },
        { key: 'heart_failure', label: t('anamnesis.heart_failure') },
        { key: 'valvulopathies', label: t('anamnesis.heart_valvulopathies') },
        { key: 'endocarditis', label: t('anamnesis.heart_endocarditis') },
        { key: 'cardiac_surgery', label: t('anamnesis.heart_cardiac_surgery') },
    ]},
    { key: 'vascular', label: t('anamnesis.diseases_vascular'), fields: [
        { key: 'peripheral_arterial_disease', label: t('anamnesis.vascular_peripheral_arterial_disease') },
        { key: 'thrombophlebitis', label: t('anamnesis.vascular_thrombophlebitis') },
        { key: 'hypotension', label: t('anamnesis.vascular_hypotension') },
        { key: 'hypertension', label: t('anamnesis.vascular_hypertension') },
        { key: 'stroke', label: t('anamnesis.vascular_stroke') },
    ]},
    { key: 'respiratory', label: t('anamnesis.diseases_respiratory'), fields: [
        { key: 'asthma', label: t('anamnesis.respiratory_asthma') },
        { key: 'emphysema', label: t('anamnesis.respiratory_emphysema') },
        { key: 'chronic_bronchitis', label: t('anamnesis.respiratory_chronic_bronchitis') },
        { key: 'tuberculosis', label: t('anamnesis.respiratory_tuberculosis') },
    ]},
    { key: 'digestive', label: t('anamnesis.diseases_digestive'), fields: [
        { key: 'gastritis_ulcer', label: t('anamnesis.digestive_gastritis_ulcer') },
    ]},
    { key: 'hepatic', label: t('anamnesis.diseases_hepatic'), fields: [
        { key: 'steatosis', label: t('anamnesis.hepatic_steatosis') },
        { key: 'chronic_hepatitis', label: t('anamnesis.hepatic_chronic_hepatitis') },
        { key: 'cirrhosis', label: t('anamnesis.hepatic_cirrhosis') },
    ]},
    { key: 'renal', label: t('anamnesis.diseases_renal'), fields: [
        { key: 'insufficiency', label: t('anamnesis.renal_insufficiency') },
        { key: 'hemodialysis', label: t('anamnesis.renal_hemodialysis') },
    ]},
    { key: 'diabetes', label: t('anamnesis.diseases_diabetes'), fields: [
        { key: 'insulin_dependent', label: t('anamnesis.diabetes_insulin') },
        { key: 'oral_antidiabetics', label: t('anamnesis.diabetes_oral') },
    ]},
    { key: 'endocrine', label: t('anamnesis.diseases_endocrine'), fields: [
        { key: 'hypothyroidism', label: t('anamnesis.endocrine_hypothyroidism') },
        { key: 'hyperthyroidism', label: t('anamnesis.endocrine_hyperthyroidism') },
    ]},
    { key: 'rheumatic', label: t('anamnesis.diseases_rheumatic'), fields: [
        { key: 'rheumatoid_arthritis', label: t('anamnesis.rheumatic_rheumatoid_arthritis') },
        { key: 'collagenoses', label: t('anamnesis.rheumatic_collagenoses') },
    ]},
    { key: 'skeletal', label: t('anamnesis.diseases_skeletal'), fields: [
        { key: 'osteoporosis', label: t('anamnesis.skeletal_osteoporosis') },
    ]},
    { key: 'neurological', label: t('anamnesis.diseases_neurological'), fields: [
        { key: 'epilepsy', label: t('anamnesis.neurological_epilepsy') },
    ]},
    { key: 'psychiatric', label: t('anamnesis.diseases_psychiatric'), fields: [
        { key: 'depression', label: t('anamnesis.psychiatric_depression') },
        { key: 'schizophrenia', label: t('anamnesis.psychiatric_schizophrenia') },
    ]},
    { key: 'neurovegetative', label: t('anamnesis.diseases_neurovegetative'), fields: [
        { key: 'panic_attacks', label: t('anamnesis.neurovegetative_panic_attacks') },
    ]},
    { key: 'hematologic', label: t('anamnesis.diseases_hematologic'), fields: [
        { key: 'anemia', label: t('anamnesis.hematologic_anemia') },
        { key: 'thalassemia', label: t('anamnesis.hematologic_thalassemia') },
        { key: 'leukemia', label: t('anamnesis.hematologic_leukemia') },
        { key: 'hemophilia', label: t('anamnesis.hematologic_hemophilia') },
        { key: 'thrombocytopenia', label: t('anamnesis.hematologic_thrombocytopenia') },
        { key: 'von_willebrand', label: t('anamnesis.hematologic_von_willebrand') },
    ]},
    { key: 'infectious', label: t('anamnesis.diseases_infectious'), fields: [
        { key: 'hepatitis_b', label: t('anamnesis.infectious_hepatitis_b') },
        { key: 'hepatitis_c', label: t('anamnesis.infectious_hepatitis_c') },
        { key: 'hepatitis_d', label: t('anamnesis.infectious_hepatitis_d') },
        { key: 'hiv', label: t('anamnesis.infectious_hiv') },
    ]},
]);

const tabs = computed(() => [
    t('patient.tab_profile'),
    t('patient.tab_anamnesis'),
    t('patient.tab_encounters'),
    t('patient.tab_attachments'),
]);

const statusColors: Record<string, string> = {
    scheduled: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

function formatDate(dateStr?: string): string {
    if (!dateStr) return '---';
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatDateTime(dateStr: string): string {
    return new Date(dateStr).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function deletePatient() {
    router.delete(route('patients.destroy', props.patient.id));
}

function confirmDelete() {
    showDeleteModal.value = true;
}
</script>

<template>
    <Head :title="`${patient.full_name || `${patient.first_name} ${patient.last_name}`}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('patients.index')"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h1 class="text-lg font-semibold text-gray-900">
                    {{ patient.full_name || `${patient.first_name} ${patient.last_name}` }}
                </h1>
            </div>
        </template>

        <!-- Patient header card -->
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-primary-100 text-lg font-bold text-primary-700">
                        {{ patient.first_name.charAt(0) }}{{ patient.last_name.charAt(0) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ patient.full_name || `${patient.first_name} ${patient.last_name}` }}
                        </h2>
                        <p class="mt-0.5 text-sm text-gray-500">{{ patient.identifier }}</p>
                        <div class="mt-2 flex flex-wrap gap-3 text-sm text-gray-600">
                            <span v-if="patient.phone">{{ patient.phone }}</span>
                            <span v-if="patient.email">{{ patient.email }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        :href="route('patients.gdpr-export', patient.id)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        {{ t('patient.gdpr_export') }}
                    </a>
                    <Link
                        :href="route('patients.edit', patient.id)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        <PencilSquareIcon class="h-4 w-4" />
                        {{ t('app.edit') }}
                    </Link>
                    <button
                        type="button"
                        @click="confirmDelete"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 transition-colors"
                    >
                        <TrashIcon class="h-4 w-4" />
                        {{ t('app.delete') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div>
            <div class="flex space-x-1 rounded-xl border border-gray-200 bg-gray-100 p-1">
                <button
                    v-for="(tab, index) in tabs"
                    :key="tab"
                    @click="currentTab = index"
                    :class="[
                        'w-full rounded-lg py-2.5 text-sm font-medium leading-5 transition-colors',
                        currentTab === index
                            ? 'bg-white text-primary-700 shadow'
                            : 'text-gray-600 hover:bg-white/60 hover:text-gray-900',
                    ]"
                >
                    {{ tab }}
                </button>
            </div>

            <div class="mt-6">
                <!-- Profile tab -->
                <div v-show="currentTab === 0">
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                        <dl class="divide-y divide-gray-100">
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.first_name') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ patient.first_name }}</dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.last_name') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ patient.last_name }}</dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.date_of_birth') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ formatDate(patient.date_of_birth) }}</dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.gender') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    <template v-if="patient.gender">{{ t(`patient.${patient.gender}`) }}</template>
                                    <template v-else>---</template>
                                </dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.cnp') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ patient.cnp || '---' }}</dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.email') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ patient.email || '---' }}</dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.phone') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ patient.phone || '---' }}</dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.address') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    {{ [patient.address, patient.city, patient.county].filter(Boolean).join(', ') || '---' }}
                                </dd>
                            </div>
                            <div v-if="patient.notes" class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.notes') }}</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ patient.notes }}</dd>
                            </div>
                            <div class="px-6 py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                                <dt class="text-sm font-medium text-gray-500">{{ t('patient.created_at') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ formatDateTime(patient.created_at) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Anamnesis tab -->
                <div v-show="currentTab === 1">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">{{ t('anamnesis.title') }}</h3>
                        <Link
                            :href="route('intake.show', { patient: patient.id })"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            {{ t('anamnesis.new') }}
                        </Link>
                    </div>

                    <div v-if="anamnesisVersions.length > 0" class="space-y-4">
                        <div
                            v-for="version in anamnesisVersions"
                            :key="version.id"
                            :class="[
                                'rounded-xl border bg-white p-5 shadow-sm transition-colors',
                                expandedAnamnesisId === version.id ? 'border-primary-300 ring-1 ring-primary-200' : 'border-gray-200',
                            ]"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ t('anamnesis.version') }} {{ version.version }}
                                        </span>
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                            {{ version.language.toUpperCase() }}
                                        </span>
                                        <span v-if="version.consent_given" class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                                            {{ t('anamnesis.consent_agree') }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ t('anamnesis.recorded_at') }}: {{ formatDateTime(version.created_at) }}
                                        <span v-if="version.recorder"> &middot; {{ version.recorder.name }}</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-1">
                                    <Link
                                        :href="route('anamnesis.create', patient.id)"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-primary-600 transition-colors"
                                        :title="t('app.edit')"
                                    >
                                        <PencilSquareIcon class="h-5 w-5" />
                                    </Link>
                                    <div class="relative">
                                        <button
                                            type="button"
                                            @click="togglePdfMenu(version.id)"
                                            @blur="closePdfMenuDelayed"
                                            class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-primary-600 transition-colors"
                                            :title="t('anamnesis.download_pdf')"
                                        >
                                            <ArrowDownTrayIcon class="h-5 w-5" />
                                        </button>
                                        <div
                                            v-if="pdfMenuOpenId === version.id"
                                            class="absolute right-0 z-10 mt-1 w-36 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black/5"
                                        >
                                            <a
                                                :href="route('anamnesis.pdf', { anamnesisVersion: version.id as any, lang: 'en' })"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-t-md"
                                            >
                                                Download EN
                                            </a>
                                            <a
                                                :href="route('anamnesis.pdf', { anamnesisVersion: version.id as any, lang: 'ro' })"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            >
                                                Download RO
                                            </a>
                                            <a
                                                :href="route('anamnesis.pdf', { anamnesisVersion: version.id as any, lang: 'es' })"
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-b-md"
                                            >
                                                Download ES
                                            </a>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="expandedAnamnesisId = expandedAnamnesisId === version.id ? null : version.id"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-primary-600 transition-colors"
                                    >
                                        <ChevronRightIcon
                                            class="h-5 w-5 transition-transform duration-200"
                                            :class="expandedAnamnesisId === version.id ? 'rotate-90' : ''"
                                        />
                                    </button>
                                </div>
                            </div>

                            <!-- Expanded view when selected -->
                            <div v-if="expandedAnamnesisId === version.id" class="mt-4 space-y-4 border-t border-gray-100 pt-4">
                                <!-- Special Situations -->
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700">{{ t('anamnesis.section_1') }}</h4>
                                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.pregnant') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.special_situations?.pregnant ? t('app.yes') : t('app.no') }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.menstruating') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.special_situations?.menstruating ? t('app.yes') : t('app.no') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Allergies -->
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700">{{ t('anamnesis.section_2') }}</h4>
                                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.drug_allergies') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.allergies?.drug_allergies?.join(', ') || '---' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.non_drug_allergies') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.allergies?.non_drug_allergies?.join(', ') || '---' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Current Treatment -->
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700">{{ t('anamnesis.section_3') }}</h4>
                                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.medications') }}</span>
                                            <p v-if="version.form_data.current_treatment?.medications?.length" class="text-sm text-gray-900">
                                                <span v-for="(med, i) in version.form_data.current_treatment.medications" :key="i">
                                                    {{ med.name }} ({{ med.dose }})<span v-if="i < version.form_data.current_treatment.medications.length - 1">, </span>
                                                </span>
                                            </p>
                                            <p v-else class="text-sm text-gray-900">---</p>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.anticoagulants') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.current_treatment?.anticoagulants ? t('app.yes') : t('app.no') }}</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Diseases -->
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700">{{ t('anamnesis.section_4') }}</h4>
                                    <div class="mt-2 space-y-2">
                                        <template v-for="cat in diseaseCategories" :key="cat.key">
                                            <div v-if="(version.form_data.diseases as any)?.[cat.key]">
                                                <template v-for="field in cat.fields" :key="field.key">
                                                    <span
                                                        v-if="(version.form_data.diseases as any)[cat.key][field.key]"
                                                        class="mr-1.5 mb-1.5 inline-flex items-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700"
                                                    >
                                                        {{ cat.label }}: {{ field.label }}
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                        <div v-if="(version.form_data.diseases as any)?.neoplasms_details" class="text-sm text-gray-900">
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.diseases_neoplasms') }}:</span>
                                            {{ (version.form_data.diseases as any).neoplasms_details }}
                                        </div>
                                        <div v-if="(version.form_data.diseases as any)?.other_diseases_details" class="text-sm text-gray-900">
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.diseases_other') }}:</span>
                                            {{ (version.form_data.diseases as any).other_diseases_details }}
                                        </div>
                                        <p v-if="!version.form_data.diseases" class="text-sm text-gray-500">---</p>
                                    </div>
                                </div>
                                <!-- Surgical History -->
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700">{{ t('anamnesis.section_5') }}</h4>
                                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.previous_surgeries') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.surgical_history?.previous_surgeries || '---' }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.transfusions') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.surgical_history?.transfusions ? t('app.yes') : t('app.no') }}</p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.surgery_complications') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.surgical_history?.surgery_complications || '---' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Dental History -->
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700">{{ t('anamnesis.section_6') }}</h4>
                                    <div class="mt-2 space-y-3">
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.dental_anesthesia_types') }}</span>
                                            <div class="mt-1 flex flex-wrap gap-1.5">
                                                <span
                                                    v-if="version.form_data.dental_history?.anesthesia_types?.local"
                                                    class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700"
                                                >{{ t('anamnesis.anesthesia_local') }}</span>
                                                <span
                                                    v-if="version.form_data.dental_history?.anesthesia_types?.plexal"
                                                    class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700"
                                                >{{ t('anamnesis.anesthesia_plexal') }}</span>
                                                <span
                                                    v-if="version.form_data.dental_history?.anesthesia_types?.troncular"
                                                    class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700"
                                                >{{ t('anamnesis.anesthesia_troncular') }}</span>
                                                <span
                                                    v-if="version.form_data.dental_history?.anesthesia_types?.general"
                                                    class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700"
                                                >{{ t('anamnesis.anesthesia_general') }}</span>
                                                <span
                                                    v-if="!version.form_data.dental_history?.anesthesia_types?.local && !version.form_data.dental_history?.anesthesia_types?.plexal && !version.form_data.dental_history?.anesthesia_types?.troncular && !version.form_data.dental_history?.anesthesia_types?.general"
                                                    class="text-sm text-gray-500"
                                                >---</span>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.adverse_reactions') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.dental_history?.adverse_reactions || '---' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Habits -->
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-700">{{ t('anamnesis.section_7') }}</h4>
                                    <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.tobacco') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.habits?.tobacco ? t('app.yes') : t('app.no') }}</p>
                                            <p v-if="version.form_data.habits?.tobacco && version.form_data.habits?.tobacco_amount" class="text-xs text-gray-500">
                                                {{ t('anamnesis.tobacco_amount') }}: {{ version.form_data.habits.tobacco_amount }}
                                                <template v-if="version.form_data.habits.tobacco_duration"> &middot; {{ t('anamnesis.tobacco_duration') }}: {{ version.form_data.habits.tobacco_duration }}</template>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.alcohol') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.habits?.alcohol ? t('app.yes') : t('app.no') }}</p>
                                            <p v-if="version.form_data.habits?.alcohol && version.form_data.habits?.alcohol_amount" class="text-xs text-gray-500">
                                                {{ t('anamnesis.alcohol_amount') }}: {{ version.form_data.habits.alcohol_amount }}
                                                <template v-if="version.form_data.habits.alcohol_duration"> &middot; {{ t('anamnesis.alcohol_duration') }}: {{ version.form_data.habits.alcohol_duration }}</template>
                                            </p>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">{{ t('anamnesis.drugs') }}</span>
                                            <p class="text-sm text-gray-900">{{ version.form_data.habits?.drugs ? t('app.yes') : t('app.no') }}</p>
                                            <p v-if="version.form_data.habits?.drugs && version.form_data.habits?.drugs_amount" class="text-xs text-gray-500">
                                                {{ t('anamnesis.drugs_amount') }}: {{ version.form_data.habits.drugs_amount }}
                                                <template v-if="version.form_data.habits.drugs_duration"> &middot; {{ t('anamnesis.drugs_duration') }}: {{ version.form_data.habits.drugs_duration }}</template>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                        <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-300" />
                        <p class="mt-2 text-sm text-gray-500">{{ t('anamnesis.no_anamnesis') }}</p>
                        <Link
                            :href="route('intake.show', { patient: patient.id })"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            {{ t('anamnesis.new') }}
                        </Link>
                    </div>
                </div>

                <!-- Encounters tab -->
                <div v-show="currentTab === 2">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">{{ t('encounter.title') }}</h3>
                        <Link
                            :href="route('encounters.create', { patient: patient.id })"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            {{ t('encounter.new') }}
                        </Link>
                    </div>

                    <div v-if="patient.encounters && patient.encounters.length > 0" class="space-y-4">
                        <!-- Timeline -->
                        <div class="relative">
                            <div class="absolute left-4 top-0 h-full w-0.5 bg-gray-200" />
                            <div v-for="encounter in patient.encounters" :key="encounter.id" class="relative pl-10">
                                <div class="absolute left-2.5 top-5 h-3 w-3 rounded-full border-2 border-white bg-primary-500 ring-2 ring-primary-100" />
                                <Link
                                    :href="route('encounters.show', encounter.id)"
                                    class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm hover:border-primary-200 transition-colors"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <CalendarDaysIcon class="h-4 w-4 text-gray-400" />
                                                <span class="text-sm font-medium text-gray-900">{{ formatDate(encounter.encounter_date) }}</span>
                                                <span
                                                    :class="[statusColors[encounter.status], 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium']"
                                                >
                                                    {{ t(`encounter.status_${encounter.status}`) }}
                                                </span>
                                            </div>
                                            <p v-if="encounter.chief_complaint" class="mt-1 text-sm text-gray-600">
                                                {{ encounter.chief_complaint }}
                                            </p>
                                            <p v-if="encounter.provider" class="mt-1 text-xs text-gray-500">
                                                {{ t('encounter.provider') }}: {{ encounter.provider.name }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <span v-if="encounter.treatments?.length">
                                                {{ encounter.treatments.length }} {{ t('treatment.title').toLowerCase() }}
                                            </span>
                                            <ChevronRightIcon class="h-4 w-4 text-gray-400" />
                                        </div>
                                    </div>
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div v-else class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                        <CalendarDaysIcon class="mx-auto h-12 w-12 text-gray-300" />
                        <p class="mt-2 text-sm text-gray-500">{{ t('encounter.no_encounters') }}</p>
                        <Link
                            :href="route('encounters.create', { patient: patient.id })"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            {{ t('encounter.new') }}
                        </Link>
                    </div>
                </div>

                <!-- Attachments tab -->
                <div v-show="currentTab === 3">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">{{ t('attachment.title') }}</h3>
                        <button
                            type="button"
                            @click="showUploadModal = true"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            {{ t('attachment.upload') }}
                        </button>
                    </div>

                    <div v-if="patient.attachments && patient.attachments.length > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="attachment in patient.attachments"
                            :key="attachment.id"
                            class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-colors hover:border-primary-200"
                        >
                            <!-- Image preview -->
                            <div
                                v-if="attachment.mime_type.startsWith('image/')"
                                class="relative aspect-video overflow-hidden bg-gray-100"
                            >
                                <img
                                    :src="route('attachments.show', attachment.id)"
                                    :alt="attachment.file_name"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div v-else class="flex aspect-video items-center justify-center bg-gray-50">
                                <PaperClipIcon class="h-12 w-12 text-gray-300" />
                            </div>
                            <div class="p-4">
                                <p class="truncate text-sm font-medium text-gray-900">{{ attachment.file_name }}</p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ formatFileSize(attachment.file_size) }}
                                    &middot; {{ formatDateTime(attachment.created_at) }}
                                </p>
                                <p v-if="attachment.description" class="mt-1 truncate text-xs text-gray-500">{{ attachment.description }}</p>
                                <div class="mt-3 flex gap-2">
                                    <a
                                        :href="route('attachments.show', attachment.id)"
                                        class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors"
                                    >
                                        <ArrowDownTrayIcon class="h-3.5 w-3.5" />
                                        {{ t('app.download') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm">
                        <PaperClipIcon class="mx-auto h-12 w-12 text-gray-300" />
                        <p class="mt-2 text-sm text-gray-500">{{ t('attachment.no_attachments') }}</p>
                        <button
                            type="button"
                            @click="showUploadModal = true"
                            class="mt-4 inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            {{ t('attachment.upload') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upload attachment modal -->
        <Modal :show="showUploadModal" @close="showUploadModal = false" max-width="lg">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('attachment.upload') }}</h3>
            <AttachmentUploader attachable-type="patient" :attachable-id="patient.id" />
        </Modal>

        <!-- Delete confirmation modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900">{{ t('app.confirm') }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ t('patient.delete_confirm') }}</p>
                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="showDeleteModal = false"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                            >
                                {{ t('app.cancel') }}
                            </button>
                            <button
                                type="button"
                                @click="deletePatient"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition-colors"
                            >
                                {{ t('app.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
