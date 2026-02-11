<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    CheckCircleIcon,
    ClockIcon,
    XCircleIcon,
    CalendarIcon,
} from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';
import type { Encounter } from '@/types';

const props = defineProps<{
    encounters: Encounter[];
}>();

const { t } = useI18n();

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

interface StatusConfig {
    variant: 'info' | 'warning' | 'success' | 'danger';
    label: string;
    icon: typeof CheckCircleIcon;
    dotColor: string;
}

function getStatusConfig(status: Encounter['status']): StatusConfig {
    const configs: Record<Encounter['status'], StatusConfig> = {
        scheduled: {
            variant: 'info',
            label: t('encounter.status_scheduled'),
            icon: CalendarIcon,
            dotColor: 'bg-blue-500',
        },
        in_progress: {
            variant: 'warning',
            label: t('encounter.status_in_progress'),
            icon: ClockIcon,
            dotColor: 'bg-yellow-500',
        },
        completed: {
            variant: 'success',
            label: t('encounter.status_completed'),
            icon: CheckCircleIcon,
            dotColor: 'bg-green-500',
        },
        cancelled: {
            variant: 'danger',
            label: t('encounter.status_cancelled'),
            icon: XCircleIcon,
            dotColor: 'bg-red-500',
        },
    };
    return configs[status];
}
</script>

<template>
    <div v-if="encounters.length === 0" class="py-8 text-center text-sm text-gray-500">
        {{ t('encounter.no_encounters') }}
    </div>

    <div v-else class="flow-root">
        <ul role="list" class="-mb-8">
            <li
                v-for="(encounter, index) in encounters"
                :key="encounter.id"
                class="relative pb-8"
            >
                <!-- Connector line -->
                <span
                    v-if="index < encounters.length - 1"
                    class="absolute left-4 top-5 -ml-px h-full w-0.5 bg-gray-200"
                    aria-hidden="true"
                />

                <Link
                    :href="route('encounters.show', encounter.id)"
                    class="group relative flex items-start gap-3"
                >
                    <!-- Timeline dot -->
                    <div class="relative flex h-8 w-8 shrink-0 items-center justify-center">
                        <component
                            :is="getStatusConfig(encounter.status).icon"
                            class="h-5 w-5"
                            :class="{
                                'text-blue-500': encounter.status === 'scheduled',
                                'text-yellow-500': encounter.status === 'in_progress',
                                'text-green-500': encounter.status === 'completed',
                                'text-red-500': encounter.status === 'cancelled',
                            }"
                            aria-hidden="true"
                        />
                    </div>

                    <!-- Content -->
                    <div class="min-w-0 flex-1 rounded-lg border border-transparent p-3 transition-colors group-hover:border-gray-200 group-hover:bg-gray-50">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-gray-900">
                                {{ formatDate(encounter.encounter_date) }}
                            </span>
                            <Badge
                                :variant="getStatusConfig(encounter.status).variant"
                                :label="getStatusConfig(encounter.status).label"
                            />
                        </div>

                        <p
                            v-if="encounter.provider"
                            class="mt-1 text-xs text-gray-500"
                        >
                            {{ t('encounter.provider') }}: {{ encounter.provider.name }}
                        </p>

                        <p
                            v-if="encounter.chief_complaint"
                            class="mt-1 line-clamp-2 text-sm text-gray-600"
                        >
                            {{ encounter.chief_complaint }}
                        </p>
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
