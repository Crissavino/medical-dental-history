<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SignaturePad from '@/Components/SignaturePad.vue';

const props = defineProps<{
    encounterId: number;
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { t, locale } = useI18n();
const patientSig = ref<string | null>(null);
const understood = ref(false);
const submitting = ref(false);

function close() {
    patientSig.value = null;
    understood.value = false;
    emit('close');
}

function submit() {
    if (!patientSig.value || !understood.value) return;
    submitting.value = true;
    router.post(
        route('extraction-consents.store', props.encounterId),
        {
            patient_signature_data: patientSig.value,
            language: locale.value,
        } as any,
        {
            onSuccess: () => close(),
            onFinish: () => { submitting.value = false; },
        }
    );
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
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">{{ t('extractionConsent.title') }}</h3>
                    </div>

                    <div class="max-h-[70vh] space-y-4 overflow-y-auto p-6">
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <p class="text-sm leading-relaxed text-gray-700">{{ t('extractionConsent.text') }}</p>
                        </div>
                        <SignaturePad v-model="patientSig" />
                        <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                            <input type="checkbox" v-model="understood" class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span>{{ t('extractionConsent.agree') }}</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                        <button type="button" @click="close" class="rounded-lg border px-3 py-2 text-sm">
                            {{ t('app.cancel') }}
                        </button>
                        <button
                            type="button"
                            :disabled="submitting || !patientSig || !understood"
                            @click="submit"
                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                        >
                            {{ t('extractionConsent.sign_button') }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
