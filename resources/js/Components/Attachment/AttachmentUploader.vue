<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    CloudArrowUpIcon,
    DocumentIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps<{
    attachableType: string;
    attachableId: number;
}>();

const { t } = useI18n();

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const isDragging = ref(false);
const uploadProgress = ref(0);
const isUploading = ref(false);
const errorMessage = ref('');

const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

const formattedFileSize = computed(() => {
    if (!selectedFile.value) return '';
    const bytes = selectedFile.value.size;
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
});

function onDragOver(event: DragEvent) {
    event.preventDefault();
    isDragging.value = true;
}

function onDragLeave() {
    isDragging.value = false;
}

function onDrop(event: DragEvent) {
    event.preventDefault();
    isDragging.value = false;

    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        handleFile(files[0]);
    }
}

function onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        handleFile(input.files[0]);
    }
}

function handleFile(file: File) {
    errorMessage.value = '';

    if (file.size > MAX_FILE_SIZE) {
        errorMessage.value = t('attachment.max_size');
        return;
    }

    selectedFile.value = file;
}

function removeFile() {
    selectedFile.value = null;
    uploadProgress.value = 0;
    errorMessage.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function triggerFileInput() {
    fileInput.value?.click();
}

function upload() {
    if (!selectedFile.value) return;

    isUploading.value = true;
    errorMessage.value = '';

    const formData = new FormData();
    formData.append('file', selectedFile.value);
    formData.append('attachable_type', props.attachableType);
    formData.append('attachable_id', String(props.attachableId));

    router.post(route('attachments.store'), formData as any, {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (progress) => {
            if (progress && progress.percentage) {
                uploadProgress.value = progress.percentage;
            }
        },
        onSuccess: () => {
            selectedFile.value = null;
            uploadProgress.value = 0;
            isUploading.value = false;
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
        onError: (errors) => {
            isUploading.value = false;
            errorMessage.value = Object.values(errors).flat().join(', ');
        },
    });
}
</script>

<template>
    <div class="space-y-3">
        <!-- Drop zone -->
        <div
            class="relative flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-8 transition-colors"
            :class="
                isDragging
                    ? 'border-primary-400 bg-primary-50'
                    : 'border-gray-300 bg-gray-50 hover:border-gray-400 hover:bg-gray-100'
            "
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            @click="triggerFileInput"
        >
            <CloudArrowUpIcon class="mx-auto h-10 w-10 text-gray-400" aria-hidden="true" />
            <p class="mt-2 text-sm text-gray-600">
                {{ t('attachment.drag_drop') }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
                {{ t('attachment.max_size') }}
            </p>
            <input
                ref="fileInput"
                type="file"
                class="hidden"
                @change="onFileSelected"
            />
        </div>

        <!-- Error message -->
        <p v-if="errorMessage" class="text-sm text-red-600">
            {{ errorMessage }}
        </p>

        <!-- Selected file preview -->
        <div
            v-if="selectedFile"
            class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3"
        >
            <DocumentIcon class="h-8 w-8 shrink-0 text-gray-400" aria-hidden="true" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-gray-900">
                    {{ selectedFile.name }}
                </p>
                <p class="text-xs text-gray-500">
                    {{ formattedFileSize }}
                </p>

                <!-- Progress bar -->
                <div v-if="isUploading" class="mt-2">
                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                        <div
                            class="h-full rounded-full bg-primary-600 transition-all duration-300"
                            :style="{ width: `${uploadProgress}%` }"
                        />
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ uploadProgress }}%
                    </p>
                </div>
            </div>

            <button
                v-if="!isUploading"
                type="button"
                class="shrink-0 rounded p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-primary-500"
                @click.stop="removeFile"
            >
                <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                <span class="sr-only">{{ t('app.delete') }}</span>
            </button>
        </div>

        <!-- Upload button -->
        <div v-if="selectedFile && !isUploading" class="flex justify-end">
            <button
                type="button"
                class="inline-flex items-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                @click="upload"
            >
                {{ t('app.upload') }}
            </button>
        </div>
    </div>
</template>
