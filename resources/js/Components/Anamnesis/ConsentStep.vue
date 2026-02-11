<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ShieldCheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const { t } = useI18n();

const isChecked = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value),
});
</script>

<template>
    <div class="space-y-6">
        <!-- Consent header -->
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-100">
                <ShieldCheckIcon class="h-5 w-5 text-primary-600" aria-hidden="true" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">
                    {{ t('anamnesis.consent_title') }}
                </h3>
                <p class="mt-1 text-xs text-gray-500">GDPR</p>
            </div>
        </div>

        <!-- Consent text -->
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <p class="text-sm leading-relaxed text-gray-700">
                {{ t('anamnesis.consent_text') }}
            </p>
        </div>

        <!-- Consent checkbox -->
        <label class="flex cursor-pointer items-start gap-3">
            <input
                v-model="isChecked"
                type="checkbox"
                class="mt-0.5 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
            />
            <span class="text-sm font-medium text-gray-900">
                {{ t('anamnesis.consent_agree') }}
            </span>
        </label>
    </div>
</template>
