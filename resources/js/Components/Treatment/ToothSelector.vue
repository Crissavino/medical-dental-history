<script setup lang="ts">
import { computed } from 'vue';
import { useToothNotation } from '@/Composables/useToothNotation';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        disabled?: boolean;
    }>(),
    { modelValue: null, disabled: false },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const { quadrants, getToothName } = useToothNotation();

// Standard FDI odontogram order for lower arch:
// Left side = Q4 patient-right (48→41), Right side = Q3 patient-left (31→38)
const lowerQ4 = computed(() => [...quadrants.lower_right].reverse()); // 48,47,...,41
const lowerQ3 = computed(() => [...quadrants.lower_left].reverse());  // 31,32,...,38

function select(tooth: string) {
    if (props.disabled) return;
    emit('update:modelValue', props.modelValue === tooth ? null : tooth);
}
</script>

<template>
    <div :class="{ 'pointer-events-none opacity-50': disabled }" class="select-none">

        <!-- Upper arch -->
        <div class="flex items-end justify-center">
            <!-- Q1 (upper right patient, left on chart) -->
            <div class="flex flex-col items-center">
                <span class="mb-1 text-[10px] font-bold text-gray-400">1</span>
                <div class="flex">
                    <button
                        v-for="tooth in quadrants.upper_right"
                        :key="tooth"
                        type="button"
                        :title="getToothName(tooth)"
                        @click="select(tooth)"
                        class="flex flex-col items-center rounded-sm px-0.5 py-0.5 transition-colors hover:bg-teal-50 focus:outline-none"
                    >
                        <svg width="30" height="30" viewBox="0 0 30 30">
                            <circle cx="15" cy="15" r="13"
                                :fill="modelValue === tooth ? '#0f766e' : '#ffffff'"
                                :stroke="modelValue === tooth ? '#0f766e' : '#9ca3af'"
                                stroke-width="1.5" />
                            <circle cx="15" cy="15" r="8"
                                :fill="modelValue === tooth ? 'rgba(255,255,255,0.12)' : '#ffffff'"
                                :stroke="modelValue === tooth ? 'rgba(255,255,255,0.55)' : '#9ca3af'"
                                stroke-width="1.5" />
                            <circle cx="15" cy="15" r="3.5"
                                :fill="modelValue === tooth ? '#ffffff' : '#d1d5db'" />
                        </svg>
                        <span
                            class="mt-0.5 text-[9px] leading-none"
                            :class="modelValue === tooth ? 'font-bold text-teal-700' : 'text-gray-400'"
                        >{{ tooth }}</span>
                    </button>
                </div>
            </div>

            <!-- Vertical midline -->
            <div class="mx-1 h-9 w-px self-center bg-gray-500" />

            <!-- Q2 (upper left patient, right on chart) -->
            <div class="flex flex-col items-center">
                <span class="mb-1 text-[10px] font-bold text-gray-400">2</span>
                <div class="flex">
                    <button
                        v-for="tooth in quadrants.upper_left"
                        :key="tooth"
                        type="button"
                        :title="getToothName(tooth)"
                        @click="select(tooth)"
                        class="flex flex-col items-center rounded-sm px-0.5 py-0.5 transition-colors hover:bg-teal-50 focus:outline-none"
                    >
                        <svg width="30" height="30" viewBox="0 0 30 30">
                            <circle cx="15" cy="15" r="13"
                                :fill="modelValue === tooth ? '#0f766e' : '#ffffff'"
                                :stroke="modelValue === tooth ? '#0f766e' : '#9ca3af'"
                                stroke-width="1.5" />
                            <circle cx="15" cy="15" r="8"
                                :fill="modelValue === tooth ? 'rgba(255,255,255,0.12)' : '#ffffff'"
                                :stroke="modelValue === tooth ? 'rgba(255,255,255,0.55)' : '#9ca3af'"
                                stroke-width="1.5" />
                            <circle cx="15" cy="15" r="3.5"
                                :fill="modelValue === tooth ? '#ffffff' : '#d1d5db'" />
                        </svg>
                        <span
                            class="mt-0.5 text-[9px] leading-none"
                            :class="modelValue === tooth ? 'font-bold text-teal-700' : 'text-gray-400'"
                        >{{ tooth }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Horizontal jaw divider -->
        <div class="my-2 h-0.5 bg-gray-300" />

        <!-- Lower arch -->
        <div class="flex items-start justify-center">
            <!-- Q4 (lower right patient, left on chart) -->
            <div class="flex flex-col items-center">
                <div class="flex">
                    <button
                        v-for="tooth in lowerQ4"
                        :key="tooth"
                        type="button"
                        :title="getToothName(tooth)"
                        @click="select(tooth)"
                        class="flex flex-col items-center rounded-sm px-0.5 py-0.5 transition-colors hover:bg-teal-50 focus:outline-none"
                    >
                        <svg width="30" height="30" viewBox="0 0 30 30">
                            <circle cx="15" cy="15" r="13"
                                :fill="modelValue === tooth ? '#0f766e' : '#ffffff'"
                                :stroke="modelValue === tooth ? '#0f766e' : '#9ca3af'"
                                stroke-width="1.5" />
                            <circle cx="15" cy="15" r="8"
                                :fill="modelValue === tooth ? 'rgba(255,255,255,0.12)' : '#ffffff'"
                                :stroke="modelValue === tooth ? 'rgba(255,255,255,0.55)' : '#9ca3af'"
                                stroke-width="1.5" />
                            <circle cx="15" cy="15" r="3.5"
                                :fill="modelValue === tooth ? '#ffffff' : '#d1d5db'" />
                        </svg>
                        <span
                            class="mt-0.5 text-[9px] leading-none"
                            :class="modelValue === tooth ? 'font-bold text-teal-700' : 'text-gray-400'"
                        >{{ tooth }}</span>
                    </button>
                </div>
                <span class="mt-1 text-[10px] font-bold text-gray-400">4</span>
            </div>

            <!-- Vertical midline -->
            <div class="mx-1 h-9 w-px self-center bg-gray-500" />

            <!-- Q3 (lower left patient, right on chart) -->
            <div class="flex flex-col items-center">
                <div class="flex">
                    <button
                        v-for="tooth in lowerQ3"
                        :key="tooth"
                        type="button"
                        :title="getToothName(tooth)"
                        @click="select(tooth)"
                        class="flex flex-col items-center rounded-sm px-0.5 py-0.5 transition-colors hover:bg-teal-50 focus:outline-none"
                    >
                        <svg width="30" height="30" viewBox="0 0 30 30">
                            <circle cx="15" cy="15" r="13"
                                :fill="modelValue === tooth ? '#0f766e' : '#ffffff'"
                                :stroke="modelValue === tooth ? '#0f766e' : '#9ca3af'"
                                stroke-width="1.5" />
                            <circle cx="15" cy="15" r="8"
                                :fill="modelValue === tooth ? 'rgba(255,255,255,0.12)' : '#ffffff'"
                                :stroke="modelValue === tooth ? 'rgba(255,255,255,0.55)' : '#9ca3af'"
                                stroke-width="1.5" />
                            <circle cx="15" cy="15" r="3.5"
                                :fill="modelValue === tooth ? '#ffffff' : '#d1d5db'" />
                        </svg>
                        <span
                            class="mt-0.5 text-[9px] leading-none"
                            :class="modelValue === tooth ? 'font-bold text-teal-700' : 'text-gray-400'"
                        >{{ tooth }}</span>
                    </button>
                </div>
                <span class="mt-1 text-[10px] font-bold text-gray-400">3</span>
            </div>
        </div>

        <!-- Selected tooth name -->
        <p v-if="modelValue" class="mt-3 text-center text-xs text-gray-500">
            {{ getToothName(modelValue) }}
        </p>
    </div>
</template>
