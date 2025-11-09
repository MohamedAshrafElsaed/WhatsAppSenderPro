<script lang="ts" setup>
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import LanguageToggle from '@/components/LanguageToggle.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { useTranslation } from '@/composables/useTranslation';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { Bell, Search } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { t, isRTL } = useTranslation();
const page = usePage();
const auth = computed(() => page.props.auth);
const sidebarSide = computed(() => (isRTL() ? 'right' : 'left'));
</script>

<template>
    <AppShell :class="isRTL() ? 'rtl' : 'ltr'" variant="sidebar">
        <AppSidebar :side="sidebarSide" />

        <AppContent class="flex flex-col overflow-x-hidden" variant="sidebar">
            <!-- Sticky Header -->
            <header
                :class="isRTL() ? 'flex-row-reverse' : ''"
                class="sticky top-0 z-50 flex h-16 shrink-0 items-center gap-4 border-b border-sidebar-border/70 bg-background/95 px-6 backdrop-blur supports-[backdrop-filter]:bg-background/60 md:px-4"
            >
                <!-- Right Section: Search, Notifications, Language, User -->
                <div
                    :class="[
                        isRTL() ? 'mr-auto flex-row-reverse' : 'ml-auto',
                        !props.breadcrumbs || props.breadcrumbs.length === 0
                            ? 'flex-1 justify-end'
                            : '',
                    ]"
                    class="flex items-center gap-2"
                >
                    <!-- Search Button -->
                    <Button class="group h-9 w-9" size="icon" variant="ghost">
                        <Search
                            class="size-4 opacity-80 group-hover:opacity-100"
                        />
                        <span class="sr-only">{{
                            t('nav.search', 'Search')
                        }}</span>
                    </Button>

                    <!-- Notifications Button -->
                    <Button
                        class="group relative h-9 w-9"
                        size="icon"
                        variant="ghost"
                    >
                        <Bell
                            class="size-4 opacity-80 group-hover:opacity-100"
                        />
                        <span class="sr-only">{{
                            t('nav.notifications', 'Notifications')
                        }}</span>
                        <!-- Notification Badge -->
                        <span class="absolute top-1 right-1 flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#25D366] opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex h-2 w-2 rounded-full bg-[#25D366]"
                            ></span>
                        </span>
                    </Button>

                    <!-- Language Toggle -->
                    <div class="hidden md:block">
                        <LanguageToggle />
                    </div>

                    <!-- User Menu -->
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                class="relative h-9 w-9 rounded-full focus-within:ring-2 focus-within:ring-[#25D366]"
                                size="icon"
                                variant="ghost"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user?.avatar"
                                        :alt="auth.user.name"
                                        :src="auth.user.avatar"
                                    />
                                    <AvatarFallback
                                        class="rounded-full bg-[#25D366] font-semibold text-white"
                                    >
                                        {{
                                            getInitials(
                                                auth.user?.first_name +
                                                    ' ' +
                                                    auth.user?.last_name,
                                            )
                                        }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </header>

            <!-- Main Content Area -->
            <div class="flex-1 overflow-auto">
                <slot />
            </div>
        </AppContent>
    </AppShell>
</template>
