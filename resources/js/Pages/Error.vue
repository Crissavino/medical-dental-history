<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps<{
    status: number;
}>();

const title = computed(() => {
    const titles: Record<number, string> = {
        403: t('errors.403_title', 'Forbidden'),
        404: t('errors.404_title', 'Page Not Found'),
        500: t('errors.500_title', 'Server Error'),
        503: t('errors.503_title', 'Service Unavailable'),
    };
    return titles[props.status] ?? t('errors.unknown_title', 'Error');
});

const description = computed(() => {
    const descriptions: Record<number, string> = {
        403: t('errors.403_description', 'You are not authorized to access this page.'),
        404: t('errors.404_description', 'The page you are looking for could not be found.'),
        500: t('errors.500_description', 'Something went wrong on our end. Please try again later.'),
        503: t('errors.503_description', 'We are performing maintenance. Please check back soon.'),
    };
    return descriptions[props.status] ?? t('errors.unknown_description', 'An unexpected error occurred.');
});
</script>

<template>
    <GuestLayout>
        <Head :title="`${status} - ${title}`" />

        <div class="text-center">
            <p class="text-7xl font-bold text-primary-600">{{ status }}</p>
            <h1 class="mt-4 text-2xl font-semibold text-gray-900">{{ title }}</h1>
            <p class="mt-2 text-gray-500">{{ description }}</p>

            <div class="mt-8">
                <Link
                    href="/"
                    class="inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 transition-colors"
                >
                    {{ t('errors.go_home', 'Go back home') }}
                </Link>
            </div>
        </div>
    </GuestLayout>
</template>
