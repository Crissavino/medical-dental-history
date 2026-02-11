<script setup lang="ts">
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useI18n } from 'vue-i18n';

const props = withDefaults(
    defineProps<{
        show: boolean;
        maxWidth?: 'sm' | 'md' | 'lg' | 'xl';
    }>(),
    {
        show: false,
        maxWidth: 'md',
    },
);

const emit = defineEmits<{
    close: [];
}>();

const { t } = useI18n();

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
    }[props.maxWidth];
});

function onClose() {
    emit('close');
}

function onOverlayClick(event: MouseEvent) {
    if (event.target === event.currentTarget) {
        onClose();
    }
}

function onEscape(event: KeyboardEvent) {
    if (event.key === 'Escape' && props.show) {
        onClose();
    }
}

watch(
    () => props.show,
    (value) => {
        if (value) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },
);

onMounted(() => {
    document.addEventListener('keydown', onEscape);
    if (props.show) {
        document.body.style.overflow = 'hidden';
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', onEscape);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 bg-gray-500/75 transition-opacity"
                aria-hidden="true"
            />
        </Transition>

        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-50 overflow-y-auto"
                role="dialog"
                aria-modal="true"
            >
                <div
                    class="flex min-h-full items-center justify-center p-4 text-center sm:p-0"
                    @mousedown.self="onClose"
                >
                    <div
                        class="relative w-full transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8"
                        :class="maxWidthClass"
                    >
                        <!-- Close button -->
                        <button
                            type="button"
                            class="absolute right-3 top-3 rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                            @click="onClose"
                        >
                            <span class="sr-only">{{ t('app.close') }}</span>
                            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                        </button>

                        <!-- Body -->
                        <div class="px-4 pb-4 pt-5 sm:p-6">
                            <slot />
                        </div>

                        <!-- Footer -->
                        <div
                            v-if="$slots.footer"
                            class="border-t border-gray-200 bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6"
                        >
                            <slot name="footer" />
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
