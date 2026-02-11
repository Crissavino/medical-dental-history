<script setup lang="ts">
defineProps<{
    dark?: boolean;
}>();
</script>

<template>
    <div class="flex items-center gap-2">
        <svg
            class="h-8 w-8 shrink-0"
            :class="dark ? 'text-white' : 'text-primary-600'"
            viewBox="0 0 24 24"
            fill="currentColor"
            xmlns="http://www.w3.org/2000/svg"
        >
            <path d="M12 2C9.5 2 7.5 3.5 6.5 5C5.5 3.5 3.5 2 1 2C1 7 3 9 5 11C5.5 12.5 5 15 5.5 18C6 21 8 22 9 22C10 22 10.5 21 11 19C11.5 17 12 17 12 17C12 17 12.5 17 13 19C13.5 21 14 22 15 22C16 22 18 21 18.5 18C19 15 18.5 12.5 19 11C21 9 23 7 23 2C20.5 2 18.5 3.5 17.5 5C16.5 3.5 14.5 2 12 2Z" />
        </svg>
        <span
            class="text-lg font-bold leading-tight"
            :class="dark ? 'text-white' : 'text-gray-900'"
        >
            Dental Wellness
        </span>
    </div>
</template>
