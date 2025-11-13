<script lang="ts" setup>
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/dashboard/settings/profile';
import { index as dashboard } from '@/routes/dashboard';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { type BreadcrumbItem } from '@/types';
import { Globe, Phone, Mail, Calendar, CheckCircle, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

interface UserDetails {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    mobile_number: string;
    country?: {
        id: number;
        name: string;
        phone_code: string;
    };
    industry?: {
        id: number;
        name: string;
    };
    locale: string;
    email_verified_at?: string;
    mobile_verified_at?: string;
    created_at: string;
    subscription_status: string;
}

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    user: UserDetails;
}

const props = defineProps<Props>();

const { t, isRTL } = useTranslation();
const page = usePage();

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

// Format dates
const memberSince = computed(() => {
    if (!props.user.created_at) return '';
    return new Date(props.user.created_at).toLocaleDateString();
});

const formattedPhone = computed(() => {
    if (!props.user.country || !props.user.mobile_number) return props.user.mobile_number;
    return `+${props.user.country.phone_code} ${props.user.mobile_number}`;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.profile_settings', 'Profile Settings')" />

        <SettingsLayout>
            <div :dir="isRTL() ? 'rtl' : 'ltr'" class="space-y-8">
                <!-- Profile Overview Card -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('settings.profile_overview', 'Profile Overview') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Email Status -->
                            <div class="flex items-center gap-2">
                                <Mail class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm">{{ user.email }}</span>
                                <Badge v-if="user.email_verified_at" variant="default" class="bg-[#25D366]">
                                    <CheckCircle class="h-3 w-3 mr-1" />
                                    {{ t('settings.verified', 'Verified') }}
                                </Badge>
                                <Badge v-else variant="destructive">
                                    <XCircle class="h-3 w-3 mr-1" />
                                    {{ t('settings.unverified', 'Unverified') }}
                                </Badge>
                            </div>

                            <!-- Phone Status -->
                            <div class="flex items-center gap-2">
                                <Phone class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm">{{ formattedPhone }}</span>
                                <Badge v-if="user.mobile_verified_at" variant="default" class="bg-[#25D366]">
                                    <CheckCircle class="h-3 w-3 mr-1" />
                                    {{ t('settings.verified', 'Verified') }}
                                </Badge>
                            </div>

                            <!-- Country -->
                            <div v-if="user.country" class="flex items-center gap-2">
                                <Globe class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm">{{ user.country.name }}</span>
                            </div>

                            <!-- Member Since -->
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-muted-foreground" />
                                <span class="text-sm">
                                    {{ t('settings.member_since', 'Member since') }} {{ memberSince }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Profile Form -->
                <div class="space-y-6">
                    <HeadingSmall
                        :description="t('settings.update_profile_info', 'Update your name and email address')"
                        :title="t('settings.profile_information', 'Profile Information')"
                    />

                    <Form
                        v-slot="{ errors, processing, recentlySuccessful }"
                        class="space-y-6"
                        v-bind="ProfileController.update.form()"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- First Name -->
                            <div class="grid gap-2">
                                <Label :class="isRTL() ? 'text-right' : 'text-left'" for="first_name">
                                    {{ t('settings.first_name', 'First Name') }}
                                </Label>
                                <Input
                                    id="first_name"
                                    :default-value="user.first_name"
                                    name="first_name"
                                    :placeholder="t('settings.first_name', 'First Name')"
                                    required
                                    :dir="isRTL() ? 'rtl' : 'ltr'"
                                />
                                <InputError :message="errors.first_name" />
                            </div>

                            <!-- Last Name -->
                            <div class="grid gap-2">
                                <Label :class="isRTL() ? 'text-right' : 'text-left'" for="last_name">
                                    {{ t('settings.last_name', 'Last Name') }}
                                </Label>
                                <Input
                                    id="last_name"
                                    :default-value="user.last_name"
                                    name="last_name"
                                    :placeholder="t('settings.last_name', 'Last Name')"
                                    required
                                    :dir="isRTL() ? 'rtl' : 'ltr'"
                                />
                                <InputError :message="errors.last_name" />
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="grid gap-2">
                            <Label :class="isRTL() ? 'text-right' : 'text-left'" for="email">
                                {{ t('settings.email', 'Email address') }}
                            </Label>
                            <Input
                                id="email"
                                :default-value="user.email"
                                autocomplete="username"
                                name="email"
                                :placeholder="t('settings.email', 'Email address')"
                                required
                                type="email"
                                dir="ltr"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <!-- Language Preference -->
                        <div class="grid gap-2">
                            <Label :class="isRTL() ? 'text-right' : 'text-left'" for="locale">
                                {{ t('settings.language', 'Language Preference') }}
                            </Label>
                            <Select name="locale" :defaultValue="user.locale">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="en">English</SelectItem>
                                    <SelectItem value="ar">العربية</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.locale" />
                        </div>

                        <!-- Email Verification Notice -->
                        <div v-if="mustVerifyEmail && !user.email_verified_at">
                            <p class="text-sm text-muted-foreground">
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

                        <!-- Submit Button -->
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

                <!-- Delete Account Section -->
                <DeleteUser />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
