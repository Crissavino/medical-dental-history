<script setup lang="ts">
import { computed, watch, onBeforeUnmount } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    HomeIcon,
    UsersIcon,
    ClipboardDocumentListIcon,
    XMarkIcon,
    UserPlusIcon,
} from '@heroicons/vue/24/outline';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import type { PageProps } from '@/types';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const { t } = useI18n();
const page = usePage<PageProps>();

const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value.role === 'admin');

interface NavItem {
    name: string;
    href: string;
    icon: typeof HomeIcon;
    routeName: string;
    adminOnly: boolean;
}

const navigation = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            name: t('app.dashboard'),
            href: route('dashboard'),
            icon: HomeIcon,
            routeName: 'dashboard',
            adminOnly: false,
        },
        {
            name: t('app.patients'),
            href: route('patients.index'),
            icon: UsersIcon,
            routeName: 'patients.*',
            adminOnly: false,
        },
        {
            name: t('app.audit_logs'),
            href: route('audit-logs.index'),
            icon: ClipboardDocumentListIcon,
            routeName: 'audit-logs.*',
            adminOnly: true,
        },
    ];

    return items.filter((item) => !item.adminOnly || isAdmin.value);
});

function isActive(routeName: string): boolean {
    return route().current(routeName) ?? false;
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        emit('close');
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            document.addEventListener('keydown', onKeydown);
            document.body.style.overflow = 'hidden';
        } else {
            document.removeEventListener('keydown', onKeydown);
            document.body.style.overflow = '';
        }
    },
);

onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <!-- Mobile / Tablet sidebar (drawer overlay) -->
    <Teleport to="body">
        <div
            v-if="open"
            class="relative z-50 md:hidden"
            role="dialog"
            aria-modal="true"
        >
            <!-- Backdrop overlay -->
            <Transition
                appear
                enter-active-class="transition-opacity ease-linear duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity ease-linear duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="open"
                    class="fixed inset-0 bg-gray-900/80"
                    @click="emit('close')"
                />
            </Transition>

            <div class="fixed inset-0 flex">
                <!-- Sliding panel -->
                <Transition
                    appear
                    enter-active-class="transition ease-in-out duration-300 transform"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition ease-in-out duration-300 transform"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                >
                    <div
                        v-if="open"
                        class="relative mr-16 flex w-full max-w-xs flex-1"
                    >
                        <!-- Close button -->
                        <Transition
                            appear
                            enter-active-class="ease-in-out duration-300"
                            enter-from-class="opacity-0"
                            enter-to-class="opacity-100"
                            leave-active-class="ease-in-out duration-300"
                            leave-from-class="opacity-100"
                            leave-to-class="opacity-0"
                        >
                            <div
                                v-if="open"
                                class="absolute left-full top-0 flex w-16 justify-center pt-5"
                            >
                                <button
                                    type="button"
                                    class="-m-2.5 p-2.5"
                                    @click="emit('close')"
                                >
                                    <span class="sr-only">{{ t('app.close') }}</span>
                                    <XMarkIcon class="h-6 w-6 text-white" aria-hidden="true" />
                                </button>
                            </div>
                        </Transition>

                        <!-- Mobile sidebar content -->
                        <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4">
                            <div class="flex h-16 shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo />
                                </Link>
                            </div>
                            <nav class="flex flex-1 flex-col">
                                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                    <li>
                                        <ul role="list" class="-mx-2 space-y-1">
                                            <li v-for="item in navigation" :key="item.routeName">
                                                <Link
                                                    :href="item.href"
                                                    :class="[
                                                        isActive(item.routeName)
                                                            ? 'bg-primary-50 text-primary-600'
                                                            : 'text-gray-700 hover:bg-gray-50 hover:text-primary-600',
                                                        'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                                    ]"
                                                    @click="emit('close')"
                                                >
                                                    <component
                                                        :is="item.icon"
                                                        :class="[
                                                            isActive(item.routeName)
                                                                ? 'text-primary-600'
                                                                : 'text-gray-400 group-hover:text-primary-600',
                                                            'h-6 w-6 shrink-0',
                                                        ]"
                                                        aria-hidden="true"
                                                    />
                                                    {{ item.name }}
                                                </Link>
                                            </li>
                                        </ul>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </Teleport>

    <!-- Desktop sidebar (static) -->
    <div class="hidden md:fixed md:inset-y-0 md:z-50 md:flex md:w-72 md:flex-col">
        <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white px-6 pb-4">
            <div class="flex h-16 shrink-0 items-center">
                <Link :href="route('dashboard')">
                    <ApplicationLogo />
                </Link>
            </div>
            <nav class="flex flex-1 flex-col">
                <ul role="list" class="flex flex-1 flex-col gap-y-7">
                    <li>
                        <ul role="list" class="-mx-2 space-y-1">
                            <li v-for="item in navigation" :key="item.routeName">
                                <Link
                                    :href="item.href"
                                    :class="[
                                        isActive(item.routeName)
                                            ? 'bg-primary-50 text-primary-600'
                                            : 'text-gray-700 hover:bg-gray-50 hover:text-primary-600',
                                        'group flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6',
                                    ]"
                                >
                                    <component
                                        :is="item.icon"
                                        :class="[
                                            isActive(item.routeName)
                                                ? 'text-primary-600'
                                                : 'text-gray-400 group-hover:text-primary-600',
                                            'h-6 w-6 shrink-0',
                                        ]"
                                        aria-hidden="true"
                                    />
                                    {{ item.name }}
                                </Link>
                            </li>
                        </ul>
                    </li>

                    <!-- New Patient button -->
                    <li>
                        <Link
                            :href="route('patients.create')"
                            class="-mx-2 flex items-center gap-x-3 rounded-md bg-primary-50 p-2 text-sm font-semibold leading-6 text-primary-700 hover:bg-primary-100 transition-colors"
                        >
                            <UserPlusIcon class="h-6 w-6 shrink-0 text-primary-600" aria-hidden="true" />
                            {{ t('patient.new') }}
                        </Link>
                    </li>

                    <!-- User info at bottom of desktop sidebar -->
                    <li class="mt-auto">
                        <div class="flex items-center gap-x-3 rounded-md px-2 py-3 text-sm font-semibold leading-6 text-gray-700">
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-medium text-gray-600"
                            >
                                {{ user.name.charAt(0).toUpperCase() }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="block truncate">{{ user.name }}</span>
                                <span class="block text-xs font-normal capitalize text-gray-400">{{ user.role }}</span>
                            </div>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</template>
