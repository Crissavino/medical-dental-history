<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    CalendarDaysIcon,
    UserIcon,
    ClipboardDocumentListIcon,
} from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';
import type { Encounter } from '@/types';

const props = defineProps<{
    encounter: Encounter;
}>();

const { t } = useI18n();

const formattedDate = computed(() => {
    return new Date(props.encounter.encounter_date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
});

const treatmentCount = computed(() => {
    return props.encounter.treatments?.length ?? 0;
});

const statusVariant = computed(() => {
    const map: Record<Encounter['status'], 'info' | 'warning' | 'success' | 'danger'> = {
        scheduled: 'info',
        in_progress: 'warning',
        completed: 'success',
        cancelled: 'danger',
    };
    return map[props.encounter.status];
});

const statusLabel = computed(() => {
    const map: Record<Encounter['status'], string> = {
        scheduled: t('encounter.status_scheduled'),
        in_progress: t('encounter.status_in_progress'),
        completed: t('encounter.status_completed'),
        cancelled: t('encounter.status_cancelled'),
    };
    return map[props.encounter.status];
});
</script>

<template>
    <Link
        :href="route('encounters.show', encounter.id)"
        class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:border-primary-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
                <!-- Date and status -->
                <div class="flex items-center gap-2">
                    <CalendarDaysIcon class="h-4 w-4 shrink-0 text-gray-400" aria-hidden="true" />
                    <span class="text-sm font-medium text-gray-900">{{ formattedDate }}</span>
                    <Badge :variant="statusVariant" :label="statusLabel" />
                </div>

                <!-- Provider -->
                <div v-if="encounter.provider" class="mt-2 flex items-center gap-1.5 text-xs text-gray-500">
                    <UserIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    <span>{{ encounter.provider.name }}</span>
                </div>

                <!-- Chief complaint -->
                <p
                    v-if="encounter.chief_complaint"
                    class="mt-2 line-clamp-2 text-sm text-gray-600"
                >
                    {{ encounter.chief_complaint }}
                </p>
            </div>

            <!-- Treatment count -->
            <div
                v-if="treatmentCount > 0"
                class="flex shrink-0 items-center gap-1 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600"
                :title="t('app.treatments')"
            >
                <ClipboardDocumentListIcon class="h-3.5 w-3.5" aria-hidden="true" />
                <span>{{ treatmentCount }}</span>
            </div>
        </div>
    </Link>
</template>
