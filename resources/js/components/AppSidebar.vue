<script lang="ts" setup>
import LanguageToggle from '@/components/LanguageToggle.vue';
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
import { index as dashboard } from '@/routes/dashboard';
import { index as campaignsIndex } from '@/routes/dashboard/campaigns';
import { index as contactsIndex } from '@/routes/dashboard/contacts';
import { index as reportsIndex } from '@/routes/dashboard/reports';
import { edit as settingsProfile } from '@/routes/dashboard/settings/profile';
import { index as templatesIndex } from '@/routes/dashboard/templates';
import { index as subscriptionIndex } from '@/routes/subscription';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    CreditCard,
    FileText,
    LayoutGrid,
    MessageSquare,
    Settings,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

interface Props {
    side?: 'left' | 'right';
}

withDefaults(defineProps<Props>(), {
    side: 'left',
});

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
        href: campaignsIndex(),
        icon: MessageSquare,
    },
    {
        title: t('nav.contacts', 'Contacts'),
        href: contactsIndex(),
        icon: Users,
    },
    {
        title: t('nav.templates', 'Templates'),
        href: templatesIndex(),
        icon: FileText,
    },
    {
        title: t('nav.reports', 'Reports'),
        href: reportsIndex(),
        icon: BarChart3,
    },
    {
        title: t('nav.subscription', 'Subscription'),
        href: subscriptionIndex(),
        icon: CreditCard,
    },
    {
        title: t('nav.settings', 'Settings'),
        href: settingsProfile(),
        icon: Settings,
    },
];

const footerNavItems: NavItem[] = [];
</script>
<template>
    <Sidebar :side="side" collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child size="lg">
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
            <!-- Language Toggle -->
            <div class="px-3 py-2">
                <LanguageToggle />
            </div>

            <NavFooter v-if="footerNavItems.length" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
