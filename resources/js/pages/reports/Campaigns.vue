<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as dashboard } from '@/routes/dashboard';
import { index as reportsIndex } from '@/routes/dashboard/reports';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle,
    Clock,
    Download,
    MessageSquare,
    Pause,
    Play,
    Send,
    TrendingUp,
    XCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface CampaignStats {
    total: number;
    running: number;
    completed: number;
    paused: number;
    failed: number;
    draft: number;
    scheduled: number;
    total_messages_sent: number;
    total_recipients: number;
    average_success_rate: number;
}

interface TopCampaign {
    id: number;
    name: string;
    status: string;
    total_recipients: number;
    messages_sent: number;
    messages_delivered: number;
    messages_failed: number;
    success_rate: number;
    created_at: string;
}

interface CampaignsByStatus {
    status: string;
    count: number;
    percentage: number;
}

interface Props {
    stats: CampaignStats;
    topCampaigns: TopCampaign[];
    campaignsByStatus: CampaignsByStatus[];
    dateRange: {
        start: string;
        end: string;
    };
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('dashboard.title', 'Dashboard'), href: dashboard().url },
    { title: t('reports.title', 'Reports & Analytics'), href: reportsIndex().url },
    { title: t('reports.campaigns_report', 'Campaign Reports') },
];

const startDate = ref(props.dateRange.start);
const endDate = ref(props.dateRange.end);

const applyDateFilter = () => {
    router.get(
        location.pathname,
        {
            start_date: startDate.value,
            end_date: endDate.value,
        },
        {
            preserveState: true,
        },
    );
};

const exportReport = () => {
    window.location.href = `/dashboard/reports/export?type=campaigns&start_date=${startDate.value}&end_date=${endDate.value}`;
};

const formatNumber = (num: number): string => {
    return new Intl.NumberFormat().format(num);
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'running':
            return Play;
        case 'completed':
            return CheckCircle;
        case 'paused':
            return Pause;
        case 'failed':
            return XCircle;
        case 'scheduled':
            return Clock;
        case 'draft':
            return AlertCircle;
        default:
            return MessageSquare;
    }
};

const getStatusColor = (status: string): string => {
    switch (status) {
        case 'running':
            return 'text-blue-600';
        case 'completed':
            return 'text-green-600';
        case 'paused':
            return 'text-yellow-600';
        case 'failed':
            return 'text-red-600';
        case 'scheduled':
            return 'text-purple-600';
        case 'draft':
            return 'text-gray-600';
        default:
            return 'text-gray-600';
    }
};

