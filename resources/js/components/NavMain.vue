<script lang="ts" setup>
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { toUrl, urlIsActive } from '@/lib/utils';
import type { NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    items: NavItem[];
}

defineProps<Props>();

const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');

const isActive = (href: string) => {
    return urlIsActive(toUrl(href), page.url);
};
</script>

<template>
    <SidebarGroup>
        <SidebarGroupContent>
            <SidebarMenu>
                <SidebarMenuItem v-for="item in items" :key="item.title">
                    <SidebarMenuButton
                        :is-active="isActive(item.href)"
                        :tooltip="item.title"
                        as-child
                    >
                        <Link :href="item.href" class="flex items-center gap-2">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarGroupContent>
    </SidebarGroup>
</template>
