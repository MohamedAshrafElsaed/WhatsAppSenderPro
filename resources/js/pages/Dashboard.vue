<script lang="ts" setup>
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { MessageCircle, TrendingUp, Users, Zap } from 'lucide-vue-next';
import { computed } from 'vue';

// Use centralized translation
const { t } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('dashboard.title'),
        href: dashboard().url,
    },
];

const stats = computed(() => [
    {
        title: t('dashboard.stats.sessions'),
        value: '12',
        icon: Zap,
        description: t(
            'dashboard.stats.sessions_desc',
            'Active WhatsApp sessions',
        ),
    },
    {
        title: t('dashboard.stats.contacts'),
        value: '2,543',
        icon: Users,
        description: t(
            'dashboard.stats.contacts_desc',
            'Total contacts in database',
        ),
    },
    {
        title: t('dashboard.stats.messages'),
        value: '15,234',
        icon: MessageCircle,
        description: t(
            'dashboard.stats.messages_desc',
            'Messages sent this month',
        ),
    },
    {
        title: t('dashboard.stats.campaigns'),
        value: '8',
        icon: TrendingUp,
        description: t(
            'dashboard.stats.campaigns_desc',
            'Active campaigns running',
        ),
    },
]);
</script>

<template>
    <Head :title="t('dashboard.title')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6"
        >
            <!-- Welcome Section -->
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('dashboard.welcome') }}
                </h1>
                <p class="text-muted-foreground">
                    {{
                        t('dashboard.subtitle') ||
                        "Here's an overview of your WhatsApp campaigns"
                    }}
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card
                    v-for="stat in stats"
                    :key="stat.title"
                    class="transition-all hover:shadow-md"
                >
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <CardTitle class="text-sm font-medium">
                            {{ stat.title }}
                        </CardTitle>
                        <component
                            :is="stat.icon"
                            class="size-4 text-muted-foreground"
                        />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stat.value }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ stat.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Main Content Area -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
                <Card class="col-span-4">
                    <CardHeader>
                        <CardTitle>{{
                            t('dashboard.recent_activity') || 'Recent Activity'
                        }}</CardTitle>
                        <CardDescription>
                            {{
                                t('dashboard.recent_activity_desc') ||
                                'Your latest campaigns and messages'
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="relative min-h-[300px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                        >
                            <PlaceholderPattern />
                        </div>
                    </CardContent>
                </Card>

                <Card class="col-span-3">
                    <CardHeader>
                        <CardTitle>{{
                            t('dashboard.quick_stats') || 'Quick Stats'
                        }}</CardTitle>
                        <CardDescription>
                            {{
                                t('dashboard.quick_stats_desc') ||
                                'Overview of your performance'
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            class="relative min-h-[300px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                        >
                            <PlaceholderPattern />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Additional Content -->
            <Card>
                <CardHeader>
                    <CardTitle>{{
                        t('dashboard.campaign_overview') || 'Campaign Overview'
                    }}</CardTitle>
                    <CardDescription>
                        {{
                            t('dashboard.campaign_overview_desc') ||
                            'Detailed view of your campaigns'
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="relative min-h-[400px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <PlaceholderPattern />
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
