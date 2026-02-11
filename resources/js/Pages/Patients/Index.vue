<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useResponsive } from '@/Composables/useResponsive';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import type { Patient, PaginatedResponse, PaginationLink } from '@/types';
import {
    MagnifyingGlassIcon,
    PlusIcon,
    ChevronRightIcon,
    EyeIcon,
    PencilSquareIcon,
    PhoneIcon,
    EnvelopeIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const { isMobile } = useResponsive();

const props = defineProps<{
    patients: PaginatedResponse<Patient>;
    filters: {
        search?: string;
    };
}>();

const search = ref(props.filters.search || '');
let debounceTimer: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.get(
            route('patients.index'),
            { search: value || undefined },
            { preserveState: true, replace: true },
        );
    }, 300);
});

function formatDate(dateStr?: string): string {
    if (!dateStr) return '---';
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head :title="t('patient.title')" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold text-gray-900">{{ t('patient.title') }}</h1>
                <Link
                    :href="route('patients.create')"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                >
                    <PlusIcon class="h-5 w-5" />
                    {{ t('patient.new') }}
                </Link>
            </div>
        </template>

        <!-- Search bar -->
        <div class="mb-6">
            <div class="relative">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                <input
                    v-model="search"
                    type="text"
                    :placeholder="t('patient.search_placeholder')"
                    class="block w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-500 focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
                />
            </div>
        </div>

        <!-- Desktop table view -->
        <div v-if="!isMobile" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ t('patient.identifier') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ t('patient.full_name') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ t('patient.phone') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ t('patient.email') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ t('patient.created_at') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ t('app.actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr
                        v-for="patient in patients.data"
                        :key="patient.id"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-500">
                            {{ patient.identifier }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <Link :href="route('patients.show', patient.id)" class="group flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700">
                                    {{ patient.first_name.charAt(0) }}{{ patient.last_name.charAt(0) }}
                                </div>
                                <span class="text-sm font-medium text-gray-900 group-hover:text-primary-600 transition-colors">
                                    {{ patient.full_name || `${patient.first_name} ${patient.last_name}` }}
                                </span>
                            </Link>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ patient.phone || '---' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ patient.email || '---' }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                            {{ formatDate(patient.created_at) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="route('patients.show', patient.id)"
                                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-primary-600 transition-colors"
                                    :title="t('app.view')"
                                >
                                    <EyeIcon class="h-4 w-4" />
                                </Link>
                                <Link
                                    :href="route('patients.edit', patient.id)"
                                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-primary-600 transition-colors"
                                    :title="t('app.edit')"
                                >
                                    <PencilSquareIcon class="h-4 w-4" />
                                </Link>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="patients.data.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500">
                            {{ t('app.no_results') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile card view -->
        <div v-else class="space-y-3">
            <div
                v-for="patient in patients.data"
                :key="patient.id"
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"
            >
                <Link :href="route('patients.show', patient.id)" class="block p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">
                                {{ patient.first_name.charAt(0) }}{{ patient.last_name.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ patient.full_name || `${patient.first_name} ${patient.last_name}` }}
                                </p>
                                <p class="text-xs text-gray-500">{{ patient.identifier }}</p>
                            </div>
                        </div>
                        <ChevronRightIcon class="h-5 w-5 text-gray-400" />
                    </div>
                    <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-500">
                        <span v-if="patient.phone" class="flex items-center gap-1">
                            <PhoneIcon class="h-3.5 w-3.5" />
                            {{ patient.phone }}
                        </span>
                        <span v-if="patient.email" class="flex items-center gap-1">
                            <EnvelopeIcon class="h-3.5 w-3.5" />
                            {{ patient.email }}
                        </span>
                    </div>
                </Link>
            </div>
            <div v-if="patients.data.length === 0" class="rounded-xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500 shadow-sm">
                {{ t('app.no_results') }}
            </div>
        </div>

        <!-- Pagination -->
        <nav v-if="patients.last_page > 1" class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
            <p class="text-sm text-gray-500">
                {{ patients.total }} {{ t('patient.title').toLowerCase() }}
            </p>
            <div class="flex gap-1">
                <template v-for="link in patients.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'bg-primary-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50',
                            'inline-flex h-9 min-w-[36px] items-center justify-center rounded-md border border-gray-300 px-3 text-sm font-medium transition-colors',
                        ]"
                        v-html="link.label"
                        preserve-state
                    />
                    <span
                        v-else
                        class="inline-flex h-9 min-w-[36px] cursor-not-allowed items-center justify-center rounded-md border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-400"
                        v-html="link.label"
                    />
                </template>
            </div>
        </nav>
    </AppLayout>
</template>
