<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        variant: 'success' | 'warning' | 'danger' | 'info' | 'neutral';
        label: string;
    }>(),
    {
        variant: 'neutral',
    },
);

const variantClasses: Record<typeof props.variant, string> = {
    success: 'bg-green-50 text-green-700 ring-green-600/20',
    warning: 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
    danger: 'bg-red-50 text-red-700 ring-red-600/20',
    info: 'bg-blue-50 text-blue-700 ring-blue-700/20',
    neutral: 'bg-gray-50 text-gray-600 ring-gray-500/20',
};
</script>

<template>
    <span
        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
        :class="variantClasses[variant]"
    >
        {{ label }}
    </span>
</template>
