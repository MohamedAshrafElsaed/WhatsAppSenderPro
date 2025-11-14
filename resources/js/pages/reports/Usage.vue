<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as dashboard } from '@/routes/dashboard';
import { index as reportsIndex } from '@/routes/dashboard/reports';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import {
    BarChart3,
    MessageSquare,
    TrendingUp,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface UsageItem {
    type: string;
    used: number;
    limit: number | string;
    percentage: number;
}

interface UsageHistory {
    month: string;
    messages: number;
    contacts: number;
    templates: number;
}

interface CurrentUsage {
    messages_per_month: UsageItem;
    contacts_validation_per_month: UsageItem;
    connected_numbers: UsageItem;
    message_templates: UsageItem;
}

interface Props {
    currentUsage: CurrentUsage;
    usageHistory: UsageHistory[];
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('dashboard.title', 'Dashboard'), href: dashboard().url },
    { title: t('reports.title', 'Reports & Analytics'), href: reportsIndex().url },
    { title: t('reports.usage', 'Usage Reports') },
];

const getUsageColor = (percentage: number): string => {
    if (percentage >= 90) return 'text-red-600';
    if (percentage >= 70) return 'text-yellow-600';
    return 'text-green-600';
};

const getProgressColor = (percentage: number): string => {
    if (percentage >= 90) return 'bg-red-600';
    if (percentage >= 70) return 'bg-yellow-600';
    return 'bg-[#25D366]';
};

const formatNumber = (num: number | string): string => {
    if (num === 'unlimited' || num === '') return '';
    return new Intl.NumberFormat().format(Number(num));
};

const formatMonth = (monthStr: string): string => {
    const date = new Date(monthStr);
    return date.toLocaleDateString(undefined, { month: 'short', year: 'numeric' });
};

