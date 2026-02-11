<script setup lang="ts">
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useResponsive } from '@/Composables/useResponsive';
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/20/solid';

export interface Column {
    key: string;
    label: string;
    sortable?: boolean;
}

const props = withDefaults(
    defineProps<{
        columns: Column[];
        rows: any[];
    }>(),
    {},
);

const { t } = useI18n();
const { isMobile } = useResponsive();

const sortKey = ref<string | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

const sortedRows = computed(() => {
    if (!sortKey.value) {
        return props.rows;
    }

    const col = props.columns.find((c) => c.key === sortKey.value);
    if (!col?.sortable) {
        return props.rows;
    }

    return [...props.rows].sort((a, b) => {
        const aVal = a[sortKey.value!];
        const bVal = b[sortKey.value!];

        if (aVal == null && bVal == null) return 0;
        if (aVal == null) return 1;
        if (bVal == null) return -1;

        let comparison = 0;
        if (typeof aVal === 'string') {
            comparison = aVal.localeCompare(bVal);
        } else {
            comparison = aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
        }

        return sortDirection.value === 'desc' ? -comparison : comparison;
    });
});

function toggleSort(column: Column) {
    if (!column.sortable) return;

    if (sortKey.value === column.key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = column.key;
        sortDirection.value = 'asc';
    }
}
</script>

<template>
    <!-- Desktop: Table view -->
    <div v-if="!isMobile" class="overflow-hidden rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        scope="col"
                        class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                        :class="{ 'cursor-pointer select-none hover:bg-gray-100': column.sortable }"
                        @click="toggleSort(column)"
                    >
                        <div class="flex items-center gap-1">
                            <span>{{ column.label }}</span>
                            <span
                                v-if="column.sortable"
                                class="inline-flex flex-col"
                            >
                                <ChevronUpIcon
                                    class="h-3 w-3"
                                    :class="
                                        sortKey === column.key && sortDirection === 'asc'
                                            ? 'text-primary-600'
                                            : 'text-gray-300'
                                    "
                                />
                                <ChevronDownIcon
                                    class="-mt-1 h-3 w-3"
                                    :class="
                                        sortKey === column.key && sortDirection === 'desc'
                                            ? 'text-primary-600'
                                            : 'text-gray-300'
                                    "
                                />
                            </span>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <template v-if="sortedRows.length > 0">
                    <tr
                        v-for="(row, rowIndex) in sortedRows"
                        :key="rowIndex"
                        class="hover:bg-gray-50"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="whitespace-nowrap px-4 py-3 text-sm text-gray-700"
                        >
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                {{ row[column.key] }}
                            </slot>
                        </td>
                    </tr>
                </template>
                <tr v-else>
                    <td :colspan="columns.length" class="px-4 py-8 text-center">
                        <slot name="empty">
                            <p class="text-sm text-gray-500">{{ t('app.no_results') }}</p>
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="$slots.pagination" class="border-t border-gray-200 bg-white px-4 py-3">
            <slot name="pagination" />
        </div>
    </div>

    <!-- Mobile: Card view -->
    <div v-else class="space-y-3">
        <template v-if="sortedRows.length > 0">
            <div
                v-for="(row, rowIndex) in sortedRows"
                :key="rowIndex"
                class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
            >
                <dl class="space-y-2">
                    <div
                        v-for="column in columns"
                        :key="column.key"
                        class="flex items-start justify-between gap-2"
                    >
                        <dt class="text-xs font-medium text-gray-500">
                            {{ column.label }}
                        </dt>
                        <dd class="text-right text-sm text-gray-900">
                            <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                                {{ row[column.key] }}
                            </slot>
                        </dd>
                    </div>
                </dl>
            </div>
        </template>

        <div v-else class="py-8 text-center">
            <slot name="empty">
                <p class="text-sm text-gray-500">{{ t('app.no_results') }}</p>
            </slot>
        </div>

        <!-- Pagination -->
        <div v-if="$slots.pagination" class="pt-3">
            <slot name="pagination" />
        </div>
    </div>
</template>
