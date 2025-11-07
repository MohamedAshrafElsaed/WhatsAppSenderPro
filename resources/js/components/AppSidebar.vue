<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/composables/useTranslation';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    MessageSquare,
    Users,
    FileText,
    Settings,
    CreditCard
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const { t } = useTranslation();
const page = usePage();
const locale = computed(() => page.props.locale || 'en');

const mainNavItems: NavItem[] = [
    {
        title: t('nav.dashboard', 'Dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('nav.campaigns', 'Campaigns'),
        href: '/campaigns',
        icon: MessageSquare,
    },
    {
        title: t('nav.contacts', 'Contacts'),
        href: '/contacts',
        icon: Users,
    },
    {
        title: t('nav.templates', 'Templates'),
        href: '/templates',
        icon: FileText,
    },
    {
        title: t('nav.subscription', 'Subscription'),
        href: '/subscription',
        icon: CreditCard,
    },
    {
        title: t('nav.settings', 'Settings'),
        href: '/settings',
        icon: Settings,
    },
];

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
