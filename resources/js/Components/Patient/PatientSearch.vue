<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/20/solid';

const props = withDefaults(
    defineProps<{
        modelValue: string;
    }>(),
    {
        modelValue: '',
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
    search: [query: string];
}>();

const { t } = useI18n();

const query = ref(props.modelValue);
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal !== query.value) {
            query.value = newVal;
        }
    },
);

function onInput(event: Event) {
    const value = (event.target as HTMLInputElement).value;
    query.value = value;
    emit('update:modelValue', value);

    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
        emit('search', value);
    }, 300);
}

function clear() {
    query.value = '';
    emit('update:modelValue', '');
    emit('search', '');
}
</script>

<template>
    <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
        </div>
        <input
            type="text"
            :value="query"
            :placeholder="t('patient.search_placeholder')"
            class="block w-full rounded-md border-gray-300 py-2 pl-10 pr-10 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            @input="onInput"
        />
        <button
            v-if="query"
            type="button"
            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
            @click="clear"
        >
            <XMarkIcon class="h-5 w-5" aria-hidden="true" />
            <span class="sr-only">{{ t('app.close') }}</span>
        </button>
    </div>
</template>
