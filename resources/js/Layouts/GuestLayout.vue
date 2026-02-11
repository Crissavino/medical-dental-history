<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
</script>

<template>
    <div class="flex min-h-screen">
        <!-- Left branding panel (desktop only) -->
        <div class="hidden lg:flex lg:w-1/2 lg:flex-col lg:justify-between bg-primary-700 p-12">
            <div>
                <Link href="/">
                    <ApplicationLogo :dark="true" />
                </Link>
            </div>
            <div>
                <p class="font-serif text-3xl font-bold text-white leading-tight">
                    {{ t('landing.tagline') }}
                </p>
                <p class="mt-4 text-primary-200 text-sm">
                    {{ t('landing.subtitle') }}
                </p>
            </div>
            <div class="text-sm text-primary-300">
                {{ t('landing.contact_address') }}
            </div>
        </div>

        <!-- Right form panel -->
        <div class="flex w-full flex-col items-center justify-center bg-white px-6 py-12 lg:w-1/2">
            <!-- Mobile logo -->
            <div class="mb-8 lg:hidden">
                <Link href="/">
                    <ApplicationLogo />
                </Link>
            </div>

            <div class="w-full max-w-md">
                <slot />
            </div>

            <div class="mt-8">
                <Link
                    href="/"
                    class="text-sm text-gray-500 hover:text-primary-600 transition-colors"
                >
                    {{ t('auth.back_to_home') }}
                </Link>
            </div>
        </div>
    </div>
</template>
