<script lang="ts" setup>
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { index as dashboard } from '@/routes/dashboard';
import { edit } from '@/routes/dashboard/settings/password';
import { Form, Head } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import { type BreadcrumbItem } from '@/types';

const { t, isRTL } = useTranslation();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('dashboard.title', 'Dashboard'),
        href: dashboard().url,
    },
    {
        title: t('settings.title', 'Settings'),
        href: edit().url,
    },
    {
        title: t('settings.password', 'Password'),
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.password_settings', 'Password Settings')" />

        <SettingsLayout>
            <div :dir="isRTL() ? 'rtl' : 'ltr'" class="space-y-6">
                <HeadingSmall
                    :description="
                        t(
                            'settings.update_password_desc',
                            'Ensure your account is using a long, random password to stay secure',
                        )
                    "
                    :title="t('settings.update_password', 'Update password')"
                />

                <Form
                    v-slot="{ errors, processing, recentlySuccessful }"
                    :options="{
                        preserveScroll: true,
                    }"
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-6"
                    reset-on-success
                    v-bind="PasswordController.update.form()"
                >
                    <div class="grid gap-2">
                        <Label
                            :class="isRTL() ? 'text-right' : 'text-left'"
                            for="current_password"
                        >
                            {{
                                t(
                                    'settings.current_password',
                                    'Current password',
                                )
                            }}
                        </Label>
                        <Input
                            id="current_password"
                            :placeholder="
                                t(
                                    'settings.current_password',
                                    'Current password',
                                )
                            "
                            autocomplete="current-password"
                            class="mt-1 block w-full"
                            dir="ltr"
                            name="current_password"
                            type="password"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label
                            :class="isRTL() ? 'text-right' : 'text-left'"
                            for="password"
                        >
                            {{ t('settings.new_password', 'New password') }}
                        </Label>
                        <Input
                            id="password"
                            :placeholder="
                                t('settings.new_password', 'New password')
                            "
                            autocomplete="new-password"
                            class="mt-1 block w-full"
                            dir="ltr"
                            name="password"
                            type="password"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label
                            :class="isRTL() ? 'text-right' : 'text-left'"
                            for="password_confirmation"
                        >
                            {{
                                t(
                                    'settings.confirm_password',
                                    'Confirm password',
                                )
                            }}
                        </Label>
                        <Input
                            id="password_confirmation"
                            :placeholder="
                                t(
                                    'settings.confirm_password',
                                    'Confirm password',
                                )
                            "
                            autocomplete="new-password"
                            class="mt-1 block w-full"
                            dir="ltr"
                            name="password_confirmation"
                            type="password"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div
                        :class="[
                            'flex items-center gap-4',
                            isRTL() ? 'flex-row-reverse' : 'flex-row',
                        ]"
                    >
                        <Button
                            :disabled="processing"
                            class="bg-[#25D366] text-white hover:bg-[#128C7E]"
                            data-test="update-password-button"
                        >
                            {{ t('settings.save_password', 'Save password') }}
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-[#25D366]"
                            >
                                {{ t('settings.saved', 'Saved.') }}
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
