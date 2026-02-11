<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import type { AuditLog, PaginatedResponse } from '@/types';
import {
    ChevronDownIcon,
    FunnelIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const { t } = useI18n();

const props = defineProps<{
    logs: PaginatedResponse<AuditLog>;
    filters: {
        entity_type?: string;
        entity_id?: string;
        action?: string;
    };
}>();

const filterEntityType = ref(props.filters.entity_type || '');
const filterAction = ref(props.filters.action || '');
const showFilters = ref(!!(props.filters.entity_type || props.filters.action));

const actionColors: Record<string, string> = {
    created: 'bg-green-100 text-green-800',
    updated: 'bg-blue-100 text-blue-800',
    deleted: 'bg-red-100 text-red-800',
};

const entityTypes = ['Patient', 'Encounter', 'Treatment', 'Attachment', 'AnamnesisVersion'];
const actions = ['created', 'updated', 'deleted'] as const;

/** Tracks which audit-log rows are expanded (by log id). */
const openLogs = reactive(new Set<number>());

function toggleLog(logId: number) {
    if (openLogs.has(logId)) {
        openLogs.delete(logId);
    } else {
        openLogs.add(logId);
    }
}

function isLogOpen(logId: number): boolean {
    return openLogs.has(logId);
}

function hasMetadata(log: AuditLog): boolean {
    return !!(log.metadata_json && Object.keys(log.metadata_json).length > 0);
}

function applyFilters() {
    router.get(
        route('audit-logs.index'),
        {
            entity_type: filterEntityType.value || undefined,
            action: filterAction.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function clearFilters() {
    filterEntityType.value = '';
    filterAction.value = '';
    applyFilters();
}

function formatDateTime(dateStr: string): string {
    return new Date(dateStr).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function formatJsonValue(value: unknown): string {
    if (value === null || value === undefined) return 'null';
    if (typeof value === 'object') return JSON.stringify(value, null, 2);
    return String(value);
}
</script>

<template>
    <Head :title="t('audit.title')" />

    <AppLayout>
        <template #header>
            <h1 class="text-lg font-semibold text-gray-900">{{ t('audit.title') }}</h1>
        </template>

        <!-- Filters -->
        <div class="mb-6">
            <button
                type="button"
                @click="showFilters = !showFilters"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
            >
                <FunnelIcon class="h-4 w-4" />
                {{ showFilters ? t('app.close') : 'Filters' }}
            </button>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-if="showFilters" class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-end gap-4">
                        <div class="w-full sm:w-auto">
                            <label class="block text-sm font-medium text-gray-700">{{ t('audit.entity_type') }}</label>
                            <select
                                v-model="filterEntityType"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:w-48 sm:text-sm"
                            >
                                <option value="">{{ t('app.view') }}</option>
                                <option v-for="type in entityTypes" :key="type" :value="type">{{ type }}</option>
                            </select>
                        </div>
                        <div class="w-full sm:w-auto">
                            <label class="block text-sm font-medium text-gray-700">{{ t('audit.action') }}</label>
                            <select
                                v-model="filterAction"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:w-48 sm:text-sm"
                            >
                                <option value="">{{ t('app.view') }}</option>
                                <option v-for="action in actions" :key="action" :value="action">
                                    {{ t(`audit.action_${action}`) }}
                                </option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                @click="applyFilters"
                                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors"
                            >
                                {{ t('app.search') }}
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                            >
                                <XMarkIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <!-- Desktop table -->
            <div class="hidden md:block">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ t('audit.timestamp') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ t('audit.user') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ t('audit.action') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ t('audit.entity_type') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ t('audit.entity_id') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                {{ t('audit.ip_address') }}
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">{{ t('audit.details') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        <template v-for="log in logs.data" :key="log.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ formatDateTime(log.created_at) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ log.user?.name || '---' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        :class="[actionColors[log.action], 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium']"
                                    >
                                        {{ t(`audit.action_${log.action}`) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ log.entity_type }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ log.entity_id }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ log.ip_address || '---' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <button
                                        v-if="hasMetadata(log)"
                                        type="button"
                                        @click="toggleLog(log.id)"
                                        class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-primary-600 transition-colors"
                                    >
                                        <ChevronDownIcon
                                            :class="[isLogOpen(log.id) ? 'rotate-180' : '', 'h-4 w-4 transition-transform duration-200']"
                                        />
                                    </button>
                                </td>
                            </tr>
                            <tr v-show="hasMetadata(log) && isLogOpen(log.id)" class="disclosure-panel">
                                <td colspan="7" class="bg-gray-50 px-6 py-4">
                                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                            {{ t('audit.details') }}
                                        </h4>
                                        <dl class="space-y-2">
                                            <div
                                                v-for="(value, key) in log.metadata_json"
                                                :key="String(key)"
                                                class="flex gap-4"
                                            >
                                                <dt class="w-40 shrink-0 text-sm font-medium text-gray-500">{{ key }}</dt>
                                                <dd class="text-sm text-gray-900">
                                                    <pre v-if="typeof value === 'object'" class="whitespace-pre-wrap text-xs">{{ formatJsonValue(value) }}</pre>
                                                    <span v-else>{{ formatJsonValue(value) }}</span>
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr v-if="logs.data.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                {{ t('app.no_results') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile card view -->
            <div class="space-y-0 divide-y divide-gray-100 md:hidden">
                <div
                    v-for="log in logs.data"
                    :key="log.id"
                >
                    <div class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        :class="[actionColors[log.action], 'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium']"
                                    >
                                        {{ t(`audit.action_${log.action}`) }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-900">{{ log.entity_type }}</span>
                                    <span class="text-sm text-gray-500">#{{ log.entity_id }}</span>
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ formatDateTime(log.created_at) }}
                                    <span v-if="log.user"> &middot; {{ log.user.name }}</span>
                                </p>
                                <p v-if="log.ip_address" class="text-xs text-gray-400">{{ log.ip_address }}</p>
                            </div>
                            <button
                                v-if="hasMetadata(log)"
                                type="button"
                                @click="toggleLog(log.id)"
                                class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 transition-colors"
                            >
                                <ChevronDownIcon
                                    :class="[isLogOpen(log.id) ? 'rotate-180' : '', 'h-4 w-4 transition-transform duration-200']"
                                />
                            </button>
                        </div>
                        <div
                            v-if="hasMetadata(log)"
                            v-show="isLogOpen(log.id)"
                            class="disclosure-panel mt-3"
                        >
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <dl class="space-y-2">
                                    <div
                                        v-for="(value, key) in log.metadata_json"
                                        :key="String(key)"
                                    >
                                        <dt class="text-xs font-medium text-gray-500">{{ key }}</dt>
                                        <dd class="mt-0.5 text-sm text-gray-900">
                                            <pre v-if="typeof value === 'object'" class="whitespace-pre-wrap text-xs">{{ formatJsonValue(value) }}</pre>
                                            <span v-else>{{ formatJsonValue(value) }}</span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="logs.data.length === 0" class="p-8 text-center text-sm text-gray-500">
                    {{ t('app.no_results') }}
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <nav v-if="logs.last_page > 1" class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
            <p class="text-sm text-gray-500">
                {{ logs.total }} {{ t('audit.title').toLowerCase() }}
            </p>
            <div class="flex gap-1">
                <template v-for="link in logs.links" :key="link.label">
                    <a
                        v-if="link.url"
                        :href="link.url"
                        :class="[
                            link.active
                                ? 'bg-primary-600 text-white'
                                : 'bg-white text-gray-700 hover:bg-gray-50',
                            'inline-flex h-9 min-w-[36px] items-center justify-center rounded-md border border-gray-300 px-3 text-sm font-medium transition-colors',
                        ]"
                        v-html="link.label"
                        @click.prevent="router.get(link.url, {}, { preserveState: true })"
                    />
                    <span
                        v-else
                        class="inline-flex h-9 min-w-[36px] cursor-not-allowed items-center justify-center rounded-md border border-gray-200 bg-gray-50 px-3 text-sm font-medium text-gray-400"
                        v-html="link.label"
                    />
                </template>
            </div>
        </nav>
    </AppLayout>
</template>

<style scoped>
.disclosure-panel {
    animation: disclosure-open 200ms ease-out;
}

@keyframes disclosure-open {
    from {
        opacity: 0;
        max-height: 0;
        overflow: hidden;
    }
    to {
        opacity: 1;
        max-height: 500px;
        overflow: hidden;
    }
}
</style>
