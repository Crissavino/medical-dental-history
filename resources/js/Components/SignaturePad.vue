<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import SignaturePadLib from 'signature_pad';

const props = defineProps<{
    modelValue: string | null;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | null): void;
}>();

const container = ref<HTMLDivElement | null>(null);
const canvas = ref<HTMLCanvasElement | null>(null);
let pad: SignaturePadLib | null = null;
let resizeObserver: ResizeObserver | null = null;

function resize() {
    if (!canvas.value || !container.value) return;
    const w = container.value.clientWidth;
    if (w === 0) return;
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.value.width = w * ratio;
    canvas.value.height = 180 * ratio;
    canvas.value.getContext('2d')?.scale(ratio, ratio);
    pad?.clear();
}

function handleEnd() {
    if (!pad || pad.isEmpty()) {
        emit('update:modelValue', null);
        return;
    }
    emit('update:modelValue', canvas.value!.toDataURL('image/png'));
}

function clear() {
    pad?.clear();
    emit('update:modelValue', null);
}

defineExpose({ clear });

onMounted(() => {
    if (!canvas.value || !container.value) return;
    pad = new SignaturePadLib(canvas.value, {
        backgroundColor: 'rgba(255, 255, 255, 0)',
        penColor: '#1f2937',
    });
    pad.addEventListener('endStroke', handleEnd);
    resizeObserver = new ResizeObserver(() => resize());
    resizeObserver.observe(container.value);
});

onUnmounted(() => {
    resizeObserver?.disconnect();
    pad?.off();
});

watch(() => props.disabled, (d) => {
    if (d) pad?.off();
    else pad?.on();
});
</script>

<template>
    <div>
        <div ref="container" class="rounded-lg border border-gray-300 bg-white">
            <canvas ref="canvas" class="block w-full" style="height: 180px;" />
        </div>
        <div class="mt-2 flex justify-end">
            <button
                type="button"
                @click="clear"
                class="text-sm text-gray-500 underline hover:text-gray-700"
            >
                Clear
            </button>
        </div>
    </div>
</template>
