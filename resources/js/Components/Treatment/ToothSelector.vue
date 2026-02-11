<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useToothNotation } from '@/Composables/useToothNotation';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        disabled?: boolean;
    }>(),
    {
        modelValue: null,
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const { t } = useI18n();
const { upperTeeth, lowerTeeth, quadrants, getToothName } = useToothNotation();

function selectTooth(tooth: string) {
    if (props.disabled) return;

    if (props.modelValue === tooth) {
        emit('update:modelValue', null);
    } else {
        emit('update:modelValue', tooth);
    }
}

function isSelected(tooth: string): boolean {
    return props.modelValue === tooth;
}
</script>

<template>
    <div
        class="rounded-lg border border-gray-200 bg-white p-4"
        :class="{ 'opacity-50': disabled }"
    >
        <p class="mb-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
            {{ t('treatment.select_tooth') }}
        </p>

        <!-- Upper arch -->
        <div class="mb-1 flex justify-center gap-0.5">
            <!-- Upper right: 18-11 -->
            <div class="flex gap-0.5">
                <button
                    v-for="tooth in quadrants.upper_right"
                    :key="tooth"
                    type="button"
                    :disabled="disabled"
                    :title="getToothName(tooth)"
                    class="flex h-9 w-9 items-center justify-center rounded text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                    :class="
                        isSelected(tooth)
                            ? 'bg-primary-600 text-white shadow-sm'
                            : 'bg-gray-100 text-gray-700 hover:bg-primary-100 hover:text-primary-700'
                    "
                    @click="selectTooth(tooth)"
                >
                    {{ tooth }}
                </button>
            </div>

            <!-- Divider -->
            <div class="flex w-4 items-center justify-center">
                <div class="h-6 w-px bg-gray-300" />
            </div>

            <!-- Upper left: 21-28 -->
            <div class="flex gap-0.5">
                <button
                    v-for="tooth in quadrants.upper_left"
                    :key="tooth"
                    type="button"
                    :disabled="disabled"
                    :title="getToothName(tooth)"
                    class="flex h-9 w-9 items-center justify-center rounded text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                    :class="
                        isSelected(tooth)
                            ? 'bg-primary-600 text-white shadow-sm'
                            : 'bg-gray-100 text-gray-700 hover:bg-primary-100 hover:text-primary-700'
                    "
                    @click="selectTooth(tooth)"
                >
                    {{ tooth }}
                </button>
            </div>
        </div>

        <!-- Horizontal divider between arches -->
        <div class="my-2 flex justify-center">
            <div class="h-px w-full max-w-md bg-gray-300" />
        </div>

        <!-- Lower arch -->
        <div class="flex justify-center gap-0.5">
            <!-- Lower right: 48-41 -->
            <div class="flex gap-0.5">
                <button
                    v-for="tooth in quadrants.lower_right.slice().reverse()"
                    :key="tooth"
                    type="button"
                    :disabled="disabled"
                    :title="getToothName(tooth)"
                    class="flex h-9 w-9 items-center justify-center rounded text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                    :class="
                        isSelected(tooth)
                            ? 'bg-primary-600 text-white shadow-sm'
                            : 'bg-gray-100 text-gray-700 hover:bg-primary-100 hover:text-primary-700'
                    "
                    @click="selectTooth(tooth)"
                >
                    {{ tooth }}
                </button>
            </div>

            <!-- Divider -->
            <div class="flex w-4 items-center justify-center">
                <div class="h-6 w-px bg-gray-300" />
            </div>

            <!-- Lower left: 31-38 -->
            <div class="flex gap-0.5">
                <button
                    v-for="tooth in quadrants.lower_left.slice().reverse()"
                    :key="tooth"
                    type="button"
                    :disabled="disabled"
                    :title="getToothName(tooth)"
                    class="flex h-9 w-9 items-center justify-center rounded text-xs font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-1"
                    :class="
                        isSelected(tooth)
                            ? 'bg-primary-600 text-white shadow-sm'
                            : 'bg-gray-100 text-gray-700 hover:bg-primary-100 hover:text-primary-700'
                    "
                    @click="selectTooth(tooth)"
                >
                    {{ tooth }}
                </button>
            </div>
        </div>

        <!-- Selected tooth label -->
        <p
            v-if="modelValue"
            class="mt-3 text-center text-xs text-gray-600"
        >
            {{ getToothName(modelValue) }}
        </p>
    </div>
</template>
