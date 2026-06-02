<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SignaturePad from '@/Components/SignaturePad.vue';
import type { Encounter } from '@/types';

const props = defineProps<{
    encounter: Encounter;
    open: boolean;
    currentUser: { id: number; name: string; has_signature: boolean };
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { t } = useI18n();
const step = ref<1 | 2 | 3>(1);
const patientSig = ref<string | null>(null);
const dentistSig = ref<string | null>(null);
const useStored = ref(false);
const submitting = ref(false);
const instrumentsConfirmed = ref(false);

function close() {
    step.value = 1;
    patientSig.value = null;
    dentistSig.value = null;
    useStored.value = false;
    instrumentsConfirmed.value = false;
    emit('close');
}

function submit() {
    if (!patientSig.value) return;
    submitting.value = true;
    if (!useStored.value && !dentistSig.value) {
        submitting.value = false;
        return;
    }
    router.post(
        route('encounters.sign', props.encounter.id),
        {
            patient_signature_data: patientSig.value,
            dentist_signature_data: useStored.value ? null : dentistSig.value,
            use_stored_dentist_signature: useStored.value,
        } as any,
        {
            onFinish: () => { submitting.value = false; },
        }
    );
}

function formatDate(dateStr: string): string {
    const d = new Date(dateStr);
    return [
        String(d.getDate()).padStart(2, '0'),
        String(d.getMonth() + 1).padStart(2, '0'),
        d.getFullYear(),
    ].join('/');
}

function patientName(): string {
    const p = props.encounter.patient;
    return `${p?.first_name ?? ''} ${p?.last_name ?? ''}`.trim();
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl">
                    <div class="flex items-center gap-2 border-b border-gray-200 px-6 py-4">
                        <span :class="['inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold', step >= 1 ? 'bg-primary-600 text-white' : 'bg-gray-200']">1</span>
                        <span class="text-sm">{{ t('encounter.sign_step_review') }}</span>
                        <span class="text-gray-300">·</span>
                        <span :class="['inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold', step >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-200']">2</span>
                        <span class="text-sm">{{ t('encounter.sign_step_patient') }}</span>
                        <span class="text-gray-300">·</span>
                        <span :class="['inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold', step >= 3 ? 'bg-primary-600 text-white' : 'bg-gray-200']">3</span>
                        <span class="text-sm">{{ t('encounter.sign_step_dentist') }}</span>
                    </div>

                    <!-- Step 1: Review -->
                    <div v-if="step === 1" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div class="rounded-md bg-yellow-50 border border-yellow-200 p-3 text-sm text-yellow-800">
                            ⚠️ {{ t('encounter.sign_warning') }}
                        </div>
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ t('encounter.date') }}</p>
                            <p>{{ formatDate(encounter.encounter_date) }}</p>
                        </div>
                        <div v-if="encounter.chief_complaint">
                            <p class="text-xs uppercase text-gray-500">{{ t('encounter.chief_complaint') }}</p>
                            <p>{{ encounter.chief_complaint }}</p>
                        </div>
                        <div v-if="encounter.diagnosis">
                            <p class="text-xs uppercase text-gray-500">{{ t('encounter.diagnosis') }}</p>
                            <p>{{ encounter.diagnosis }}</p>
                        </div>
                        <div v-if="encounter.clinical_notes">
                            <p class="text-xs uppercase text-gray-500">{{ t('encounter.clinical_notes') }}</p>
                            <p class="whitespace-pre-wrap">{{ encounter.clinical_notes }}</p>
                        </div>
                        <div v-if="encounter.treatments?.length">
                            <p class="text-xs uppercase text-gray-500">{{ t('treatment.title') }}</p>
                            <table class="w-full text-sm">
                                <thead><tr class="text-left text-xs text-gray-500"><th>Code</th><th>Tooth</th><th>Description</th><th class="text-right">Cost</th></tr></thead>
                                <tbody>
                                    <tr v-for="tr in encounter.treatments" :key="tr.id" class="border-t">
                                        <td>{{ tr.treatment_code }}</td>
                                        <td>{{ tr.tooth_number }}</td>
                                        <td>{{ tr.description }}</td>
                                        <td class="text-right">{{ tr.cost }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Step 2: Patient signature -->
                    <div v-else-if="step === 2" class="p-6 space-y-3">
                        <p class="text-sm">{{ t('encounter.sign_patient_text', { name: patientName() }) }}</p>
                        <SignaturePad v-model="patientSig" />
                        <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" v-model="instrumentsConfirmed" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span>{{ t('encounter.sign_patient_instruments_confirm') }}</span>
                        </label>
                    </div>

                    <!-- Step 3: Dentist signature -->
                    <div v-else class="p-6 space-y-3">
                        <p class="text-sm">{{ t('encounter.sign_dentist_text', { name: currentUser.name }) }}</p>
                        <label v-if="currentUser.has_signature" class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="useStored">
                            {{ t('encounter.sign_use_stored') }}
                        </label>
                        <SignaturePad v-if="!useStored" v-model="dentistSig" />
                    </div>

                    <div class="flex justify-between gap-2 border-t border-gray-200 px-6 py-4">
                        <button type="button" @click="close" class="rounded-lg border px-3 py-2 text-sm">
                            {{ t('encounter.sign_back_to_edit') }}
                        </button>
                        <div class="flex gap-2">
                            <button
                                v-if="step < 3"
                                type="button"
                                :disabled="step === 2 && (!patientSig || !instrumentsConfirmed)"
                                @click="step = (step + 1) as 1 | 2 | 3"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                {{ t('encounter.sign_continue') }}
                            </button>
                            <button
                                v-else
                                type="button"
                                :disabled="submitting || (!useStored && !dentistSig)"
                                @click="submit"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                            >
                                {{ t('encounter.sign_submit') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
