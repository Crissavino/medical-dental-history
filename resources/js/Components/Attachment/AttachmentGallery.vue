<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    DocumentTextIcon,
    PhotoIcon,
    FilmIcon,
    DocumentIcon,
    ArrowDownTrayIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import type { Attachment } from '@/types';

const props = withDefaults(
    defineProps<{
        attachments: Attachment[];
        deletable?: boolean;
    }>(),
    {
        deletable: false,
    },
);

const { t } = useI18n();

function getFileIcon(mimeType: string) {
    if (mimeType.startsWith('image/')) return PhotoIcon;
    if (mimeType.startsWith('video/')) return FilmIcon;
    if (mimeType === 'application/pdf' || mimeType.startsWith('text/')) return DocumentTextIcon;
    return DocumentIcon;
}

function getIconBgClass(mimeType: string): string {
    if (mimeType.startsWith('image/')) return 'bg-purple-100 text-purple-600';
    if (mimeType.startsWith('video/')) return 'bg-pink-100 text-pink-600';
    if (mimeType === 'application/pdf') return 'bg-red-100 text-red-600';
    if (mimeType.startsWith('text/')) return 'bg-blue-100 text-blue-600';
    return 'bg-gray-100 text-gray-600';
}

function formatFileSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function downloadAttachment(attachment: Attachment) {
    window.open(route('attachments.show', attachment.id), '_blank');
}

function deleteAttachment(attachment: Attachment) {
    if (!confirm(t('attachment.delete_confirm'))) return;

    router.delete(route('attachments.destroy', attachment.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div v-if="attachments.length === 0" class="py-8 text-center text-sm text-gray-500">
        {{ t('attachment.no_attachments') }}
    </div>

    <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div
            v-for="attachment in attachments"
            :key="attachment.id"
            class="group rounded-lg border border-gray-200 bg-white p-4 transition-shadow hover:shadow-md"
        >
            <div class="flex items-start gap-3">
                <!-- File icon -->
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                    :class="getIconBgClass(attachment.mime_type)"
                >
                    <component
                        :is="getFileIcon(attachment.mime_type)"
                        class="h-5 w-5"
                        aria-hidden="true"
                    />
                </div>

                <!-- File info -->
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-900" :title="attachment.file_name">
                        {{ attachment.file_name }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ formatFileSize(attachment.file_size) }}
                    </p>
                    <p v-if="attachment.uploader" class="mt-0.5 text-xs text-gray-400">
                        {{ attachment.uploader.name }}
                    </p>
                    <p class="mt-0.5 text-xs text-gray-400">
                        {{ formatDate(attachment.created_at) }}
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-3 flex items-center gap-2 border-t border-gray-100 pt-3">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    @click="downloadAttachment(attachment)"
                >
                    <ArrowDownTrayIcon class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ t('app.download') }}
                </button>

                <button
                    v-if="deletable"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500"
                    @click="deleteAttachment(attachment)"
                >
                    <TrashIcon class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ t('app.delete') }}
                </button>
            </div>
        </div>
    </div>
</template>
