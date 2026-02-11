<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import {
    Bars3Icon,
    ChevronDownIcon,
    UserCircleIcon,
    ArrowRightStartOnRectangleIcon,
    LanguageIcon,
    CheckIcon,
    XMarkIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';
import Sidebar from '@/Components/Layout/Sidebar.vue';
import MobileNav from '@/Components/Layout/MobileNav.vue';
import type { PageProps } from '@/types';

const { t, locale } = useI18n();
const page = usePage<PageProps>();

const sidebarOpen = ref(false);

const user = computed(() => page.props.auth.user);
const flash = computed(() => page.props.flash);

const showFlash = ref(false);

watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash?.success || newFlash?.error) {
            showFlash.value = true;
            setTimeout(() => {
                showFlash.value = false;
            }, 5000);
        }
    },
    { immediate: true },
);

function dismissFlash() {
    showFlash.value = false;
}

interface LocaleOption {
    code: string;
    label: string;
}

const locales: LocaleOption[] = [
    { code: 'en', label: 'EN' },
    { code: 'ro', label: 'RO' },
];

function switchLocale(code: string) {
    locale.value = code;
    localStorage.setItem('locale', code);
    langMenuOpen.value = false;
}

function logout() {
    userMenuOpen.value = false;
    router.post(route('logout'));
}

// Dropdown state
const langMenuOpen = ref(false);
const userMenuOpen = ref(false);

const langMenuRef = ref<HTMLElement | null>(null);
const userMenuRef = ref<HTMLElement | null>(null);

function toggleLangMenu() {
    langMenuOpen.value = !langMenuOpen.value;
    if (langMenuOpen.value) {
        userMenuOpen.value = false;
    }
}

function toggleUserMenu() {
    userMenuOpen.value = !userMenuOpen.value;
    if (userMenuOpen.value) {
        langMenuOpen.value = false;
    }
}

function handleClickOutside(event: MouseEvent) {
    const target = event.target as Node;
    if (langMenuOpen.value && langMenuRef.value && !langMenuRef.value.contains(target)) {
        langMenuOpen.value = false;
    }
    if (userMenuOpen.value && userMenuRef.value && !userMenuRef.value.contains(target)) {
        userMenuOpen.value = false;
    }
}

function handleEscape(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        langMenuOpen.value = false;
        userMenuOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscape);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscape);
});
</script>

<template>
    <div>
        <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

        <!-- Main content area -->
        <div class="lg:pl-72">
            <!-- Top navigation bar -->
            <div
                class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8"
            >
                <!-- Mobile sidebar toggle -->
                <button
                    type="button"
                    class="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                    @click="sidebarOpen = true"
                >
                    <span class="sr-only">Open sidebar</span>
                    <Bars3Icon class="h-6 w-6" aria-hidden="true" />
                </button>

                <!-- Separator (mobile only) -->
                <div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true" />

                <!-- Top bar content -->
                <div class="flex flex-1 items-center justify-end gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Page title slot -->
                    <div class="flex flex-1 items-center">
                        <slot name="header" />
                    </div>

                    <!-- Right side actions -->
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Language switcher -->
                        <div ref="langMenuRef" class="relative">
                            <button
                                type="button"
                                class="flex items-center gap-x-1 rounded-md px-2 py-1.5 text-sm font-medium text-gray-600 transition-colors duration-150 hover:bg-gray-100 hover:text-gray-900"
                                @click="toggleLangMenu"
                            >
                                <LanguageIcon class="h-5 w-5" aria-hidden="true" />
                                <span class="hidden sm:inline">{{ locale.toUpperCase() }}</span>
                                <ChevronDownIcon class="h-4 w-4 text-gray-400" aria-hidden="true" />
                            </button>

                            <transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div
                                    v-show="langMenuOpen"
                                    class="absolute right-0 z-10 mt-2 w-36 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                >
                                    <button
                                        v-for="loc in locales"
                                        :key="loc.code"
                                        type="button"
                                        class="flex w-full items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                        @click="switchLocale(loc.code)"
                                    >
                                        <span>
                                            {{ loc.code === 'en' ? t('app.english') : t('app.romanian') }}
                                        </span>
                                        <CheckIcon
                                            v-if="locale === loc.code"
                                            class="h-4 w-4 text-primary-600"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </div>
                            </transition>
                        </div>

                        <!-- Separator -->
                        <div class="hidden h-6 w-px bg-gray-200 lg:block" aria-hidden="true" />

                        <!-- User dropdown -->
                        <div ref="userMenuRef" class="relative">
                            <button
                                type="button"
                                class="-m-1.5 flex items-center rounded-md p-1.5 transition-colors duration-150 hover:bg-gray-100"
                                @click="toggleUserMenu"
                            >
                                <span
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-sm font-medium text-primary-700"
                                >
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </span>
                                <span class="hidden lg:flex lg:items-center">
                                    <span
                                        class="ml-3 text-sm font-semibold leading-6 text-gray-900"
                                        aria-hidden="true"
                                    >
                                        {{ user.name }}
                                    </span>
                                    <ChevronDownIcon
                                        class="ml-2 h-4 w-4 text-gray-400"
                                        aria-hidden="true"
                                    />
                                </span>
                            </button>

                            <transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95"
                            >
                                <div
                                    v-show="userMenuOpen"
                                    class="absolute right-0 z-10 mt-2.5 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                >
                                    <Link
                                        :href="route('profile.edit')"
                                        class="flex items-center gap-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                        @click="userMenuOpen = false"
                                    >
                                        <UserCircleIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
                                        {{ t('app.profile') }}
                                    </Link>
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                        @click="logout"
                                    >
                                        <ArrowRightStartOnRectangleIcon
                                            class="h-5 w-5 text-gray-400"
                                            aria-hidden="true"
                                        />
                                        {{ t('app.logout') }}
                                    </button>
                                </div>
                            </transition>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flash messages -->
            <transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="transform -translate-y-2 opacity-0"
                enter-to-class="transform translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="transform translate-y-0 opacity-100"
                leave-to-class="transform -translate-y-2 opacity-0"
            >
                <div
                    v-if="showFlash && (flash?.success || flash?.error)"
                    class="mx-4 mt-4 sm:mx-6 lg:mx-8"
                >
                    <!-- Success flash -->
                    <div
                        v-if="flash?.success"
                        class="rounded-md bg-green-50 p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <CheckCircleIcon class="h-5 w-5 text-green-400" aria-hidden="true" />
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-green-800">
                                    {{ flash.success }}
                                </p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button
                                    type="button"
                                    class="inline-flex rounded-md bg-green-50 p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 focus:ring-offset-green-50"
                                    @click="dismissFlash"
                                >
                                    <span class="sr-only">{{ t('app.close') }}</span>
                                    <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Error flash -->
                    <div
                        v-if="flash?.error"
                        class="rounded-md bg-red-50 p-4"
                    >
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <ExclamationTriangleIcon class="h-5 w-5 text-red-400" aria-hidden="true" />
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-red-800">
                                    {{ flash.error }}
                                </p>
                            </div>
                            <div class="ml-auto pl-3">
                                <button
                                    type="button"
                                    class="inline-flex rounded-md bg-red-50 p-1.5 text-red-500 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 focus:ring-offset-red-50"
                                    @click="dismissFlash"
                                >
                                    <span class="sr-only">{{ t('app.close') }}</span>
                                    <XMarkIcon class="h-5 w-5" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Page content -->
            <main class="pb-20 lg:pb-0">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>

        <!-- Mobile bottom navigation -->
        <MobileNav />
    </div>
</template>
