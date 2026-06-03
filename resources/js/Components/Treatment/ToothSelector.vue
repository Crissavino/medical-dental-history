<script setup lang="ts">
import { computed } from 'vue';
import { useToothNotation } from '@/Composables/useToothNotation';

const props = withDefaults(
    defineProps<{
        modelValue: string | null;
        surface?: string | null;
        disabled?: boolean;
    }>(),
    { modelValue: null, surface: null, disabled: false },
);

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
    'update:surface': [value: string | null];
    'surfaceSelected': [];
}>();

const { quadrants, getToothName } = useToothNotation();

// Standard FDI odontogram: Q4 (48→41) left, Q3 (31→38) right
const lowerQ4 = computed(() => [...quadrants.lower_right].reverse());
const lowerQ3 = computed(() => [...quadrants.lower_left].reverse());

// Anterior teeth use "incisal" instead of "occlusal"
const ANTERIOR = new Set(['11','12','13','21','22','23','31','32','33','41','42','43']);

function centerSurface(tooth: string): string {
    return ANTERIOR.has(tooth) ? 'incisal' : 'occlusal';
}

function centerLabel(tooth: string): string {
    return ANTERIOR.has(tooth) ? 'I' : 'O';
}

function selectTooth(tooth: string) {
    if (props.disabled) return;
    if (props.modelValue === tooth) {
        // deselect
        emit('update:modelValue', null);
        emit('update:surface', null);
    } else {
        emit('update:modelValue', tooth);
        emit('update:surface', null);
    }
}

function selectSurface(surface: string) {
    if (props.disabled) return;
    const next = props.surface === surface ? null : surface;
    emit('update:surface', next);
    if (next !== null) emit('surfaceSelected');
}

// SVG zones for viewBox "0 0 40 40", center (20,20), r=18
// Diagonal corners at 45°: 20 ± 18*sin(45°) ≈ 20 ± 12.73 → 7.27 and 32.73
const ZONE_PATHS = {
    buccal:  'M 20 20 L 7.3 7.3 A 18 18 0 0 1 32.7 7.3 Z',
    distal:  'M 20 20 L 32.7 7.3 A 18 18 0 0 1 32.7 32.7 Z',
    lingual: 'M 20 20 L 32.7 32.7 A 18 18 0 0 1 7.3 32.7 Z',
    mesial:  'M 20 20 L 7.3 32.7 A 18 18 0 0 1 7.3 7.3 Z',
} as const;

function zoneFill(zoneSurface: string): string {
    return props.surface === zoneSurface ? '#0f766e' : '#ccfbf1';
}

function zoneTextFill(zoneSurface: string): string {
    return props.surface === zoneSurface ? '#ffffff' : '#374151';
}

function centerFill(tooth: string): string {
    return props.surface === centerSurface(tooth) ? '#0f766e' : '#f0fdf4';
}

function centerTextFill(tooth: string): string {
    return props.surface === centerSurface(tooth) ? '#ffffff' : '#374151';
}
</script>

