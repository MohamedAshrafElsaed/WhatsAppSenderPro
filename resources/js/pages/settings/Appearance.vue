<script lang="ts" setup>
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { index as dashboard } from '@/routes/dashboard';
import { edit } from '@/routes/dashboard/settings/appearance';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

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
        title: t('settings.appearance', 'Appearance'),
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head
            :title="t('settings.appearance_settings', 'Appearance Settings')"
        />

        <SettingsLayout>
            <div :dir="isRTL() ? 'rtl' : 'ltr'" class="space-y-6">
                <HeadingSmall
                    :description="
                        t(
                            'settings.update_appearance_desc',
                            'Customize the look and feel of your account',
                        )
                    "
                    :title="
                        t('settings.appearance_settings', 'Appearance settings')
                    "
                />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
