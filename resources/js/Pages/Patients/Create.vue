<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Components/Layout/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { DocumentTextIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();

const form = useForm({
    first_name: '',
    last_name: '',
    date_of_birth: '',
    gender: '' as '' | 'male' | 'female' | 'other',
    email: '',
    phone: '',
    address: '',
    city: '',
    county: '',
    cnp: '',
    notes: '',
    redirect_to_anamnesis: false,
});

function submit(redirectToAnamnesis = false) {
    form.redirect_to_anamnesis = redirectToAnamnesis;
    form.post(route('patients.store'));
}
</script>

<template>
    <Head :title="t('patient.new')" />

    <AppLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link
                    :href="route('patients.index')"
                    class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </Link>
                <h1 class="text-2xl font-bold text-gray-900">{{ t('patient.new') }}</h1>
            </div>
        </template>

        <div class="mx-auto max-w-3xl">
            <form @submit.prevent="submit(false)" class="space-y-8">
                <!-- Personal Information -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">{{ t('patient.personal_info') }}</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel :value="t('patient.first_name')" />
                            <input
                                v-model="form.first_name"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.first_name" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel :value="t('patient.last_name')" />
                            <input
                                v-model="form.last_name"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                required
                            />
                            <InputError :message="form.errors.last_name" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel :value="t('patient.date_of_birth')" />
                            <input
                                v-model="form.date_of_birth"
                                type="date"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <InputError :message="form.errors.date_of_birth" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel :value="t('patient.gender')" />
                            <select
                                v-model="form.gender"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            >
                                <option value="">---</option>
                                <option value="male">{{ t('patient.male') }}</option>
                                <option value="female">{{ t('patient.female') }}</option>
                                <option value="other">{{ t('patient.other') }}</option>
                            </select>
                            <InputError :message="form.errors.gender" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel :value="t('patient.cnp')" />
                            <input
                                v-model="form.cnp"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                maxlength="13"
                            />
                            <InputError :message="form.errors.cnp" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">{{ t('patient.contact_info') }}</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <InputLabel :value="t('patient.email')" />
                            <input
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel :value="t('patient.phone')" />
                            <input
                                v-model="form.phone"
                                type="tel"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <InputError :message="form.errors.phone" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Address -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">{{ t('patient.address') }}</h2>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <InputLabel :value="t('patient.address')" />
                            <input
                                v-model="form.address"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <InputError :message="form.errors.address" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel :value="t('patient.city')" />
                            <input
                                v-model="form.city"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <InputError :message="form.errors.city" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel :value="t('patient.county')" />
                            <input
                                v-model="form.county"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            />
                            <InputError :message="form.errors.county" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="mb-6 text-lg font-semibold text-gray-900">{{ t('patient.notes') }}</h2>
                    <div>
                        <textarea
                            v-model="form.notes"
                            rows="4"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            :placeholder="t('patient.notes')"
                        />
                        <InputError :message="form.errors.notes" class="mt-1" />
                    </div>
                </div>

                <!-- Form actions -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        :href="route('patients.index')"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors"
                    >
                        {{ t('app.cancel') }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg v-if="form.processing && !form.redirect_to_anamnesis" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ t('app.save') }}
                    </button>
                    <button
                        type="button"
                        @click="submit(true)"
                        :disabled="form.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-green-500 transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <svg v-if="form.processing && form.redirect_to_anamnesis" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <DocumentTextIcon v-else class="h-4 w-4" />
                        {{ t('patient.save_and_fill_anamnesis') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
