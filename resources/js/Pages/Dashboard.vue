<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import type { Patient, Encounter } from '@/types';
import {
    UsersIcon,
    CalendarDaysIcon,
    ClockIcon,
    ChevronRightIcon,
    UserPlusIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

interface DashboardStats {
    total_patients: number;
    today_encounters: number;
    pending_encounters: number;
}

const props = defineProps<{
    recentPatients: Patient[];
    todayEncounters: Encounter[];
    stats: DashboardStats;
}>();

const statusColors: Record<string, string> = {
    scheduled: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatTime(dateStr: string): string {
    return new Date(dateStr).toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <Head :title="t('app.dashboard')" />

    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900">
                {{ t('dashboard.welcome') }}, {{ $page.props.auth.user.name }}
            </h1>
        </template>

        <!-- Quick Actions -->
        <div class="mb-6 flex flex-wrap gap-3">
            <Link
                :href="route('patients.create')"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 transition-colors"
            >
                <UserPlusIcon class="h-5 w-5" />
                {{ t('dashboard.new_patient') }}
            </Link>
            <Link
                :href="route('intake.show')"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
            >
                <ClipboardDocumentListIcon class="h-5 w-5" />
                {{ t('dashboard.patient_intake') }}
            </Link>
        </div>

        <!-- Stats cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <!-- Total Patients -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-50">
                            <UsersIcon class="h-6 w-6 text-primary-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">{{ t('dashboard.total_patients') }}</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.total_patients }}</p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 bg-gray-50 px-5 py-3">
                    <Link
                        :href="route('patients.index')"
                        class="text-sm font-medium text-primary-600 hover:text-primary-800 transition-colors"
                    >
                        {{ t('app.view') }} {{ t('app.patients').toLowerCase() }}
                        <ChevronRightIcon class="ml-1 inline h-4 w-4" />
                    </Link>
                </div>
            </div>

            <!-- Today's Encounters -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-50">
                            <CalendarDaysIcon class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">{{ t('dashboard.today_encounters') }}</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.today_encounters }}</p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 bg-gray-50 px-5 py-3">
                    <Link
                        :href="route('patients.index')"
                        class="text-sm font-medium text-green-600 hover:text-green-800 transition-colors"
                    >
                        {{ t('app.view') }} {{ t('app.encounters').toLowerCase() }}
                        <ChevronRightIcon class="ml-1 inline h-4 w-4" />
                    </Link>
                </div>
            </div>

            <!-- Pending Encounters -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm sm:col-span-2 lg:col-span-1">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-50">
                            <ClockIcon class="h-6 w-6 text-amber-600" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">{{ t('dashboard.pending_encounters') }}</p>
                            <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats.pending_encounters }}</p>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 bg-gray-50 px-5 py-3">
                    <Link
                        :href="route('patients.index')"
                        class="text-sm font-medium text-amber-600 hover:text-amber-800 transition-colors"
                    >
                        {{ t('app.view') }} {{ t('dashboard.pending_encounters').toLowerCase() }}
                        <ChevronRightIcon class="ml-1 inline h-4 w-4" />
                    </Link>
                </div>
            </div>
        </div>

        <!-- Content grid -->
        <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Recent Patients -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h2 class="text-base font-semibold text-gray-900">{{ t('dashboard.recent_patients') }}</h2>
                    <Link
                        :href="route('patients.index')"
                        class="text-sm font-medium text-primary-600 hover:text-primary-800 transition-colors"
                    >
                        {{ t('app.view') }}
                    </Link>
                </div>
                <ul v-if="recentPatients.length > 0" class="divide-y divide-gray-100">
                    <li v-for="patient in recentPatients" :key="patient.id">
                        <Link
                            :href="route('patients.show', patient.id)"
                            class="flex items-center gap-4 px-6 py-3 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">
                                {{ patient.first_name.charAt(0) }}{{ patient.last_name.charAt(0) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">
                                    {{ patient.full_name || `${patient.first_name} ${patient.last_name}` }}
                                </p>
                                <p class="truncate text-xs text-gray-500">
                                    {{ patient.identifier }} &middot; {{ patient.phone || patient.email || '---' }}
                                </p>
                            </div>
                            <ChevronRightIcon class="h-5 w-5 shrink-0 text-gray-400" />
                        </Link>
                    </li>
                </ul>
                <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
                    {{ t('app.no_results') }}
                </div>
            </div>

            <!-- Today's Encounters -->
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h2 class="text-base font-semibold text-gray-900">{{ t('dashboard.today_encounters') }}</h2>
                    <Link
                        :href="route('patients.index')"
                        class="text-sm font-medium text-primary-600 hover:text-primary-800 transition-colors"
                    >
                        {{ t('app.view') }}
                    </Link>
                </div>
                <ul v-if="todayEncounters.length > 0" class="divide-y divide-gray-100">
                    <li v-for="encounter in todayEncounters" :key="encounter.id">
                        <Link
                            :href="route('encounters.show', encounter.id)"
                            class="flex items-center gap-4 px-6 py-3 hover:bg-gray-50 transition-colors"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-medium text-gray-900">
                                        {{ encounter.patient?.full_name || `${encounter.patient?.first_name} ${encounter.patient?.last_name}` }}
                                    </p>
                                    <span
                                        :class="[statusColors[encounter.status], 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium']"
                                    >
                                        {{ t(`encounter.status_${encounter.status}`) }}
                                    </span>
                                </div>
                                <p class="mt-0.5 truncate text-xs text-gray-500">
                                    {{ formatTime(encounter.encounter_date) }}
                                    <span v-if="encounter.chief_complaint"> &middot; {{ encounter.chief_complaint }}</span>
                                </p>
                            </div>
                            <ChevronRightIcon class="h-5 w-5 shrink-0 text-gray-400" />
                        </Link>
                    </li>
                </ul>
                <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
                    {{ t('dashboard.no_encounters_today') }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
