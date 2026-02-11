<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { CheckCircleIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
</script>

<template>
    <Head :title="t('anamnesis.title')" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-md text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
                <CheckCircleIcon class="h-12 w-12 text-green-600" />
            </div>

            <h1 class="mt-6 text-2xl font-bold text-gray-900">
                {{ t('anamnesis.title') }}
            </h1>

            <p class="mt-4 text-base text-gray-600">
                Your information has been submitted successfully.
            </p>

            <p class="mt-2 text-sm text-gray-500">
                Thank you for completing the intake form. Your dental team will review your information before your appointment.
            </p>

            <div class="mt-8">
                <Link
                    :href="route('intake.show')"
                    class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    {{ t('app.back') }}
                </Link>
            </div>

            <div class="mt-6 flex items-center justify-center gap-2">
                <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-primary-600">
                    <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                    </svg>
                </div>
                <span class="text-sm font-medium text-gray-500">{{ t('app.name') }}</span>
            </div>
        </div>
    </div>
</template>
