<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import { useToothNotation } from '@/Composables/useToothNotation';
import type { Encounter } from '@/types';
import {
    PencilSquareIcon,
    TrashIcon,
    CalendarDaysIcon,
    UserIcon,
    ClipboardDocumentListIcon,
    PaperClipIcon,
    ArrowDownTrayIcon,
    ChevronRightIcon,
    PlusIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const { getToothName } = useToothNotation();

const props = defineProps<{
    encounter: Encounter;
}>();

const showDeleteModal = ref(false);

const statusColors: Record<string, string> = {
    scheduled: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

const treatmentStatusColors: Record<string, string> = {
    planned: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-green-100 text-green-800',
};

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}

function formatDateTime(dateStr: string): string {
    return new Date(dateStr).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function formatCost(cost?: number): string {
    if (cost === undefined || cost === null) return '---';
    return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'RON' }).format(cost);
}

function confirmDelete() {
    showDeleteModal.value = true;
}

function deleteEncounter() {
    router.delete(route('encounters.destroy', props.encounter.id));
}
</script>

<template>
    <Head :title="`${t('encounter.details')} #${encounter.id}`" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('patients.show', encounter.patient_id)"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h1 class="text-lg font-semibold text-gray-900">{{ t('encounter.details') }}</h1>
            </div>
        </template>

        <!-- Encounter header -->
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-bold text-gray-900">
                            {{ encounter.patient?.full_name || `${encounter.patient?.first_name} ${encounter.patient?.last_name}` }}
                        </h2>
                        <span
                            :class="[statusColors[encounter.status], 'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold']"
                        >
                            {{ t(`encounter.status_${encounter.status}`) }}
                        </span>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-600">
                        <span class="flex items-center gap-1.5">
                            <CalendarDaysIcon class="h-4 w-4 text-gray-400" />
                            {{ formatDate(encounter.encounter_date) }}
                        </span>
                        <span v-if="encounter.provider" class="flex items-center gap-1.5">
                            <UserIcon class="h-4 w-4 text-gray-400" />
                            {{ encounter.provider.name }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('encounters.edit', encounter.id)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        <PencilSquareIcon class="h-4 w-4" />
                        {{ t('app.edit') }}
                    </Link>
                    <button
                        type="button"
                        @click="confirmDelete"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-medium text-red-700 shadow-sm hover:bg-red-50 transition-colors"
                    >
                        <TrashIcon class="h-4 w-4" />
                        {{ t('app.delete') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main content -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Chief Complaint -->
                <div v-if="encounter.chief_complaint" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ t('encounter.chief_complaint') }}</h3>
                    <p class="mt-2 text-gray-900">{{ encounter.chief_complaint }}</p>
                </div>

                <!-- Clinical Notes -->
                <div v-if="encounter.clinical_notes" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ t('encounter.clinical_notes') }}</h3>
                    <p class="mt-2 whitespace-pre-wrap text-gray-900">{{ encounter.clinical_notes }}</p>
                </div>

                <!-- Diagnosis -->
                <div v-if="encounter.diagnosis" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ t('encounter.diagnosis') }}</h3>
                    <p class="mt-2 text-gray-900">{{ encounter.diagnosis }}</p>
                </div>

                <!-- Treatments -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h3 class="text-base font-semibold text-gray-900">
                            {{ t('treatment.title') }}
                            <span v-if="encounter.treatments?.length" class="ml-1 text-sm font-normal text-gray-500">
                                ({{ encounter.treatments.length }})
                            </span>
                        </h3>
                        <Link
                            :href="route('encounters.edit', encounter.id)"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                        >
                            <PlusIcon class="h-4 w-4" />
                            {{ t('encounter.add_treatment') }}
                        </Link>
                    </div>

                    <div v-if="encounter.treatments && encounter.treatments.length > 0">
                        <div
                            v-for="(treatment, index) in encounter.treatments"
                            :key="treatment.id"
                            :class="['px-6 py-4', index > 0 ? 'border-t border-gray-100' : '']"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-900">{{ treatment.treatment_code }}</span>
                                        <span class="text-sm text-gray-600">{{ treatment.description }}</span>
                                        <span
                                            :class="[treatmentStatusColors[treatment.status], 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium']"
                                        >
                                            {{ t(`treatment.status_${treatment.status}`) }}
                                        </span>
                                    </div>
                                    <div class="mt-1 flex flex-wrap gap-3 text-xs text-gray-500">
                                        <span v-if="treatment.tooth_number">
                                            {{ t('treatment.tooth_number') }}: {{ treatment.tooth_number }} ({{ getToothName(treatment.tooth_number) }})
                                        </span>
                                        <span v-if="treatment.surface">
                                            {{ t('treatment.surface') }}: {{ t(`treatment.surface_${treatment.surface}`) }}
                                        </span>
                                        <span v-if="treatment.cost !== undefined && treatment.cost !== null">
                                            {{ t('treatment.cost') }}: {{ formatCost(treatment.cost) }}
                                        </span>
                                    </div>
                                    <p v-if="treatment.notes" class="mt-1 text-sm text-gray-500">{{ treatment.notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="p-6 text-center text-sm text-gray-500">
                        {{ t('treatment.no_treatments') }}
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Patient info card -->
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ t('patient.details') }}</h3>
                    <Link
                        :href="route('patients.show', encounter.patient_id)"
                        class="mt-3 flex items-center gap-3 rounded-lg p-2 -mx-2 hover:bg-gray-50 transition-colors"
                    >
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700">
                            {{ encounter.patient?.first_name?.charAt(0) }}{{ encounter.patient?.last_name?.charAt(0) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-900">
                                {{ encounter.patient?.full_name || `${encounter.patient?.first_name} ${encounter.patient?.last_name}` }}
                            </p>
                            <p class="text-xs text-gray-500">{{ encounter.patient?.identifier }}</p>
                        </div>
                        <ChevronRightIcon class="h-4 w-4 text-gray-400" />
                    </Link>
                </div>

                <!-- Attachments -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">{{ t('attachment.title') }}</h3>
                    </div>
                    <div v-if="encounter.attachments && encounter.attachments.length > 0" class="divide-y divide-gray-100">
                        <div
                            v-for="attachment in encounter.attachments"
                            :key="attachment.id"
                            class="flex items-center gap-3 px-5 py-3"
                        >
                            <PaperClipIcon class="h-5 w-5 shrink-0 text-gray-400" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">{{ attachment.file_name }}</p>
                                <p class="text-xs text-gray-500">{{ formatFileSize(attachment.file_size) }}</p>
                            </div>
                            <a
                                :href="route('attachments.show', attachment.id)"
                                class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-primary-600 transition-colors"
                            >
                                <ArrowDownTrayIcon class="h-4 w-4" />
                            </a>
                        </div>
                    </div>
                    <div v-else class="p-5 text-center text-sm text-gray-500">
                        {{ t('attachment.no_attachments') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete confirmation modal -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4">
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900">{{ t('app.confirm') }}</h3>
                        <p class="mt-2 text-sm text-gray-600">{{ t('patient.delete_confirm') }}</p>
                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                @click="showDeleteModal = false"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                            >
                                {{ t('app.cancel') }}
                            </button>
                            <button
                                type="button"
                                @click="deleteEncounter"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 transition-colors"
                            >
                                {{ t('app.delete') }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>
