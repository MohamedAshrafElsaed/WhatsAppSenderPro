<script lang="ts" setup>
import AppContent from '@/components/AppContent.vue';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppShell from '@/components/AppShell.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import LanguageToggle from '@/components/LanguageToggle.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { toUrl, urlIsActive } from '@/lib/utils';
import { index as dashboard } from '@/routes/dashboard';
import type { BreadcrumbItem, NavItem } from '@/types';
import { InertiaLinkProps, Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, Menu } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);

const isCurrentRoute = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) =>
        urlIsActive(url, page.url),
);

const activeItemStyles = computed(
    () => (url: NonNullable<InertiaLinkProps['href']>) =>
        isCurrentRoute.value(toUrl(url))
            ? 'text-neutral-900 dark:bg-neutral-800 dark:text-neutral-100'
            : '',
);

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];
</script>

<template>
    <AppShell class="flex-col">
        <!-- Header -->
        <div>
            <div class="border-b border-sidebar-border/80">
                <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                    <!-- Mobile Menu -->
                    <div class="lg:hidden">
                        <Sheet>
                            <SheetTrigger :as-child="true">
                                <Button
                                    class="mr-2 h-9 w-9"
                                    size="icon"
                                    variant="ghost"
                                >
                                    <Menu class="h-5 w-5" />
                                </Button>
                            </SheetTrigger>
                            <SheetContent class="w-[300px] p-6" side="left">
                                <SheetTitle class="sr-only"
                                    >Navigation Menu
                                </SheetTitle>
                                <SheetHeader
                                    class="flex justify-start text-left"
                                >
                                    <AppLogoIcon
                                        class="size-6 fill-current text-black dark:text-white"
                                    />
                                </SheetHeader>
                                <div
                                    class="flex h-full flex-1 flex-col justify-between space-y-4 py-6"
                                >
                                    <nav class="-mx-3 space-y-1">
                                        <Link
                                            v-for="item in mainNavItems"
                                            :key="item.title"
                                            :class="[
                                                activeItemStyles(item.href),
                                                'flex items-center gap-2 rounded-md px-3 py-2 text-sm hover:bg-neutral-100 dark:hover:bg-neutral-800',
                                            ]"
                                            :href="item.href"
                                        >
                                            <component
                                                :is="item.icon"
                                                class="size-4"
                                            />
                                            {{ item.title }}
                                        </Link>
                                    </nav>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>

                    <!-- Logo -->
                    <Link
                        :href="dashboard()"
                        class="flex items-center gap-2 text-neutral-900 no-underline dark:text-white"
                    >
                        <AppLogo class="h-6" />
                    </Link>

                    <!-- Desktop Navigation -->
                    <div
                        class="hidden flex-1 items-center justify-between lg:flex"
                    >
                        <NavigationMenu class="ml-6">
                            <NavigationMenuList class="gap-1">
                                <NavigationMenuItem
                                    v-for="item in mainNavItems"
                                    :key="item.title"
                                >
                                    <Link
                                        :class="[
                                            navigationMenuTriggerStyle(),
                                            activeItemStyles(item.href),
                                        ]"
                                        :href="item.href"
                                    >
                                        {{ item.title }}
                                    </Link>
                                </NavigationMenuItem>
                            </NavigationMenuList>
                        </NavigationMenu>

                        <div class="flex items-center gap-2">
                            <!-- Language Toggle Button -->
                            <LanguageToggle />
                        </div>

                        <DropdownMenu>
                            <DropdownMenuTrigger :as-child="true">
                                <Button
                                    class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
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
                                            class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
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
                </div>
            </div>

            <!-- Breadcrumbs -->
            <div
                v-if="props.breadcrumbs.length > 0"
                class="flex w-full border-b border-sidebar-border/70"
            >
                <div
                    class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl"
                >
                    <Breadcrumbs :breadcrumbs="breadcrumbs" />
                </div>
            </div>
        </div>

        <!-- Content Area (THIS IS CRITICAL!) -->
        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>
