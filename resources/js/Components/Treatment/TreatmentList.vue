<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';
import type { Treatment } from '@/types';

const props = withDefaults(
    defineProps<{
        treatments: Treatment[];
        editable?: boolean;
    }>(),
    {
        editable: false,
    },
);

const emit = defineEmits<{
    edit: [treatment: Treatment];
    delete: [treatment: Treatment];
}>();

const { t } = useI18n();

function statusVariant(status: Treatment['status']): 'info' | 'warning' | 'success' {
    const map: Record<Treatment['status'], 'info' | 'warning' | 'success'> = {
        planned: 'info',
        in_progress: 'warning',
        completed: 'success',
    };
    return map[status];
}

function statusLabel(status: Treatment['status']): string {
    const map: Record<Treatment['status'], string> = {
        planned: t('treatment.status_planned'),
        in_progress: t('treatment.status_in_progress'),
        completed: t('treatment.status_completed'),
    };
    return map[status];
}

function surfaceLabel(surface?: Treatment['surface']): string {
    if (!surface) return '—';
    const map: Record<string, string> = {
        mesial: t('treatment.surface_mesial'),
        distal: t('treatment.surface_distal'),
        buccal: t('treatment.surface_buccal'),
        lingual: t('treatment.surface_lingual'),
        occlusal: t('treatment.surface_occlusal'),
        incisal: t('treatment.surface_incisal'),
    };
    return map[surface] ?? surface;
}

function formatCost(cost?: number): string {
    if (cost == null) return '—';
    return new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: 'RON',
        minimumFractionDigits: 2,
    }).format(cost);
}

function confirmDelete(treatment: Treatment) {
    emit('delete', treatment);
}
</script>

<template>
    <div v-if="treatments.length === 0" class="py-8 text-center text-sm text-gray-500">
        {{ t('treatment.no_treatments') }}
    </div>

    <div v-else class="overflow-hidden rounded-lg border border-gray-200">
        <!-- Desktop table -->
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        {{ t('treatment.tooth_number') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        {{ t('treatment.treatment_code') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        {{ t('treatment.description') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        {{ t('treatment.surface') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        {{ t('treatment.cost') }}
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                        {{ t('treatment.status') }}
                    </th>
                    <th
                        v-if="editable"
                        scope="col"
                        class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                    >
                        {{ t('app.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <tr
                    v-for="treatment in treatments"
                    :key="treatment.id"
                    class="hover:bg-gray-50"
                >
                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                        {{ treatment.tooth_number ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                        {{ treatment.treatment_code }}
                    </td>
                    <td class="max-w-xs truncate px-4 py-3 text-sm text-gray-700">
                        {{ treatment.description }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                        {{ surfaceLabel(treatment.surface) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                        {{ formatCost(treatment.cost) }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                        <Badge
                            :variant="statusVariant(treatment.status)"
                            :label="statusLabel(treatment.status)"
                        />
                    </td>
                    <td
                        v-if="editable"
                        class="whitespace-nowrap px-4 py-3 text-right text-sm"
                    >
                        <div class="flex items-center justify-end gap-2">
                            <button
                                type="button"
                                :title="t('app.edit')"
                                class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                @click="emit('edit', treatment)"
                            >
                                <PencilSquareIcon class="h-4 w-4" aria-hidden="true" />
                                <span class="sr-only">{{ t('app.edit') }}</span>
                            </button>
                            <button
                                type="button"
                                :title="t('app.delete')"
                                class="rounded p-1 text-gray-400 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500"
                                @click="confirmDelete(treatment)"
                            >
                                <TrashIcon class="h-4 w-4" aria-hidden="true" />
                                <span class="sr-only">{{ t('app.delete') }}</span>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
