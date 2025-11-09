<script lang="ts" setup>
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
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
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import { useTranslation } from '@/composables/useTranslation';
import { toUrl, urlIsActive } from '@/lib/utils';
import { index as dashboard } from '@/routes/dashboard';
import type { BreadcrumbItem, NavItem } from '@/types';
import { InertiaLinkProps, Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, Menu, Search } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { t } = useTranslation();
const page = usePage();
const auth = computed(() => page.props.auth);
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');

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

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard', 'Dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
]);

const rightNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <div>
        <div class="border-b border-sidebar-border/80">
            <div
                :class="isRTL ? 'flex-row-reverse' : ''"
                class="mx-auto flex h-16 items-center px-4 md:max-w-7xl"
            >
                <!-- Mobile Menu -->
                <div :class="isRTL ? 'mr-auto ml-0' : 'mr-2'" class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button class="h-9 w-9" size="icon" variant="ghost">
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent
                            :side="isRTL ? 'right' : 'left'"
                            class="w-[300px] p-6"
                        >
                            <SheetTitle class="sr-only">
                                {{
                                    t('nav.navigation_menu', 'Navigation Menu')
                                }}
                            </SheetTitle>
                            <SheetHeader
                                :class="isRTL ? 'text-right' : 'text-left'"
                                class="flex justify-start"
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
                                            isRTL ? 'flex-row-reverse' : '',
                                        ]"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                    >
                                        <component
                                            :is="item.icon"
                                            v-if="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :class="
                                            isRTL
                                                ? 'flex-row-reverse space-x-reverse'
                                                : ''
                                        "
                                        :href="toUrl(item.href)"
                                        class="flex items-center space-x-2 text-sm font-medium"
                                        rel="noopener noreferrer"
                                        target="_blank"
                                    >
                                        <component
                                            :is="item.icon"
                                            v-if="item.icon"
                                            class="h-5 w-5"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link
                    :class="isRTL ? 'ml-2' : 'mr-2'"
                    :href="dashboard()"
                    class="flex items-center gap-x-2"
                >
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu
                        :class="isRTL ? 'mr-auto' : 'ml-auto'"
                        class="flex h-full items-stretch"
                    >
                        <NavigationMenuList
                            :class="
                                isRTL
                                    ? 'space-x-2 space-x-reverse'
                                    : 'space-x-2'
                            "
                            class="flex h-full items-stretch"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        activeItemStyles(item.href),
                                        'h-9 cursor-pointer px-3',
                                        isRTL ? 'flex-row-reverse' : '',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        :is="item.icon"
                                        v-if="item.icon"
                                        :class="isRTL ? 'ml-2' : 'mr-2'"
                                        class="h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentRoute(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                ></div>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div
                    :class="[
                        isRTL
                            ? 'mr-auto space-x-2 space-x-reverse'
                            : 'ml-auto space-x-2',
                    ]"
                    class="flex items-center"
                >
                    <div class="relative flex items-center space-x-1">
                        <Button
                            class="group h-9 w-9 cursor-pointer"
                            size="icon"
                            variant="ghost"
                        >
                            <Search
                                class="size-5 opacity-80 group-hover:opacity-100"
                            />
                        </Button>

                        <div
                            :class="
                                isRTL
                                    ? 'space-x-1 space-x-reverse'
                                    : 'space-x-1'
                            "
                            class="hidden lg:flex"
                        >
                            <template
                                v-for="item in rightNavItems"
                                :key="item.title"
                            >
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Button
                                                as-child
                                                class="group h-9 w-9 cursor-pointer"
                                                size="icon"
                                                variant="ghost"
                                            >
                                                <a
                                                    :href="toUrl(item.href)"
                                                    rel="noopener noreferrer"
                                                    target="_blank"
                                                >
                                                    <span class="sr-only">{{
                                                        item.title
                                                    }}</span>
                                                    <component
                                                        :is="item.icon"
                                                        class="size-5 opacity-80 group-hover:opacity-100"
                                                    />
                                                </a>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ item.title }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                        </div>
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
                                        v-if="auth.user.avatar"
                                        :alt="auth.user.name"
                                        :src="auth.user.avatar"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-[#25D366] font-semibold text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
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

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                :class="isRTL ? 'justify-end' : 'justify-start'"
                class="mx-auto flex h-12 w-full items-center px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