<template>
    <div :class="{ 'pointer-events-none opacity-50': disabled }" class="select-none">

        <!-- Upper arch -->
        <div class="flex items-end justify-center">
            <!-- Q1 -->
            <div class="flex flex-col items-center">
                <span class="mb-1 text-[10px] font-bold text-gray-400">1</span>
                <div class="flex">
                    <template v-for="tooth in quadrants.upper_right" :key="tooth">
                        <!-- Selected: show 5-zone surface picker -->
                        <div v-if="modelValue === tooth" class="flex flex-col items-center px-0.5 py-0.5">
                            <svg width="48" height="48" viewBox="0 0 40 40" class="cursor-pointer overflow-visible">
                                <path :d="ZONE_PATHS.buccal" :fill="zoneFill('buccal')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('buccal')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.distal" :fill="zoneFill('distal')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('distal')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.lingual" :fill="zoneFill('lingual')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('lingual')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.mesial" :fill="zoneFill('mesial')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('mesial')" class="hover:opacity-80" />
                                <circle cx="20" cy="20" r="7.5" :fill="centerFill(tooth)" stroke="white" stroke-width="0.5" @click.stop="selectSurface(centerSurface(tooth))" class="hover:opacity-80 cursor-pointer" />
                                <circle cx="20" cy="20" r="18" fill="none" stroke="#0f766e" stroke-width="1.5" style="pointer-events:none" />
                                <!-- Zone labels -->
                                <text x="20" y="10" text-anchor="middle" font-size="5" :fill="zoneTextFill('buccal')" style="pointer-events:none" font-weight="600">B</text>
                                <text x="31" y="21.5" text-anchor="middle" font-size="5" :fill="zoneTextFill('distal')" style="pointer-events:none" font-weight="600">D</text>
                                <text x="20" y="34" text-anchor="middle" font-size="5" :fill="zoneTextFill('lingual')" style="pointer-events:none" font-weight="600">L</text>
                                <text x="9" y="21.5" text-anchor="middle" font-size="5" :fill="zoneTextFill('mesial')" style="pointer-events:none" font-weight="600">M</text>
                                <text x="20" y="22" text-anchor="middle" font-size="5.5" :fill="centerTextFill(tooth)" style="pointer-events:none" font-weight="600">{{ centerLabel(tooth) }}</text>
                            </svg>
                            <span class="mt-0.5 text-[9px] font-bold leading-none text-teal-700">{{ tooth }}</span>
                        </div>
                        <!-- Unselected: concentric circles -->
                        <button v-else type="button" :title="getToothName(tooth)" @click="selectTooth(tooth)" class="flex flex-col items-center rounded-sm px-0.5 py-0.5 transition-colors hover:bg-teal-50 focus:outline-none">
                            <svg width="48" height="48" viewBox="0 0 40 40">
                                <circle cx="20" cy="20" r="17" fill="white" stroke="#9ca3af" stroke-width="1.5" />
                                <circle cx="20" cy="20" r="10" fill="white" stroke="#9ca3af" stroke-width="1.5" />
                                <circle cx="20" cy="20" r="4" fill="#d1d5db" />
                            </svg>
                            <span class="mt-0.5 text-[9px] leading-none text-gray-400">{{ tooth }}</span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="mx-1 h-10 w-px self-center bg-gray-500" />

            <!-- Q2 -->
            <div class="flex flex-col items-center">
                <span class="mb-1 text-[10px] font-bold text-gray-400">2</span>
                <div class="flex">
                    <template v-for="tooth in quadrants.upper_left" :key="tooth">
                        <div v-if="modelValue === tooth" class="flex flex-col items-center px-0.5 py-0.5">
                            <svg width="48" height="48" viewBox="0 0 40 40" class="cursor-pointer overflow-visible">
                                <path :d="ZONE_PATHS.buccal" :fill="zoneFill('buccal')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('buccal')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.distal" :fill="zoneFill('distal')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('distal')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.lingual" :fill="zoneFill('lingual')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('lingual')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.mesial" :fill="zoneFill('mesial')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('mesial')" class="hover:opacity-80" />
                                <circle cx="20" cy="20" r="7.5" :fill="centerFill(tooth)" stroke="white" stroke-width="0.5" @click.stop="selectSurface(centerSurface(tooth))" class="hover:opacity-80 cursor-pointer" />
                                <circle cx="20" cy="20" r="18" fill="none" stroke="#0f766e" stroke-width="1.5" style="pointer-events:none" />
                                <text x="20" y="10" text-anchor="middle" font-size="5" :fill="zoneTextFill('buccal')" style="pointer-events:none" font-weight="600">B</text>
                                <text x="31" y="21.5" text-anchor="middle" font-size="5" :fill="zoneTextFill('distal')" style="pointer-events:none" font-weight="600">D</text>
                                <text x="20" y="34" text-anchor="middle" font-size="5" :fill="zoneTextFill('lingual')" style="pointer-events:none" font-weight="600">L</text>
                                <text x="9" y="21.5" text-anchor="middle" font-size="5" :fill="zoneTextFill('mesial')" style="pointer-events:none" font-weight="600">M</text>
                                <text x="20" y="22" text-anchor="middle" font-size="5.5" :fill="centerTextFill(tooth)" style="pointer-events:none" font-weight="600">{{ centerLabel(tooth) }}</text>
                            </svg>
                            <span class="mt-0.5 text-[9px] font-bold leading-none text-teal-700">{{ tooth }}</span>
                        </div>
                        <button v-else type="button" :title="getToothName(tooth)" @click="selectTooth(tooth)" class="flex flex-col items-center rounded-sm px-0.5 py-0.5 transition-colors hover:bg-teal-50 focus:outline-none">
                            <svg width="48" height="48" viewBox="0 0 40 40">
                                <circle cx="20" cy="20" r="17" fill="white" stroke="#9ca3af" stroke-width="1.5" />
                                <circle cx="20" cy="20" r="10" fill="white" stroke="#9ca3af" stroke-width="1.5" />
                                <circle cx="20" cy="20" r="4" fill="#d1d5db" />
                            </svg>
                            <span class="mt-0.5 text-[9px] leading-none text-gray-400">{{ tooth }}</span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Horizontal jaw divider -->
        <div class="my-2 h-0.5 bg-gray-300" />

        <!-- Lower arch -->
        <div class="flex items-start justify-center">
            <!-- Q4 -->
            <div class="flex flex-col items-center">
                <div class="flex">
                    <template v-for="tooth in lowerQ4" :key="tooth">
                        <div v-if="modelValue === tooth" class="flex flex-col items-center px-0.5 py-0.5">
                            <svg width="48" height="48" viewBox="0 0 40 40" class="cursor-pointer overflow-visible">
                                <path :d="ZONE_PATHS.buccal" :fill="zoneFill('buccal')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('buccal')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.distal" :fill="zoneFill('distal')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('distal')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.lingual" :fill="zoneFill('lingual')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('lingual')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.mesial" :fill="zoneFill('mesial')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('mesial')" class="hover:opacity-80" />
                                <circle cx="20" cy="20" r="7.5" :fill="centerFill(tooth)" stroke="white" stroke-width="0.5" @click.stop="selectSurface(centerSurface(tooth))" class="hover:opacity-80 cursor-pointer" />
                                <circle cx="20" cy="20" r="18" fill="none" stroke="#0f766e" stroke-width="1.5" style="pointer-events:none" />
                                <text x="20" y="10" text-anchor="middle" font-size="5" :fill="zoneTextFill('buccal')" style="pointer-events:none" font-weight="600">B</text>
                                <text x="31" y="21.5" text-anchor="middle" font-size="5" :fill="zoneTextFill('distal')" style="pointer-events:none" font-weight="600">D</text>
                                <text x="20" y="34" text-anchor="middle" font-size="5" :fill="zoneTextFill('lingual')" style="pointer-events:none" font-weight="600">L</text>
                                <text x="9" y="21.5" text-anchor="middle" font-size="5" :fill="zoneTextFill('mesial')" style="pointer-events:none" font-weight="600">M</text>
                                <text x="20" y="22" text-anchor="middle" font-size="5.5" :fill="centerTextFill(tooth)" style="pointer-events:none" font-weight="600">{{ centerLabel(tooth) }}</text>
                            </svg>
                            <span class="mt-0.5 text-[9px] font-bold leading-none text-teal-700">{{ tooth }}</span>
                        </div>
                        <button v-else type="button" :title="getToothName(tooth)" @click="selectTooth(tooth)" class="flex flex-col items-center rounded-sm px-0.5 py-0.5 transition-colors hover:bg-teal-50 focus:outline-none">
                            <svg width="48" height="48" viewBox="0 0 40 40">
                                <circle cx="20" cy="20" r="17" fill="white" stroke="#9ca3af" stroke-width="1.5" />
                                <circle cx="20" cy="20" r="10" fill="white" stroke="#9ca3af" stroke-width="1.5" />
                                <circle cx="20" cy="20" r="4" fill="#d1d5db" />
                            </svg>
                            <span class="mt-0.5 text-[9px] leading-none text-gray-400">{{ tooth }}</span>
                        </button>
                    </template>
                </div>
                <span class="mt-1 text-[10px] font-bold text-gray-400">4</span>
            </div>

            <div class="mx-1 h-10 w-px self-center bg-gray-500" />

            <!-- Q3 -->
            <div class="flex flex-col items-center">
                <div class="flex">
                    <template v-for="tooth in lowerQ3" :key="tooth">
                        <div v-if="modelValue === tooth" class="flex flex-col items-center px-0.5 py-0.5">
                            <svg width="48" height="48" viewBox="0 0 40 40" class="cursor-pointer overflow-visible">
                                <path :d="ZONE_PATHS.buccal" :fill="zoneFill('buccal')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('buccal')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.distal" :fill="zoneFill('distal')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('distal')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.lingual" :fill="zoneFill('lingual')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('lingual')" class="hover:opacity-80" />
                                <path :d="ZONE_PATHS.mesial" :fill="zoneFill('mesial')" stroke="white" stroke-width="0.5" @click.stop="selectSurface('mesial')" class="hover:opacity-80" />
                                <circle cx="20" cy="20" r="7.5" :fill="centerFill(tooth)" stroke="white" stroke-width="0.5" @click.stop="selectSurface(centerSurface(tooth))" class="hover:opacity-80 cursor-pointer" />
                                <circle cx="20" cy="20" r="18" fill="none" stroke="#0f766e" stroke-width="1.5" style="pointer-events:none" />
                                <text x="20" y="10" text-anchor="middle" font-size="5" :fill="zoneTextFill('buccal')" style="pointer-events:none" font-weight="600">B</text>
                                <text x="31" y="21.5" text-anchor="middle" font-size="5" :fill="zoneTextFill('distal')" style="pointer-events:none" font-weight="600">D</text>
                                <text x="20" y="34" text-anchor="middle" font-size="5" :fill="zoneTextFill('lingual')" style="pointer-events:none" font-weight="600">L</text>
                                <text x="9" y="21.5" text-anchor="middle" font-size="5" :fill="zoneTextFill('mesial')" style="pointer-events:none" font-weight="600">M</text>
                                <text x="20" y="22" text-anchor="middle" font-size="5.5" :fill="centerTextFill(tooth)" style="pointer-events:none" font-weight="600">{{ centerLabel(tooth) }}</text>
                            </svg>
                            <span class="mt-0.5 text-[9px] font-bold leading-none text-teal-700">{{ tooth }}</span>
                        </div>
                        <button v-else type="button" :title="getToothName(tooth)" @click="selectTooth(tooth)" class="flex flex-col items-center rounded-sm px-0.5 py-0.5 transition-colors hover:bg-teal-50 focus:outline-none">
                            <svg width="48" height="48" viewBox="0 0 40 40">
                                <circle cx="20" cy="20" r="17" fill="white" stroke="#9ca3af" stroke-width="1.5" />
                                <circle cx="20" cy="20" r="10" fill="white" stroke="#9ca3af" stroke-width="1.5" />
                                <circle cx="20" cy="20" r="4" fill="#d1d5db" />
                            </svg>
                            <span class="mt-0.5 text-[9px] leading-none text-gray-400">{{ tooth }}</span>
                        </button>
                    </template>
                </div>
                <span class="mt-1 text-[10px] font-bold text-gray-400">3</span>
            </div>
        </div>

        <!-- Selected info -->
        <p v-if="modelValue" class="mt-3 text-center text-xs text-gray-500">
            {{ getToothName(modelValue) }}
            <span v-if="surface" class="ml-1 font-medium text-teal-700">· {{ surface }}</span>
        </p>
    </div>
</template>
