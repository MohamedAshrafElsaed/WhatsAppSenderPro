<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useTranslation } from '@/composables/useTranslation';
import { toUrl, urlIsActive } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/dashboard/settings/appearance';
import { edit as editPassword } from '@/routes/dashboard/settings/password';
import { edit as editProfile } from '@/routes/dashboard/settings/profile';
import { index as subscription } from '@/routes/dashboard/settings/subscription';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { CreditCard, Lock, Palette, User } from 'lucide-vue-next';
import { computed } from 'vue';

const { t, isRTL } = useTranslation();

const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: t('settings.profile', 'Profile'),
        href: editProfile(),
        icon: User,
    },
    {
        title: t('settings.password', 'Password'),
        href: editPassword(),
        icon: Lock,
    },
    {
        title: t('settings.subscription', 'Subscription'),
        href: subscription(),
        icon: CreditCard,
    },
    {
        title: t('settings.appearance', 'Appearance'),
        href: editAppearance(),
        icon: Palette,
    },
]);

const currentPath =
    typeof window !== 'undefined' ? window.location.pathname : '';
</script>

<template>
    <div :class="isRTL() ? 'text-right' : 'text-left'" class="px-4 py-6">
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
                isRTL()
                    ? 'lg:flex-row-reverse lg:space-x-12 lg:space-x-reverse'
                    : 'lg:space-x-12'
            "
            class="flex flex-col lg:flex-row"
        >
            <aside class="w-full max-w-xl lg:w-56">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :class="[
                            'w-full',
                            isRTL() ? 'justify-end' : 'justify-start',
                            urlIsActive(item.href, currentPath)
                                ? 'border-[#25D366] bg-[#25D366]/10 text-[#25D366] hover:bg-[#25D366]/20'
                                : 'hover:bg-muted hover:text-[#25D366]',
                        ]"
                        as-child
                        variant="ghost"
                    >
                        <Link :href="item.href" class="flex items-center gap-2">
                            <component
                                :is="item.icon"
                                :class="[
                                    'h-4 w-4',
                                    isRTL() ? 'order-2' : 'order-1',
                                ]"
                            />
                            <span :class="isRTL() ? 'order-1' : 'order-2'">
                                {{ item.title }}
                            </span>
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 bg-border/50 lg:hidden" />

            <div class="flex-1 md:max-w-4xl">
                <section class="space-y-8">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