const getStatusBadgeVariant = (status: string) => {
    switch (status) {
        case 'completed':
            return 'default';
        case 'running':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'outline';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('reports.campaigns_report', 'Campaign Reports')" />

        <div
            :class="isRTL() ? 'text-right' : 'text-left'"
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <Heading
                    :description="t('reports.campaigns_description', 'Track your campaign performance and delivery metrics')"
                    :title="t('reports.campaigns_report', 'Campaign Reports')"
                />

                <Button class="bg-[#25D366] hover:bg-[#128C7E]" @click="exportReport">
                    <Download :class="isRTL() ? 'ml-2' : 'mr-2'" class="h-4 w-4" />
                    {{ t('common.export', 'Export') }}
                </Button>
            </div>

            <!-- Date Range Filter -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('reports.date_range', 'Date Range') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex flex-wrap gap-4">
                        <div class="flex-1">
                            <Label>{{ t('reports.start_date', 'Start Date') }}</Label>
                            <Input v-model="startDate" type="date" />
                        </div>
                        <div class="flex-1">
                            <Label>{{ t('reports.end_date', 'End Date') }}</Label>
                            <Input v-model="endDate" type="date" />
                        </div>
                        <div class="flex items-end">
                            <Button @click="applyDateFilter">
                                {{ t('common.apply', 'Apply') }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Campaign Stats Overview -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Total Campaigns -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('campaigns.total_campaigns', 'Total Campaigns') }}
                        </CardTitle>
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-[#25D366]">
                            {{ formatNumber(stats.total) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('campaigns.in_this_period', 'In this period') }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Messages Sent -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('campaigns.messages_sent', 'Messages Sent') }}
                        </CardTitle>
                        <Send class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatNumber(stats.total_messages_sent) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('campaigns.to_recipients', 'To') }} {{ formatNumber(stats.total_recipients) }} {{ t('campaigns.recipients', 'recipients') }}
                        </p>
                    </CardContent>
                </Card>

                <!-- Average Success Rate -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('campaigns.avg_success_rate', 'Avg Success Rate') }}
                        </CardTitle>
                        <CheckCircle class="h-4 w-4 text-[#25D366]" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-[#25D366]">
                            {{ stats.average_success_rate }}%
                        </div>
                        <div class="flex items-center gap-1 text-xs text-muted-foreground">
                            <TrendingUp class="h-3 w-3 text-[#25D366]" />
                            <span>{{ t('campaigns.delivery_rate', 'Delivery rate') }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Completed Campaigns -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('campaigns.completed', 'Completed') }}
                        </CardTitle>
                        <CheckCircle class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">
                            {{ formatNumber(stats.completed) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ Math.round((stats.completed / stats.total) * 100) || 0 }}% {{ t('campaigns.of_total', 'of total') }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Campaign Status Distribution -->
            <Card class="border-t-4 border-t-[#25D366]">
                <CardHeader>
                    <CardTitle>{{ t('campaigns.status_distribution', 'Campaign Status Distribution') }}</CardTitle>
                    <CardDescription>
                        {{ t('campaigns.status_distribution_desc', 'Breakdown of campaigns by status') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="statusData in campaignsByStatus"
                            :key="statusData.status"
                            class="space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <component
                                        :is="getStatusIcon(statusData.status)"
                                        :class="getStatusColor(statusData.status)"
                                        class="h-4 w-4"
                                    />
                                    <span class="font-medium capitalize">{{ t(`campaigns.status.${statusData.status}`, statusData.status) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-muted-foreground">{{ formatNumber(statusData.count) }}</span>
                                    <span class="font-bold" :class="getStatusColor(statusData.status)">
                                        {{ statusData.percentage }}%
                                    </span>
                                </div>
                            </div>
                            <Progress :model-value="statusData.percentage" />
                        </div>

                        <div
                            v-if="campaignsByStatus.length === 0"
                            class="py-8 text-center text-muted-foreground"
                        >
                            {{ t('campaigns.no_campaigns', 'No campaigns in this period') }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Performing Campaigns -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('campaigns.top_performing', 'Top Performing Campaigns') }}</CardTitle>
                    <CardDescription>
                        {{ t('campaigns.top_performing_desc', 'Campaigns with the highest delivery success rates') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="(campaign, index) in topCampaigns"
                            :key="campaign.id"
                            class="rounded-lg border p-4 hover:shadow-md transition-shadow"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <Badge variant="secondary" class="text-xs">
                                            #{{ index + 1 }}
                                        </Badge>
                                        <h4 class="font-medium">{{ campaign.name }}</h4>
                                        <Badge :variant="getStatusBadgeVariant(campaign.status)">
                                            {{ t(`campaigns.status.${campaign.status}`, campaign.status) }}
                                        </Badge>
                                    </div>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-muted-foreground mt-3">
                                        <div class="flex items-center gap-1">
                                            <MessageSquare class="h-3 w-3" />
                                            <span>{{ formatNumber(campaign.total_recipients) }} {{ t('campaigns.recipients', 'recipients') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Send class="h-3 w-3 text-green-600" />
                                            <span>{{ formatNumber(campaign.messages_delivered) }} {{ t('campaigns.delivered', 'delivered') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <XCircle class="h-3 w-3 text-red-600" />
                                            <span>{{ formatNumber(campaign.messages_failed) }} {{ t('campaigns.failed', 'failed') }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 text-muted-foreground">
                                            <Clock class="h-3 w-3" />
                                            <span>{{ new Date(campaign.created_at).toLocaleDateString() }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-[#25D366]">
                                        {{ campaign.success_rate }}%
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ t('campaigns.success', 'success') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="topCampaigns.length === 0"
                            class="py-8 text-center text-muted-foreground"
                        >
                            {{ t('campaigns.no_campaigns', 'No campaigns in this period') }}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
