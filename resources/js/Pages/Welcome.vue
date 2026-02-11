<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    ShieldCheckIcon,
    DocumentTextIcon,
    ClipboardDocumentCheckIcon,
} from '@heroicons/vue/24/outline';

const { t, locale } = useI18n();

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();

function toggleLocale() {
    locale.value = locale.value === 'en' ? 'ro' : 'en';
}

const features = [
    { icon: ShieldCheckIcon, titleKey: 'landing.feature_care_title', descKey: 'landing.feature_care_desc' },
    { icon: DocumentTextIcon, titleKey: 'landing.feature_records_title', descKey: 'landing.feature_records_desc' },
    { icon: ClipboardDocumentCheckIcon, titleKey: 'landing.feature_checkin_title', descKey: 'landing.feature_checkin_desc' },
];
</script>

<template>
    <Head title="Dental Wellness Clinic" />

    <div class="min-h-screen bg-white">
        <!-- Header -->
        <header class="border-b border-gray-100">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <!-- Tooth icon -->
                    <svg class="h-8 w-8 text-primary-600" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C9.5 2 7.5 3.5 6.5 5C5.5 3.5 3.5 2 1 2C1 7 3 9 5 11C5.5 12.5 5 15 5.5 18C6 21 8 22 9 22C10 22 10.5 21 11 19C11.5 17 12 17 12 17C12 17 12.5 17 13 19C13.5 21 14 22 15 22C16 22 18 21 18.5 18C19 15 18.5 12.5 19 11C21 9 23 7 23 2C20.5 2 18.5 3.5 17.5 5C16.5 3.5 14.5 2 12 2Z" />
                    </svg>
                    <span class="text-xl font-bold text-gray-900">Dental Wellness</span>
                </div>

                <div class="flex items-center gap-4">
                    <button
                        @click="toggleLocale"
                        class="rounded-md px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors"
                    >
                        {{ locale === 'en' ? 'RO' : 'EN' }}
                    </button>
                    <Link
                        v-if="canLogin"
                        :href="route('login')"
                        class="rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition-colors"
                    >
                        {{ t('landing.staff_login') }}
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden bg-gradient-to-br from-primary-50 via-white to-primary-50">
            <div class="mx-auto max-w-7xl px-6 py-24 sm:py-32 lg:py-40">
                <div class="mx-auto max-w-2xl text-center">
                    <h1 class="font-serif text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl lg:text-6xl">
                        {{ t('landing.tagline') }}
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-gray-600">
                        {{ t('landing.subtitle') }}
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-4">
                        <Link
                            :href="route('intake.show')"
                            class="rounded-lg bg-primary-600 px-6 py-3 text-sm font-semibold text-white shadow-md hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 transition-colors"
                        >
                            {{ t('landing.patient_intake') }}
                        </Link>
                        <Link
                            v-if="canLogin"
                            :href="route('login')"
                            class="rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                        >
                            {{ t('landing.staff_login') }}
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="bg-white py-20 sm:py-24">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.titleKey"
                        class="rounded-2xl border border-gray-100 bg-gray-50 p-8 text-center"
                    >
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-primary-100">
                            <component :is="feature.icon" class="h-7 w-7 text-primary-600" />
                        </div>
                        <h3 class="mt-5 text-lg font-semibold text-gray-900">
                            {{ t(feature.titleKey) }}
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-gray-600">
                            {{ t(feature.descKey) }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gray-100 bg-gray-50">
            <div class="mx-auto max-w-7xl px-6 py-12">
                <div class="flex flex-col items-center gap-6 sm:flex-row sm:justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="h-6 w-6 text-primary-600" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C9.5 2 7.5 3.5 6.5 5C5.5 3.5 3.5 2 1 2C1 7 3 9 5 11C5.5 12.5 5 15 5.5 18C6 21 8 22 9 22C10 22 10.5 21 11 19C11.5 17 12 17 12 17C12 17 12.5 17 13 19C13.5 21 14 22 15 22C16 22 18 21 18.5 18C19 15 18.5 12.5 19 11C21 9 23 7 23 2C20.5 2 18.5 3.5 17.5 5C16.5 3.5 14.5 2 12 2Z" />
                        </svg>
                        <span class="font-semibold text-gray-900">Dental Wellness Clinic</span>
                    </div>
                    <div class="flex flex-col items-center gap-2 text-sm text-gray-500 sm:items-end">
                        <p>{{ t('landing.contact_address') }}</p>
                        <p>
                            {{ t('landing.contact_phone') }}
                            <span class="mx-2">|</span>
                            {{ t('landing.contact_email') }}
                        </p>
                        <a
                            href="https://instagram.com/dentalwellness.ro"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-primary-600 hover:text-primary-700 transition-colors"
                        >
                            @dentalwellness.ro
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
