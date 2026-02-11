<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    HomeIcon,
    UsersIcon,
    UserCircleIcon,
    PlusCircleIcon,
} from '@heroicons/vue/24/outline';
import {
    HomeIcon as HomeIconSolid,
    UsersIcon as UsersIconSolid,
    UserCircleIcon as UserCircleIconSolid,
    PlusCircleIcon as PlusCircleIconSolid,
} from '@heroicons/vue/24/solid';

const { t } = useI18n();

interface MobileNavItem {
    name: string;
    href: string;
    icon: typeof HomeIcon;
    activeIcon: typeof HomeIconSolid;
    routeName: string;
    highlight?: boolean;
}

const items: MobileNavItem[] = [
    {
        name: t('app.dashboard'),
        href: route('dashboard'),
        icon: HomeIcon,
        activeIcon: HomeIconSolid,
        routeName: 'dashboard',
    },
    {
        name: t('app.patients'),
        href: route('patients.index'),
        icon: UsersIcon,
        activeIcon: UsersIconSolid,
        routeName: 'patients.*',
    },
    {
        name: t('app.create'),
        href: route('patients.create'),
        icon: PlusCircleIcon,
        activeIcon: PlusCircleIconSolid,
        routeName: 'patients.create',
        highlight: true,
    },
    {
        name: t('app.profile'),
        href: route('profile.edit'),
        icon: UserCircleIcon,
        activeIcon: UserCircleIconSolid,
        routeName: 'profile.*',
    },
];

function isActive(routeName: string): boolean {
    return route().current(routeName) ?? false;
}
</script>

<template>
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white md:hidden">
        <nav class="flex items-center justify-around">
            <Link
                v-for="item in items"
                :key="item.routeName"
                :href="item.href"
                :class="[
                    'flex flex-1 flex-col items-center gap-1 px-2 py-2 text-xs font-medium transition-colors duration-150',
                    item.highlight && !isActive(item.routeName)
                        ? 'text-primary-600'
                        : isActive(item.routeName)
                            ? 'text-primary-600'
                            : 'text-gray-500 hover:text-gray-700',
                ]"
            >
                <component
                    :is="isActive(item.routeName) ? item.activeIcon : item.icon"
                    :class="['h-6 w-6', item.highlight && !isActive(item.routeName) ? 'text-primary-500' : '']"
                    aria-hidden="true"
                />
                <span>{{ item.name }}</span>
            </Link>
        </nav>
    </div>
</template>
