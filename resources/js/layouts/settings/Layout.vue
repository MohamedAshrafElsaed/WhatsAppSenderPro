<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/composables/useTranslation';
import { toUrl, urlIsActive } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/dashboard/settings/appearance';
import { edit as editProfile } from '@/routes/dashboard/settings/profile';
import { show } from '@/routes/dashboard/settings/two-factor';
import { edit as editPassword } from '@/routes/dashboard/settings/password';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { t } = useTranslation();
const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');

const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: t('settings.profile', 'Profile'),
        href: editProfile(),
    },
    {
        title: t('settings.password', 'Password'),
        href: editPassword(),
    },
    {
        title: t('settings.two_factor', 'Two-Factor Auth'),
        href: show(),
    },
    {
        title: t('settings.appearance', 'Appearance'),
        href: editAppearance(),
    },
]);

const currentPath =
    typeof window !== 'undefined' ? window.location.pathname : '';
</script>

<template>
    <div :class="isRTL ? 'text-right' : 'text-left'" class="px-4 py-6">
        <Heading
            :description="
                t(
                    'settings.description',
                    'Manage your profile and account settings',
                )
            "
            :title="t('settings.title', 'Settings')"
        />

        <div
            :class="
                isRTL
                    ? 'lg:flex-row-reverse lg:space-x-12 lg:space-x-reverse'
                    : 'lg:space-x-12'
            "
            class="flex flex-col lg:flex-row"
        >
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :class="[
                            'w-full',
                            isRTL ? 'justify-end' : 'justify-start',
                            { 'bg-muted': urlIsActive(item.href, currentPath) },
                        ]"
                        as-child
                        variant="ghost"
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
