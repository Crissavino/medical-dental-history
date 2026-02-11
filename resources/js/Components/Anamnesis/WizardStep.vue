<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';

const props = withDefaults(
    defineProps<{
        title: string;
        stepNumber: number;
        totalSteps: number;
        canProceed?: boolean;
    }>(),
    {
        canProceed: true,
    },
);

const emit = defineEmits<{
    next: [];
    previous: [];
}>();

const { t } = useI18n();

const isFirstStep = computed(() => props.stepNumber === 1);
const isLastStep = computed(() => props.stepNumber === props.totalSteps);

const progressPercentage = computed(() => {
    return Math.round((props.stepNumber / props.totalSteps) * 100);
});
</script>

<template>
    <div class="flex flex-col">
        <!-- Step header -->
        <div class="border-b border-gray-200 pb-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-primary-600">
                        {{ t('app.next') }} {{ stepNumber }} / {{ totalSteps }}
                    </p>
                    <h2 class="mt-1 text-lg font-semibold text-gray-900">
                        {{ title }}
                    </h2>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                <div
                    class="h-full rounded-full bg-primary-600 transition-all duration-500"
                    :style="{ width: `${progressPercentage}%` }"
                />
            </div>
        </div>

        <!-- Step content -->
        <div class="flex-1 py-6">
            <slot />
        </div>

        <!-- Navigation buttons -->
        <div class="flex items-center justify-between border-t border-gray-200 pt-4">
            <button
                v-if="!isFirstStep"
                type="button"
                class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                @click="emit('previous')"
            >
                <ChevronLeftIcon class="h-4 w-4" aria-hidden="true" />
                {{ t('app.previous') }}
            </button>
            <div v-else />

            <button
                v-if="!isLastStep"
                type="button"
                :disabled="!canProceed"
                class="inline-flex items-center gap-1.5 rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50"
                @click="emit('next')"
            >
                {{ t('app.next') }}
                <ChevronRightIcon class="h-4 w-4" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>
