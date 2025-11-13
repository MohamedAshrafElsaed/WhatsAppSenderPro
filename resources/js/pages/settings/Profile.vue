<script lang="ts" setup>
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/dashboard/settings/profile';
import { index as dashboard } from '@/routes/dashboard';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { type BreadcrumbItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const { t, isRTL } = useTranslation();
const page = usePage();
const user = page.props.auth.user;

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
        title: t('settings.profile', 'Profile'),
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.profile_settings', 'Profile Settings')" />

        <SettingsLayout>
            <div :dir="isRTL() ? 'rtl' : 'ltr'" class="flex flex-col space-y-6">
                <HeadingSmall
                    :description="t('settings.update_profile_info', 'Update your name and email address')"
                    :title="t('settings.profile_information', 'Profile Information')"
                />

                <Form
                    v-slot="{ errors, processing, recentlySuccessful }"
                    class="space-y-6"
                    v-bind="ProfileController.update.form()"
                >
                    <div class="grid gap-2">
                        <Label :class="isRTL() ? 'text-right' : 'text-left'" for="name">
                            {{ t('settings.name', 'Name') }}
                        </Label>
                        <Input
                            id="name"
                            :default-value="user.name"
                            autocomplete="name"
                            class="mt-1 block w-full"
                            name="name"
                            :placeholder="t('settings.full_name', 'Full name')"
                            required
                            :dir="isRTL() ? 'rtl' : 'ltr'"
                        />
                        <InputError :message="errors.name" class="mt-2" />
                    </div>

                    <div class="grid gap-2">
                        <Label :class="isRTL() ? 'text-right' : 'text-left'" for="email">
                            {{ t('settings.email', 'Email address') }}
                        </Label>
                        <Input
                            id="email"
                            :default-value="user.email"
                            autocomplete="username"
                            class="mt-1 block w-full"
                            name="email"
                            :placeholder="t('settings.email', 'Email address')"
                            required
                            type="email"
                            dir="ltr"
                        />
                        <InputError :message="errors.email" class="mt-2" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            {{ t('settings.email_unverified', 'Your email address is unverified.') }}
                            <Link
                                :href="send()"
                                as="button"
                                class="text-[#25D366] underline decoration-[#25D366]/30 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-[#25D366]"
                            >
                                {{ t('settings.resend_verification', 'Click here to resend the verification email.') }}
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-[#25D366]"
                        >
                            {{ t('settings.verification_sent', 'A new verification link has been sent to your email address.') }}
                        </div>
                    </div>

                    <div :class="[
                        'flex items-center gap-4',
                        isRTL() ? 'flex-row-reverse' : 'flex-row'
                    ]">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            class="bg-[#25D366] hover:bg-[#128C7E] text-white"
                        >
                            {{ t('settings.save', 'Save') }}
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
                                {{ t('settings.saved', 'Saved') }}
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
