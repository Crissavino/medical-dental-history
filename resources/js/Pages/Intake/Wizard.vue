<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import type { Patient, AnamnesisVersion, Medication, AntibioticDetail } from '@/types';
import {
    CheckIcon,
    PlusIcon,
    TrashIcon,
    LanguageIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/vue/24/outline';

const { t, locale } = useI18n();

const props = defineProps<{
    patient?: Patient;
    existingAnamnesis?: AnamnesisVersion;
}>();

function switchLocale(code: string) {
    locale.value = code;
    localStorage.setItem('locale', code);
    form.language = code as 'en' | 'ro' | 'es';
}

const currentStep = ref(1);
const totalSteps = 8;

const steps = computed(() => [
    { number: 1, name: t('anamnesis.step_patient_info') },
    { number: 2, name: t('anamnesis.step_special_situations') },
    { number: 3, name: t('anamnesis.step_allergies') },
    { number: 4, name: t('anamnesis.step_current_treatment') },
    { number: 5, name: t('anamnesis.step_diseases') },
    { number: 6, name: t('anamnesis.step_surgical_dental') },
    { number: 7, name: t('anamnesis.step_habits') },
    { number: 8, name: t('anamnesis.step_consent') },
]);

const ed = props.existingAnamnesis?.form_data;

const form = useForm({
    // Patient info
    first_name: props.patient?.first_name || '',
    last_name: props.patient?.last_name || '',
    phone: props.patient?.phone || '',
    email: props.patient?.email || '',
    cnp: props.patient?.cnp || '',
    address: props.patient?.address || '',
    first_visit_date: '',
    patient_id: props.patient?.id || null as number | null,
    // Form data (matches AnamnesisFormData)
    form_data: {
        special_situations: {
            pregnant: ed?.special_situations?.pregnant || false,
            menstruating: ed?.special_situations?.menstruating || false,
            gestational_age: ed?.special_situations?.gestational_age || '',
        },
        allergies: {
            drug_allergies: ed?.allergies?.drug_allergies || [] as string[],
            non_drug_allergies: ed?.allergies?.non_drug_allergies || [] as string[],
        },
        current_treatment: {
            medications: ed?.current_treatment?.medications || [] as Medication[],
            antibiotics_last_2_weeks: ed?.current_treatment?.antibiotics_last_2_weeks || false,
            antibiotic_details: ed?.current_treatment?.antibiotic_details || [] as AntibioticDetail[],
            anticoagulants: ed?.current_treatment?.anticoagulants || false,
            anticoagulant_inr: ed?.current_treatment?.anticoagulant_inr || '',
            bisphosphonates: ed?.current_treatment?.bisphosphonates || false,
            bisphosphonate_route: ed?.current_treatment?.bisphosphonate_route || '' as '' | 'oral' | 'iv',
            bisphosphonate_duration: ed?.current_treatment?.bisphosphonate_duration || '',
            bisphosphonate_beta_ctx: ed?.current_treatment?.bisphosphonate_beta_ctx || '',
        },
        diseases: {
            heart: {
                angina_pectoris: ed?.diseases?.heart?.angina_pectoris || false,
                myocardial_infarction: ed?.diseases?.heart?.myocardial_infarction || false,
                arrhythmias: ed?.diseases?.heart?.arrhythmias || false,
                blocks: ed?.diseases?.heart?.blocks || false,
                heart_failure: ed?.diseases?.heart?.heart_failure || false,
                heart_failure_nyha: ed?.diseases?.heart?.heart_failure_nyha || '',
                valvulopathies: ed?.diseases?.heart?.valvulopathies || false,
                endocarditis: ed?.diseases?.heart?.endocarditis || false,
                cardiac_surgery: ed?.diseases?.heart?.cardiac_surgery || false,
            },
            vascular: {
                peripheral_arterial_disease: ed?.diseases?.vascular?.peripheral_arterial_disease || false,
                thrombophlebitis: ed?.diseases?.vascular?.thrombophlebitis || false,
                hypotension: ed?.diseases?.vascular?.hypotension || false,
                hypertension: ed?.diseases?.vascular?.hypertension || false,
                hypertension_max: ed?.diseases?.vascular?.hypertension_max || '',
                stroke: ed?.diseases?.vascular?.stroke || false,
            },
            respiratory: {
                asthma: ed?.diseases?.respiratory?.asthma || false,
                emphysema: ed?.diseases?.respiratory?.emphysema || false,
                chronic_bronchitis: ed?.diseases?.respiratory?.chronic_bronchitis || false,
                tuberculosis: ed?.diseases?.respiratory?.tuberculosis || false,
            },
            digestive: {
                gastritis_ulcer: ed?.diseases?.digestive?.gastritis_ulcer || false,
            },
            hepatic: {
                steatosis: ed?.diseases?.hepatic?.steatosis || false,
                chronic_hepatitis: ed?.diseases?.hepatic?.chronic_hepatitis || false,
                cirrhosis: ed?.diseases?.hepatic?.cirrhosis || false,
            },
            renal: {
                insufficiency: ed?.diseases?.renal?.insufficiency || false,
                hemodialysis: ed?.diseases?.renal?.hemodialysis || false,
            },
            diabetes: {
                insulin_dependent: ed?.diseases?.diabetes?.insulin_dependent || false,
                oral_antidiabetics: ed?.diseases?.diabetes?.oral_antidiabetics || false,
            },
            endocrine: {
                hypothyroidism: ed?.diseases?.endocrine?.hypothyroidism || false,
                hyperthyroidism: ed?.diseases?.endocrine?.hyperthyroidism || false,
            },
            rheumatic: {
                rheumatoid_arthritis: ed?.diseases?.rheumatic?.rheumatoid_arthritis || false,
                collagenoses: ed?.diseases?.rheumatic?.collagenoses || false,
            },
            skeletal: {
                osteoporosis: ed?.diseases?.skeletal?.osteoporosis || false,
            },
            neurological: {
                epilepsy: ed?.diseases?.neurological?.epilepsy || false,
            },
            psychiatric: {
                depression: ed?.diseases?.psychiatric?.depression || false,
                schizophrenia: ed?.diseases?.psychiatric?.schizophrenia || false,
            },
            neurovegetative: {
                panic_attacks: ed?.diseases?.neurovegetative?.panic_attacks || false,
            },
            hematologic: {
                anemia: ed?.diseases?.hematologic?.anemia || false,
                thalassemia: ed?.diseases?.hematologic?.thalassemia || false,
                leukemia: ed?.diseases?.hematologic?.leukemia || false,
                hemophilia: ed?.diseases?.hematologic?.hemophilia || false,
                thrombocytopenia: ed?.diseases?.hematologic?.thrombocytopenia || false,
                von_willebrand: ed?.diseases?.hematologic?.von_willebrand || false,
            },
            infectious: {
                hepatitis_b: ed?.diseases?.infectious?.hepatitis_b || false,
                hepatitis_c: ed?.diseases?.infectious?.hepatitis_c || false,
                hepatitis_d: ed?.diseases?.infectious?.hepatitis_d || false,
                hiv: ed?.diseases?.infectious?.hiv || false,
            },
            neoplasms: ed?.diseases?.neoplasms || false,
            neoplasms_details: ed?.diseases?.neoplasms_details || '',
            other_diseases: ed?.diseases?.other_diseases || false,
            other_diseases_details: ed?.diseases?.other_diseases_details || '',
        },
        surgical_history: {
            previous_surgeries: ed?.surgical_history?.previous_surgeries || '',
            transfusions: ed?.surgical_history?.transfusions || false,
            surgery_complications: ed?.surgical_history?.surgery_complications || '',
        },
        dental_history: {
            anesthesia_types: {
                local: ed?.dental_history?.anesthesia_types?.local || false,
                plexal: ed?.dental_history?.anesthesia_types?.plexal || false,
                troncular: ed?.dental_history?.anesthesia_types?.troncular || false,
                general: ed?.dental_history?.anesthesia_types?.general || false,
            },
            adverse_reactions: ed?.dental_history?.adverse_reactions || '',
        },
        habits: {
            tobacco: ed?.habits?.tobacco || false,
            tobacco_amount: ed?.habits?.tobacco_amount || '',
            tobacco_duration: ed?.habits?.tobacco_duration || '',
            alcohol: ed?.habits?.alcohol || false,
            alcohol_amount: ed?.habits?.alcohol_amount || '',
            alcohol_duration: ed?.habits?.alcohol_duration || '',
            drugs: ed?.habits?.drugs || false,
            drugs_amount: ed?.habits?.drugs_amount || '',
            drugs_duration: ed?.habits?.drugs_duration || '',
        },
    },
    consent_given: false,
    language: locale.value as 'en' | 'ro' | 'es',
});

// --- Tag input helpers ---
const newDrugAllergy = ref('');
function addDrugAllergy() {
    const val = newDrugAllergy.value.trim();
    if (val && !form.form_data.allergies.drug_allergies.includes(val)) {
        form.form_data.allergies.drug_allergies.push(val);
    }
    newDrugAllergy.value = '';
}
function removeDrugAllergy(i: number) {
    form.form_data.allergies.drug_allergies.splice(i, 1);
}

const newNonDrugAllergy = ref('');
function addNonDrugAllergy() {
    const val = newNonDrugAllergy.value.trim();
    if (val && !form.form_data.allergies.non_drug_allergies.includes(val)) {
        form.form_data.allergies.non_drug_allergies.push(val);
    }
    newNonDrugAllergy.value = '';
}
function removeNonDrugAllergy(i: number) {
    form.form_data.allergies.non_drug_allergies.splice(i, 1);
}

// --- Medication helpers ---
function addMedication() {
    form.form_data.current_treatment.medications.push({ name: '', dose: '' });
}
function removeMedication(i: number) {
    form.form_data.current_treatment.medications.splice(i, 1);
}

function addAntibiotic() {
    form.form_data.current_treatment.antibiotic_details.push({ drug: '', dose: '' });
}
function removeAntibiotic(i: number) {
    form.form_data.current_treatment.antibiotic_details.splice(i, 1);
}

// --- Navigation ---
function nextStep() {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}
function prevStep() {
    if (currentStep.value > 1) {
        currentStep.value--;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function submit() {
    form.post(route('intake.store'), { preserveScroll: false });
}

// --- Disease category definitions for rendering ---
const diseaseCategories = computed(() => [
    {
        key: 'heart',
        label: t('anamnesis.diseases_heart'),
        fields: [
            { key: 'angina_pectoris', label: t('anamnesis.heart_angina_pectoris') },
            { key: 'myocardial_infarction', label: t('anamnesis.heart_myocardial_infarction') },
            { key: 'arrhythmias', label: t('anamnesis.heart_arrhythmias') },
            { key: 'blocks', label: t('anamnesis.heart_blocks') },
            { key: 'heart_failure', label: t('anamnesis.heart_failure'), extra: 'heart_failure_nyha' },
            { key: 'valvulopathies', label: t('anamnesis.heart_valvulopathies') },
            { key: 'endocarditis', label: t('anamnesis.heart_endocarditis') },
            { key: 'cardiac_surgery', label: t('anamnesis.heart_cardiac_surgery') },
        ],
    },
    {
        key: 'vascular',
        label: t('anamnesis.diseases_vascular'),
        fields: [
            { key: 'peripheral_arterial_disease', label: t('anamnesis.vascular_peripheral_arterial_disease') },
            { key: 'thrombophlebitis', label: t('anamnesis.vascular_thrombophlebitis') },
            { key: 'hypotension', label: t('anamnesis.vascular_hypotension') },
            { key: 'hypertension', label: t('anamnesis.vascular_hypertension'), extra: 'hypertension_max' },
            { key: 'stroke', label: t('anamnesis.vascular_stroke') },
        ],
    },
    {
        key: 'respiratory',
        label: t('anamnesis.diseases_respiratory'),
        fields: [
            { key: 'asthma', label: t('anamnesis.respiratory_asthma') },
            { key: 'emphysema', label: t('anamnesis.respiratory_emphysema') },
            { key: 'chronic_bronchitis', label: t('anamnesis.respiratory_chronic_bronchitis') },
            { key: 'tuberculosis', label: t('anamnesis.respiratory_tuberculosis') },
        ],
    },
    {
        key: 'digestive',
        label: t('anamnesis.diseases_digestive'),
        fields: [
            { key: 'gastritis_ulcer', label: t('anamnesis.digestive_gastritis_ulcer') },
        ],
    },
    {
        key: 'hepatic',
        label: t('anamnesis.diseases_hepatic'),
        fields: [
            { key: 'steatosis', label: t('anamnesis.hepatic_steatosis') },
            { key: 'chronic_hepatitis', label: t('anamnesis.hepatic_chronic_hepatitis') },
            { key: 'cirrhosis', label: t('anamnesis.hepatic_cirrhosis') },
        ],
    },
    {
        key: 'renal',
        label: t('anamnesis.diseases_renal'),
        fields: [
            { key: 'insufficiency', label: t('anamnesis.renal_insufficiency') },
            { key: 'hemodialysis', label: t('anamnesis.renal_hemodialysis') },
        ],
    },
    {
        key: 'diabetes',
        label: t('anamnesis.diseases_diabetes'),
        fields: [
            { key: 'insulin_dependent', label: t('anamnesis.diabetes_insulin') },
            { key: 'oral_antidiabetics', label: t('anamnesis.diabetes_oral') },
        ],
    },
    {
        key: 'endocrine',
        label: t('anamnesis.diseases_endocrine'),
        fields: [
            { key: 'hypothyroidism', label: t('anamnesis.endocrine_hypothyroidism') },
            { key: 'hyperthyroidism', label: t('anamnesis.endocrine_hyperthyroidism') },
        ],
    },
    {
        key: 'rheumatic',
        label: t('anamnesis.diseases_rheumatic'),
        fields: [
            { key: 'rheumatoid_arthritis', label: t('anamnesis.rheumatic_rheumatoid_arthritis') },
            { key: 'collagenoses', label: t('anamnesis.rheumatic_collagenoses') },
        ],
    },
    {
        key: 'skeletal',
        label: t('anamnesis.diseases_skeletal'),
        fields: [
            { key: 'osteoporosis', label: t('anamnesis.skeletal_osteoporosis') },
        ],
    },
    {
        key: 'neurological',
        label: t('anamnesis.diseases_neurological'),
        fields: [
            { key: 'epilepsy', label: t('anamnesis.neurological_epilepsy') },
        ],
    },
    {
        key: 'psychiatric',
        label: t('anamnesis.diseases_psychiatric'),
        fields: [
            { key: 'depression', label: t('anamnesis.psychiatric_depression') },
            { key: 'schizophrenia', label: t('anamnesis.psychiatric_schizophrenia') },
        ],
    },
    {
        key: 'neurovegetative',
        label: t('anamnesis.diseases_neurovegetative'),
        fields: [
            { key: 'panic_attacks', label: t('anamnesis.neurovegetative_panic_attacks') },
        ],
    },
    {
        key: 'hematologic',
        label: t('anamnesis.diseases_hematologic'),
        fields: [
            { key: 'anemia', label: t('anamnesis.hematologic_anemia') },
            { key: 'thalassemia', label: t('anamnesis.hematologic_thalassemia') },
            { key: 'leukemia', label: t('anamnesis.hematologic_leukemia') },
            { key: 'hemophilia', label: t('anamnesis.hematologic_hemophilia') },
            { key: 'thrombocytopenia', label: t('anamnesis.hematologic_thrombocytopenia') },
            { key: 'von_willebrand', label: t('anamnesis.hematologic_von_willebrand') },
        ],
    },
    {
        key: 'infectious',
        label: t('anamnesis.diseases_infectious'),
        fields: [
            { key: 'hepatitis_b', label: t('anamnesis.infectious_hepatitis_b') },
            { key: 'hepatitis_c', label: t('anamnesis.infectious_hepatitis_c') },
            { key: 'hepatitis_d', label: t('anamnesis.infectious_hepatitis_d') },
            { key: 'hiv', label: t('anamnesis.infectious_hiv') },
        ],
    },
]);
</script>

<template>
    <Head :title="t('anamnesis.title')" />

    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="border-b border-gray-200 bg-white">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-600">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-semibold text-gray-900">{{ t('anamnesis.clinic_name') }}</span>
                        <p class="text-xs text-gray-500">{{ t('anamnesis.title') }}</p>
                    </div>
                </div>
                <!-- Language toggle -->
                <div class="flex items-center gap-1 rounded-lg border border-gray-200 p-0.5">
                    <button
                        v-for="lang in ['en', 'ro', 'es']"
                        :key="lang"
                        type="button"
                        @click="switchLocale(lang)"
                        :class="[
                            locale === lang
                                ? 'bg-primary-600 text-white'
                                : 'text-gray-600 hover:text-gray-900',
                            'flex items-center gap-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                        ]"
                    >
                        <LanguageIcon v-if="locale === lang" class="h-4 w-4" />
                        {{ lang.toUpperCase() }}
                    </button>
                </div>
            </div>
        </header>

        <!-- Progress bar -->
        <div class="border-b border-gray-200 bg-white">
            <div class="mx-auto max-w-3xl px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-600">
                        {{ t('anamnesis.step') }} {{ currentStep }}/{{ totalSteps }}
                    </span>
                    <span class="text-sm font-medium text-primary-600">
                        {{ steps[currentStep - 1].name }}
                    </span>
                </div>
                <div class="mt-3 flex gap-1">
                    <div
                        v-for="step in totalSteps"
                        :key="step"
                        :class="[
                            step <= currentStep ? 'bg-primary-600' : 'bg-gray-200',
                            'h-1.5 flex-1 rounded-full transition-colors duration-300',
                        ]"
                    />
                </div>
            </div>
        </div>

        <!-- Form content -->
        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
            <form @submit.prevent="submit">

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STEP 1: Patient Information                        -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-show="currentStep === 1" class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-6 text-xl font-bold text-gray-900">{{ t('anamnesis.step_patient_info') }}</h2>

                        <div v-if="patient" class="mb-6 rounded-lg bg-primary-50 p-4">
                            <p class="text-sm font-medium text-primary-900">
                                {{ patient.full_name || `${patient.first_name} ${patient.last_name}` }}
                            </p>
                            <p class="text-sm text-primary-700">{{ patient.identifier }}</p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel :value="t('patient.last_name')" />
                                <input v-model="form.last_name" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" :readonly="!!patient" required />
                                <InputError :message="form.errors.last_name" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel :value="t('patient.first_name')" />
                                <input v-model="form.first_name" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" :readonly="!!patient" required />
                                <InputError :message="form.errors.first_name" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel :value="t('anamnesis.patient_cnp_passport')" />
                                <input v-model="form.cnp" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                <InputError :message="form.errors.cnp" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel :value="t('anamnesis.patient_phone')" />
                                <input v-model="form.phone" type="tel" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                <InputError :message="form.errors.phone" class="mt-1" />
                            </div>
                            <div class="sm:col-span-2">
                                <InputLabel :value="t('anamnesis.patient_home_address')" />
                                <input v-model="form.address" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                <InputError :message="form.errors.address" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel :value="t('anamnesis.patient_email')" />
                                <input v-model="form.email" type="email" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                <InputError :message="form.errors.email" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel :value="t('anamnesis.patient_first_visit_date')" />
                                <input v-model="form.first_visit_date" type="date" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STEP 2: Special Situations                         -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-show="currentStep === 2" class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-2 text-xl font-bold text-gray-900">{{ t('anamnesis.section_1') }}</h2>
                        <p class="mb-6 text-sm text-gray-500">{{ t('anamnesis.step_special_situations') }}</p>

                        <div class="space-y-4">
                            <!-- Pregnant -->
                            <button
                                type="button"
                                @click="form.form_data.special_situations.pregnant = !form.form_data.special_situations.pregnant"
                                :class="[
                                    form.form_data.special_situations.pregnant
                                        ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                    'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                ]"
                            >
                                <div :class="[form.form_data.special_situations.pregnant ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                    <CheckIcon v-if="form.form_data.special_situations.pregnant" class="h-3.5 w-3.5 text-white" />
                                </div>
                                {{ t('anamnesis.pregnant') }}
                            </button>

                            <!-- Gestational age (shown when pregnant) -->
                            <div v-if="form.form_data.special_situations.pregnant" class="ml-8">
                                <InputLabel :value="t('anamnesis.gestational_age')" />
                                <input v-model="form.form_data.special_situations.gestational_age" type="text" class="mt-1 block w-full max-w-xs rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            </div>

                            <!-- Menstruating -->
                            <button
                                type="button"
                                @click="form.form_data.special_situations.menstruating = !form.form_data.special_situations.menstruating"
                                :class="[
                                    form.form_data.special_situations.menstruating
                                        ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                    'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                ]"
                            >
                                <div :class="[form.form_data.special_situations.menstruating ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                    <CheckIcon v-if="form.form_data.special_situations.menstruating" class="h-3.5 w-3.5 text-white" />
                                </div>
                                {{ t('anamnesis.menstruating') }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STEP 3: Allergies / Intolerances                   -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-show="currentStep === 3" class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-2 text-xl font-bold text-gray-900">{{ t('anamnesis.section_2') }}</h2>

                        <!-- Drug allergies -->
                        <div class="mb-8">
                            <InputLabel :value="t('anamnesis.drug_allergies')" />
                            <div class="mt-2 flex gap-2">
                                <input
                                    v-model="newDrugAllergy"
                                    type="text"
                                    class="block flex-1 rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    :placeholder="t('anamnesis.drug_allergies_placeholder')"
                                    @keydown.enter.prevent="addDrugAllergy"
                                />
                                <button type="button" @click="addDrugAllergy" class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors">
                                    <PlusIcon class="h-5 w-5" />
                                </button>
                            </div>
                            <div v-if="form.form_data.allergies.drug_allergies.length" class="mt-3 flex flex-wrap gap-2">
                                <span v-for="(item, i) in form.form_data.allergies.drug_allergies" :key="i" class="inline-flex items-center gap-1 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800">
                                    {{ item }}
                                    <button type="button" @click="removeDrugAllergy(i)" class="ml-1 rounded-full p-0.5 hover:bg-red-200 transition-colors">
                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                                    </button>
                                </span>
                            </div>
                        </div>

                        <!-- Non-drug allergies -->
                        <div>
                            <InputLabel :value="t('anamnesis.non_drug_allergies')" />
                            <div class="mt-2 flex gap-2">
                                <input
                                    v-model="newNonDrugAllergy"
                                    type="text"
                                    class="block flex-1 rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    :placeholder="t('anamnesis.non_drug_allergies_placeholder')"
                                    @keydown.enter.prevent="addNonDrugAllergy"
                                />
                                <button type="button" @click="addNonDrugAllergy" class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors">
                                    <PlusIcon class="h-5 w-5" />
                                </button>
                            </div>
                            <div v-if="form.form_data.allergies.non_drug_allergies.length" class="mt-3 flex flex-wrap gap-2">
                                <span v-for="(item, i) in form.form_data.allergies.non_drug_allergies" :key="i" class="inline-flex items-center gap-1 rounded-full bg-orange-100 px-3 py-1 text-sm font-medium text-orange-800">
                                    {{ item }}
                                    <button type="button" @click="removeNonDrugAllergy(i)" class="ml-1 rounded-full p-0.5 hover:bg-orange-200 transition-colors">
                                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                                    </button>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STEP 4: Current Treatment                          -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-show="currentStep === 4" class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-2 text-xl font-bold text-gray-900">{{ t('anamnesis.section_3') }}</h2>

                        <!-- Medications -->
                        <div class="mb-8">
                            <div class="flex items-center justify-between">
                                <InputLabel :value="t('anamnesis.medications')" />
                                <button type="button" @click="addMedication" class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                                    <PlusIcon class="h-4 w-4" />
                                    {{ t('anamnesis.add_medication') }}
                                </button>
                            </div>
                            <div v-if="form.form_data.current_treatment.medications.length" class="mt-3 space-y-3">
                                <div v-for="(med, i) in form.form_data.current_treatment.medications" :key="i" class="flex items-start gap-3">
                                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                                        <input v-model="med.name" type="text" class="block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" :placeholder="t('anamnesis.medication_name')" />
                                        <input v-model="med.dose" type="text" class="block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" :placeholder="t('anamnesis.medication_dose')" />
                                    </div>
                                    <button type="button" @click="removeMedication(i)" class="mt-2 rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Antibiotics last 2 weeks -->
                        <div class="mb-8 rounded-lg border border-gray-200 p-4">
                            <button
                                type="button"
                                @click="form.form_data.current_treatment.antibiotics_last_2_weeks = !form.form_data.current_treatment.antibiotics_last_2_weeks"
                                :class="[
                                    form.form_data.current_treatment.antibiotics_last_2_weeks
                                        ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                    'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                ]"
                            >
                                <div :class="[form.form_data.current_treatment.antibiotics_last_2_weeks ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                    <CheckIcon v-if="form.form_data.current_treatment.antibiotics_last_2_weeks" class="h-3.5 w-3.5 text-white" />
                                </div>
                                {{ t('anamnesis.antibiotics_last_2_weeks') }}
                            </button>

                            <div v-if="form.form_data.current_treatment.antibiotics_last_2_weeks" class="mt-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-gray-600">{{ t('anamnesis.antibiotic_drug') }} / {{ t('anamnesis.antibiotic_dose') }}</span>
                                    <button type="button" @click="addAntibiotic" class="inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                                        <PlusIcon class="h-4 w-4" />
                                        {{ t('anamnesis.add_antibiotic') }}
                                    </button>
                                </div>
                                <div v-for="(ab, i) in form.form_data.current_treatment.antibiotic_details" :key="i" class="flex items-start gap-3">
                                    <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
                                        <input v-model="ab.drug" type="text" class="block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" :placeholder="t('anamnesis.antibiotic_drug')" />
                                        <input v-model="ab.dose" type="text" class="block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" :placeholder="t('anamnesis.antibiotic_dose')" />
                                    </div>
                                    <button type="button" @click="removeAntibiotic(i)" class="mt-2 rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Anticoagulants -->
                        <div class="mb-8 rounded-lg border border-gray-200 p-4">
                            <button
                                type="button"
                                @click="form.form_data.current_treatment.anticoagulants = !form.form_data.current_treatment.anticoagulants"
                                :class="[
                                    form.form_data.current_treatment.anticoagulants
                                        ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                    'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                ]"
                            >
                                <div :class="[form.form_data.current_treatment.anticoagulants ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                    <CheckIcon v-if="form.form_data.current_treatment.anticoagulants" class="h-3.5 w-3.5 text-white" />
                                </div>
                                {{ t('anamnesis.anticoagulants') }}
                            </button>

                            <div v-if="form.form_data.current_treatment.anticoagulants" class="mt-4">
                                <InputLabel :value="t('anamnesis.anticoagulant_inr')" />
                                <input v-model="form.form_data.current_treatment.anticoagulant_inr" type="text" class="mt-1 block w-full max-w-xs rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="e.g., 2.5" />
                            </div>
                        </div>

                        <!-- Bisphosphonates -->
                        <div class="rounded-lg border border-gray-200 p-4">
                            <button
                                type="button"
                                @click="form.form_data.current_treatment.bisphosphonates = !form.form_data.current_treatment.bisphosphonates"
                                :class="[
                                    form.form_data.current_treatment.bisphosphonates
                                        ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                    'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                ]"
                            >
                                <div :class="[form.form_data.current_treatment.bisphosphonates ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                    <CheckIcon v-if="form.form_data.current_treatment.bisphosphonates" class="h-3.5 w-3.5 text-white" />
                                </div>
                                {{ t('anamnesis.bisphosphonates') }}
                            </button>

                            <div v-if="form.form_data.current_treatment.bisphosphonates" class="mt-4 space-y-4">
                                <div>
                                    <InputLabel :value="t('anamnesis.bisphosphonate_route')" />
                                    <div class="mt-2 flex gap-3">
                                        <button
                                            v-for="opt in [{ value: 'oral', label: t('anamnesis.bisphosphonate_oral') }, { value: 'iv', label: t('anamnesis.bisphosphonate_iv') }]"
                                            :key="opt.value"
                                            type="button"
                                            @click="form.form_data.current_treatment.bisphosphonate_route = opt.value as 'oral' | 'iv'"
                                            :class="[
                                                form.form_data.current_treatment.bisphosphonate_route === opt.value
                                                    ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                                'flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium transition-colors',
                                            ]"
                                        >
                                            <div :class="[form.form_data.current_treatment.bisphosphonate_route === opt.value ? 'border-primary-600 bg-primary-600' : 'border-gray-300', 'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition-colors']">
                                                <div v-if="form.form_data.current_treatment.bisphosphonate_route === opt.value" class="h-1.5 w-1.5 rounded-full bg-white" />
                                            </div>
                                            {{ opt.label }}
                                        </button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :value="t('anamnesis.bisphosphonate_duration')" />
                                        <input v-model="form.form_data.current_treatment.bisphosphonate_duration" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                    <div>
                                        <InputLabel :value="t('anamnesis.bisphosphonate_beta_ctx')" />
                                        <input v-model="form.form_data.current_treatment.bisphosphonate_beta_ctx" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STEP 5: Diseases / Medical History                 -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-show="currentStep === 5" class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-2 text-xl font-bold text-gray-900">{{ t('anamnesis.section_4') }}</h2>
                        <p class="mb-6 text-sm text-gray-500">{{ t('anamnesis.step_diseases') }}</p>

                        <div class="space-y-6">
                            <!-- Loop over organ-system categories -->
                            <div v-for="cat in diseaseCategories" :key="cat.key" class="rounded-lg border border-gray-200 p-4">
                                <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-gray-500">{{ cat.label }}</h3>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <div v-for="field in cat.fields" :key="field.key">
                                        <button
                                            type="button"
                                            @click="(form.form_data.diseases as any)[cat.key][field.key] = !(form.form_data.diseases as any)[cat.key][field.key]"
                                            :class="[
                                                (form.form_data.diseases as any)[cat.key][field.key]
                                                    ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                                'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                            ]"
                                        >
                                            <div :class="[(form.form_data.diseases as any)[cat.key][field.key] ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                                <CheckIcon v-if="(form.form_data.diseases as any)[cat.key][field.key]" class="h-3.5 w-3.5 text-white" />
                                            </div>
                                            {{ field.label }}
                                        </button>

                                        <!-- Extra field for heart_failure NYHA -->
                                        <div v-if="field.extra === 'heart_failure_nyha' && (form.form_data.diseases as any)[cat.key][field.key]" class="mt-2 ml-7">
                                            <input
                                                v-model="form.form_data.diseases.heart.heart_failure_nyha"
                                                type="text"
                                                class="block w-full max-w-[120px] rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                :placeholder="t('anamnesis.heart_failure_nyha')"
                                            />
                                        </div>

                                        <!-- Extra field for hypertension max value -->
                                        <div v-if="field.extra === 'hypertension_max' && (form.form_data.diseases as any)[cat.key][field.key]" class="mt-2 ml-7">
                                            <input
                                                v-model="form.form_data.diseases.vascular.hypertension_max"
                                                type="text"
                                                class="block w-full max-w-[160px] rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                :placeholder="t('anamnesis.vascular_hypertension_max')"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Neoplasms -->
                            <div class="rounded-lg border border-gray-200 p-4">
                                <button
                                    type="button"
                                    @click="form.form_data.diseases.neoplasms = !form.form_data.diseases.neoplasms"
                                    :class="[
                                        form.form_data.diseases.neoplasms
                                            ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                        'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                    ]"
                                >
                                    <div :class="[form.form_data.diseases.neoplasms ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                        <CheckIcon v-if="form.form_data.diseases.neoplasms" class="h-3.5 w-3.5 text-white" />
                                    </div>
                                    {{ t('anamnesis.diseases_neoplasms') }}
                                </button>
                                <div v-if="form.form_data.diseases.neoplasms" class="mt-3 ml-7">
                                    <textarea v-model="form.form_data.diseases.neoplasms_details" rows="2" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :placeholder="t('anamnesis.neoplasms_details')" />
                                </div>
                            </div>

                            <!-- Other diseases -->
                            <div class="rounded-lg border border-gray-200 p-4">
                                <button
                                    type="button"
                                    @click="form.form_data.diseases.other_diseases = !form.form_data.diseases.other_diseases"
                                    :class="[
                                        form.form_data.diseases.other_diseases
                                            ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                        'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                    ]"
                                >
                                    <div :class="[form.form_data.diseases.other_diseases ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                        <CheckIcon v-if="form.form_data.diseases.other_diseases" class="h-3.5 w-3.5 text-white" />
                                    </div>
                                    {{ t('anamnesis.diseases_other') }}
                                </button>
                                <div v-if="form.form_data.diseases.other_diseases" class="mt-3 ml-7">
                                    <textarea v-model="form.form_data.diseases.other_diseases_details" rows="2" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :placeholder="t('anamnesis.other_diseases_details')" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STEP 6: Surgical & Dental History                  -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-show="currentStep === 6" class="space-y-6">
                    <!-- Surgical History -->
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-2 text-xl font-bold text-gray-900">{{ t('anamnesis.section_5') }}</h2>

                        <div class="space-y-6">
                            <div>
                                <InputLabel :value="t('anamnesis.previous_surgeries')" />
                                <textarea
                                    v-model="form.form_data.surgical_history.previous_surgeries"
                                    rows="3"
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    :placeholder="t('anamnesis.previous_surgeries_placeholder')"
                                />
                            </div>

                            <button
                                type="button"
                                @click="form.form_data.surgical_history.transfusions = !form.form_data.surgical_history.transfusions"
                                :class="[
                                    form.form_data.surgical_history.transfusions
                                        ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                    'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                ]"
                            >
                                <div :class="[form.form_data.surgical_history.transfusions ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                    <CheckIcon v-if="form.form_data.surgical_history.transfusions" class="h-3.5 w-3.5 text-white" />
                                </div>
                                {{ t('anamnesis.transfusions') }}
                            </button>

                            <div>
                                <InputLabel :value="t('anamnesis.surgery_complications')" />
                                <textarea
                                    v-model="form.form_data.surgical_history.surgery_complications"
                                    rows="2"
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    :placeholder="t('anamnesis.surgery_complications_placeholder')"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Dental History -->
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-2 text-xl font-bold text-gray-900">{{ t('anamnesis.section_6') }}</h2>

                        <div class="space-y-6">
                            <div>
                                <InputLabel :value="t('anamnesis.dental_anesthesia_types')" />
                                <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <button
                                        v-for="aType in [
                                            { key: 'local', label: t('anamnesis.anesthesia_local') },
                                            { key: 'plexal', label: t('anamnesis.anesthesia_plexal') },
                                            { key: 'troncular', label: t('anamnesis.anesthesia_troncular') },
                                            { key: 'general', label: t('anamnesis.anesthesia_general') },
                                        ]"
                                        :key="aType.key"
                                        type="button"
                                        @click="(form.form_data.dental_history.anesthesia_types as any)[aType.key] = !(form.form_data.dental_history.anesthesia_types as any)[aType.key]"
                                        :class="[
                                            (form.form_data.dental_history.anesthesia_types as any)[aType.key]
                                                ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                            'flex items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                        ]"
                                    >
                                        <div :class="[(form.form_data.dental_history.anesthesia_types as any)[aType.key] ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                            <CheckIcon v-if="(form.form_data.dental_history.anesthesia_types as any)[aType.key]" class="h-3.5 w-3.5 text-white" />
                                        </div>
                                        {{ aType.label }}
                                    </button>
                                </div>
                            </div>

                            <div>
                                <InputLabel :value="t('anamnesis.adverse_reactions')" />
                                <textarea
                                    v-model="form.form_data.dental_history.adverse_reactions"
                                    rows="2"
                                    class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    :placeholder="t('anamnesis.adverse_reactions_placeholder')"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STEP 7: Habits                                     -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-show="currentStep === 7" class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-2 text-xl font-bold text-gray-900">{{ t('anamnesis.section_7') }}</h2>

                        <div class="space-y-6">
                            <!-- Tobacco -->
                            <div class="rounded-lg border border-gray-200 p-4">
                                <button
                                    type="button"
                                    @click="form.form_data.habits.tobacco = !form.form_data.habits.tobacco"
                                    :class="[
                                        form.form_data.habits.tobacco
                                            ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                        'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                    ]"
                                >
                                    <div :class="[form.form_data.habits.tobacco ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                        <CheckIcon v-if="form.form_data.habits.tobacco" class="h-3.5 w-3.5 text-white" />
                                    </div>
                                    {{ t('anamnesis.tobacco') }}
                                </button>
                                <div v-if="form.form_data.habits.tobacco" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :value="t('anamnesis.tobacco_amount')" />
                                        <input v-model="form.form_data.habits.tobacco_amount" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                    <div>
                                        <InputLabel :value="t('anamnesis.tobacco_duration')" />
                                        <input v-model="form.form_data.habits.tobacco_duration" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                </div>
                            </div>

                            <!-- Alcohol -->
                            <div class="rounded-lg border border-gray-200 p-4">
                                <button
                                    type="button"
                                    @click="form.form_data.habits.alcohol = !form.form_data.habits.alcohol"
                                    :class="[
                                        form.form_data.habits.alcohol
                                            ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                        'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                    ]"
                                >
                                    <div :class="[form.form_data.habits.alcohol ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                        <CheckIcon v-if="form.form_data.habits.alcohol" class="h-3.5 w-3.5 text-white" />
                                    </div>
                                    {{ t('anamnesis.alcohol') }}
                                </button>
                                <div v-if="form.form_data.habits.alcohol" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :value="t('anamnesis.alcohol_amount')" />
                                        <input v-model="form.form_data.habits.alcohol_amount" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                    <div>
                                        <InputLabel :value="t('anamnesis.alcohol_duration')" />
                                        <input v-model="form.form_data.habits.alcohol_duration" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                </div>
                            </div>

                            <!-- Drugs -->
                            <div class="rounded-lg border border-gray-200 p-4">
                                <button
                                    type="button"
                                    @click="form.form_data.habits.drugs = !form.form_data.habits.drugs"
                                    :class="[
                                        form.form_data.habits.drugs
                                            ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                        'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left text-sm font-medium transition-colors',
                                    ]"
                                >
                                    <div :class="[form.form_data.habits.drugs ? 'border-primary-600 bg-primary-600' : 'border-gray-300 bg-white', 'flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors']">
                                        <CheckIcon v-if="form.form_data.habits.drugs" class="h-3.5 w-3.5 text-white" />
                                    </div>
                                    {{ t('anamnesis.drugs') }}
                                </button>
                                <div v-if="form.form_data.habits.drugs" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <InputLabel :value="t('anamnesis.drugs_amount')" />
                                        <input v-model="form.form_data.habits.drugs_amount" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                    <div>
                                        <InputLabel :value="t('anamnesis.drugs_duration')" />
                                        <input v-model="form.form_data.habits.drugs_duration" type="text" class="mt-1 block w-full rounded-lg border-gray-300 text-base shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════════════════════════════════════════════ -->
                <!-- STEP 8: GDPR Consent                               -->
                <!-- ═══════════════════════════════════════════════════ -->
                <div v-show="currentStep === 8" class="space-y-6">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <h2 class="mb-6 text-xl font-bold text-gray-900">{{ t('anamnesis.consent_title') }}</h2>

                        <div class="rounded-lg bg-gray-50 p-4 sm:p-6">
                            <p class="text-sm leading-relaxed text-gray-700">
                                {{ t('anamnesis.consent_text') }}
                            </p>
                        </div>

                        <div class="mt-6">
                            <button
                                type="button"
                                @click="form.consent_given = !form.consent_given"
                                :class="[
                                    form.consent_given
                                        ? 'border-green-500 bg-green-50 text-green-800 ring-1 ring-green-500'
                                        : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
                                    'flex w-full items-center gap-3 rounded-lg border px-4 py-4 text-left text-base font-medium transition-colors',
                                ]"
                            >
                                <div
                                    :class="[
                                        form.consent_given ? 'border-green-600 bg-green-600' : 'border-gray-300 bg-white',
                                        'flex h-6 w-6 shrink-0 items-center justify-center rounded border-2 transition-colors',
                                    ]"
                                >
                                    <CheckIcon v-if="form.consent_given" class="h-4 w-4 text-white" />
                                </div>
                                {{ t('anamnesis.consent_agree') }}
                            </button>
                            <InputError :message="form.errors.consent_given" class="mt-2" />
                        </div>
                    </div>
                </div>

                <!-- Navigation buttons -->
                <div class="mt-8 flex items-center justify-between">
                    <button
                        v-if="currentStep > 1"
                        type="button"
                        @click="prevStep"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-3 text-base font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        <ChevronLeftIcon class="h-5 w-5" />
                        {{ t('app.previous') }}
                    </button>
                    <div v-else />

                    <button
                        v-if="currentStep < totalSteps"
                        type="button"
                        @click="nextStep"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-5 py-3 text-base font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                    >
                        {{ t('app.next') }}
                        <ChevronRightIcon class="h-5 w-5" />
                    </button>

                    <button
                        v-if="currentStep === totalSteps"
                        type="submit"
                        :disabled="form.processing || !form.consent_given"
                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-green-500 transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg v-if="form.processing" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <CheckIcon v-else class="h-5 w-5" />
                        {{ form.processing ? t('anamnesis.submitting') : t('anamnesis.submit') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
