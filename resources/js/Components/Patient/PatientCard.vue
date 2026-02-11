<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    PhoneIcon,
    EnvelopeIcon,
    CalendarDaysIcon,
    IdentificationIcon,
} from '@heroicons/vue/20/solid';
import type { Patient } from '@/types';

const props = defineProps<{
    patient: Patient;
}>();

const { t } = useI18n();

const displayName = computed(() => {
    return props.patient.full_name ?? `${props.patient.first_name} ${props.patient.last_name}`;
});

const formattedDob = computed(() => {
    if (!props.patient.date_of_birth) return null;
    return new Date(props.patient.date_of_birth).toLocaleDateString();
});

const initials = computed(() => {
    const first = props.patient.first_name?.charAt(0) ?? '';
    const last = props.patient.last_name?.charAt(0) ?? '';
    return `${first}${last}`.toUpperCase();
});
</script>

<template>
    <Link
        :href="route('patients.show', patient.id)"
        class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition-all hover:border-primary-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
    >
        <div class="flex items-start gap-4">
            <!-- Avatar -->
            <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700"
            >
                {{ initials }}
            </div>

            <!-- Info -->
            <div class="min-w-0 flex-1">
                <h3 class="truncate text-sm font-semibold text-gray-900">
                    {{ displayName }}
                </h3>

                <div class="mt-1 flex items-center gap-1 text-xs text-gray-500">
                    <IdentificationIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                    <span>{{ patient.identifier }}</span>
                </div>

                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
                    <div
                        v-if="patient.phone"
                        class="flex items-center gap-1 text-xs text-gray-500"
                    >
                        <PhoneIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span class="truncate">{{ patient.phone }}</span>
                    </div>

                    <div
                        v-if="patient.email"
                        class="flex items-center gap-1 text-xs text-gray-500"
                    >
                        <EnvelopeIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span class="truncate">{{ patient.email }}</span>
                    </div>

                    <div
                        v-if="formattedDob"
                        class="flex items-center gap-1 text-xs text-gray-500"
                    >
                        <CalendarDaysIcon class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        <span>{{ formattedDob }}</span>
                    </div>
                </div>
            </div>
        </div>
    </Link>
</template>
