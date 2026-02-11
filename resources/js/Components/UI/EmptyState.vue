<script setup lang="ts">
import { computed, type Component } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { InboxIcon } from '@heroicons/vue/24/outline';

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        icon?: Component;
        actionLabel?: string;
        actionHref?: string;
    }>(),
    {
        icon: undefined,
        actionLabel: undefined,
        actionHref: undefined,
    },
);

const emit = defineEmits<{
    action: [];
}>();

const { t } = useI18n();

const resolvedIcon = computed(() => props.icon ?? InboxIcon);
</script>

<template>
    <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
        <component
            :is="resolvedIcon"
            class="mx-auto h-12 w-12 text-gray-400"
            aria-hidden="true"
        />
        <h3 class="mt-4 text-sm font-semibold text-gray-900">
            {{ title }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
            {{ description }}
        </p>
        <div v-if="actionLabel" class="mt-6">
            <Link
                v-if="actionHref"
                :href="actionHref"
                class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                {{ actionLabel }}
            </Link>
            <button
                v-else
                type="button"
                class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                @click="emit('action')"
            >
                {{ actionLabel }}
            </button>
        </div>
    </div>
</template>