// Calculate total usage across all metrics
const overallUsagePercentage = computed(() => {
    const items = Object.values(props.currentUsage);
    const validItems = items.filter(item => typeof item.limit === 'number' && item.limit > 0);
    if (validItems.length === 0) return 0;
    const avgPercentage = validItems.reduce((sum, item) => sum + item.percentage, 0) / validItems.length;
    return Math.round(avgPercentage);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('reports.usage', 'Usage Reports')" />

        <div
            :class="isRTL() ? 'text-right' : 'text-left'"
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Heading
                    :description="t('reports.usage_description', 'Track your subscription usage and quota consumption')"
                    :title="t('reports.usage', 'Usage Reports')"
                />
            </div>

            <!-- Overall Usage Summary -->
            <Card class="border-t-4 border-t-[#25D366]">
                <CardHeader>
                    <CardTitle>{{ t('reports.overall_usage', 'Overall Usage') }}</CardTitle>
                    <CardDescription>
                        {{ t('reports.overall_usage_desc', 'Your total resource consumption this billing period') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-3xl font-bold" :class="getUsageColor(overallUsagePercentage)">
                                {{ overallUsagePercentage }}%
                            </span>
                            <Badge
                                :variant="overallUsagePercentage >= 90 ? 'destructive' : overallUsagePercentage >= 70 ? 'secondary' : 'default'"
                                class="text-sm"
                            >
                                {{
                                    overallUsagePercentage >= 90
                                        ? t('reports.usage_critical', 'Critical')
                                        : overallUsagePercentage >= 70
                                          ? t('reports.usage_warning', 'Warning')
                                          : t('reports.usage_healthy', 'Healthy')
                                }}
                            </Badge>
                        </div>
                        <Progress :model-value="overallUsagePercentage" :class="getProgressColor(overallUsagePercentage)" />
                    </div>
                </CardContent>
            </Card>

            <!-- Current Usage Details -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Messages Usage -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('reports.messages_usage', 'Messages') }}
                        </CardTitle>
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold">
                                    {{ formatNumber(currentUsage.messages_per_month.used) }}
                                </span>
                                <span class="text-sm text-muted-foreground">
                                    / {{ formatNumber(currentUsage.messages_per_month.limit) }}
                                </span>
                            </div>
                            <Progress
                                :model-value="currentUsage.messages_per_month.percentage"
                                :class="getProgressColor(currentUsage.messages_per_month.percentage)"
                            />
                            <div class="flex items-center justify-between text-xs">
                                <span
                                    :class="getUsageColor(currentUsage.messages_per_month.percentage)"
                                    class="font-medium"
                                >
                                    {{ currentUsage.messages_per_month.percentage }}% {{ t('common.used', 'used') }}
                                </span>
                                <span class="text-muted-foreground">
                                    {{
                                        currentUsage.messages_per_month.limit === 'unlimited'
                                            ? t('subscription.unlimited', 'Unlimited')
                                            : formatNumber(
                                                  Number(currentUsage.messages_per_month.limit) -
                                                      currentUsage.messages_per_month.used
                                              ) +
                                              ' ' +
                                              t('subscription.remaining', 'remaining')
                                    }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Contacts Validation Usage -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('reports.contacts_validation', 'Contact Validations') }}
                        </CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold">
                                    {{ formatNumber(currentUsage.contacts_validation_per_month.used) }}
                                </span>
                                <span class="text-sm text-muted-foreground">
                                    / {{ formatNumber(currentUsage.contacts_validation_per_month.limit) }}
                                </span>
                            </div>
                            <Progress
                                :model-value="currentUsage.contacts_validation_per_month.percentage"
                                :class="getProgressColor(currentUsage.contacts_validation_per_month.percentage)"
                            />
                            <div class="flex items-center justify-between text-xs">
                                <span
                                    :class="getUsageColor(currentUsage.contacts_validation_per_month.percentage)"
                                    class="font-medium"
                                >
                                    {{ currentUsage.contacts_validation_per_month.percentage }}% {{ t('common.used', 'used') }}
                                </span>
                                <span class="text-muted-foreground">
                                    {{
                                        currentUsage.contacts_validation_per_month.limit === 'unlimited'
                                            ? t('subscription.unlimited', 'Unlimited')
                                            : formatNumber(
                                                  Number(currentUsage.contacts_validation_per_month.limit) -
                                                      currentUsage.contacts_validation_per_month.used
                                              ) +
                                              ' ' +
                                              t('subscription.remaining', 'remaining')
                                    }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Connected Numbers Usage -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('subscription.connected_numbers', 'Connected Numbers') }}
                        </CardTitle>
                        <BarChart3 class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold">
                                    {{ formatNumber(currentUsage.connected_numbers.used) }}
                                </span>
                                <span class="text-sm text-muted-foreground">
                                    / {{ formatNumber(currentUsage.connected_numbers.limit) }}
                                </span>
                            </div>
                            <Progress
                                :model-value="currentUsage.connected_numbers.percentage"
                                :class="getProgressColor(currentUsage.connected_numbers.percentage)"
                            />
                            <div class="flex items-center justify-between text-xs">
                                <span
                                    :class="getUsageColor(currentUsage.connected_numbers.percentage)"
                                    class="font-medium"
                                >
                                    {{ currentUsage.connected_numbers.percentage }}% {{ t('common.used', 'used') }}
                                </span>
                                <span class="text-muted-foreground">
                                    {{
                                        currentUsage.connected_numbers.limit === 'unlimited'
                                            ? t('subscription.unlimited', 'Unlimited')
                                            : formatNumber(
                                                  Number(currentUsage.connected_numbers.limit) -
                                                      currentUsage.connected_numbers.used
                                              ) +
                                              ' ' +
                                              t('subscription.remaining', 'remaining')
                                    }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Templates Usage -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('subscription.templates', 'Templates') }}
                        </CardTitle>
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold">
                                    {{ formatNumber(currentUsage.message_templates.used) }}
                                </span>
                                <span class="text-sm text-muted-foreground">
                                    / {{ formatNumber(currentUsage.message_templates.limit) }}
                                </span>
                            </div>
                            <Progress
                                :model-value="currentUsage.message_templates.percentage"
                                :class="getProgressColor(currentUsage.message_templates.percentage)"
                            />
                            <div class="flex items-center justify-between text-xs">
                                <span
                                    :class="getUsageColor(currentUsage.message_templates.percentage)"
                                    class="font-medium"
                                >
                                    {{ currentUsage.message_templates.percentage }}% {{ t('common.used', 'used') }}
                                </span>
                                <span class="text-muted-foreground">
                                    {{
                                        currentUsage.message_templates.limit === 'unlimited'
                                            ? t('subscription.unlimited', 'Unlimited')
                                            : formatNumber(
                                                  Number(currentUsage.message_templates.limit) -
                                                      currentUsage.message_templates.used
                                              ) +
                                              ' ' +
                                              t('subscription.remaining', 'remaining')
                                    }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Usage History -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('reports.usage_history', 'Usage History') }}</CardTitle>
                    <CardDescription>
                        {{ t('reports.usage_history_desc', 'Your resource consumption over the last 6 months') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="history in usageHistory"
                            :key="history.month"
                            class="rounded-lg border p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="font-medium">{{ formatMonth(history.month) }}</h4>
                                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <MessageSquare class="h-3 w-3" />
                                            {{ formatNumber(history.messages) }} {{ t('reports.messages', 'messages') }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Users class="h-3 w-3" />
                                            {{ formatNumber(history.contacts) }} {{ t('reports.contacts', 'contacts') }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <BarChart3 class="h-3 w-3" />
                                            {{ formatNumber(history.templates) }} {{ t('reports.templates', 'templates') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="usageHistory.length === 0"
                            class="py-8 text-center text-muted-foreground"
                        >
                            {{ t('reports.no_usage_history', 'No usage history available yet') }}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
