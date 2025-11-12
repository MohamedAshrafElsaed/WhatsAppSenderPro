<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as dashboard } from '@/routes/dashboard';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    BarChart3,
    CheckCircle,
    Download,
    MessageSquare,
    TrendingDown,
    TrendingUp,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface OverviewStats {
    total_campaigns: number;
    total_messages: number;
    total_contacts: number;
    average_success_rate: number;
    active_campaigns: number;
    completed_campaigns: number;
}

interface CampaignPerformance {
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

interface MessageTrend {
    date: string;
    total: number;
    delivered: number;
    failed: number;
    success_rate: number;
}

interface ContactGrowth {
    date: string;
    count: number;
}

interface Props {
    overview: OverviewStats;
    campaignPerformance: CampaignPerformance[];
    messageTrends: MessageTrend[];
    contactGrowth: ContactGrowth[];
    dateRange: {
        start: string;
        end: string;
    };
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('dashboard.title', 'Dashboard'), href: dashboard().url },
    { title: t('reports.title', 'Reports & Analytics') },
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
        }
    );
};

const exportReport = (type: string) => {
    window.location.href = `/dashboard/reports/export?type=${type}&start_date=${startDate.value}&end_date=${endDate.value}`;
};

const formatNumber = (num: number): string => {
    return new Intl.NumberFormat().format(num);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('reports.title', 'Reports & Analytics')" />

        <div :class="isRTL() ? 'text-right' : 'text-left'" class="space-y-6">
            <!-- Header -->
            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-center justify-between">
                <Heading
                    :description="t('reports.description', 'Comprehensive analytics and insights for your WhatsApp campaigns')"
                    :title="t('reports.title', 'Reports & Analytics')"
                >
                    <template #icon>
                        <div class="flex items-center justify-center rounded-lg bg-[#25D366] p-2">
                            <BarChart3 class="h-5 w-5 text-white" />
                        </div>
                    </template>
                </Heading>
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

            <!-- Overview Stats -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('reports.total_campaigns', 'Total Campaigns') }}
                        </CardTitle>
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatNumber(overview.total_campaigns) }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ overview.active_campaigns }} {{ t('reports.active', 'active') }},
                            {{ overview.completed_campaigns }} {{ t('reports.completed', 'completed') }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('reports.total_messages', 'Total Messages') }}
                        </CardTitle>
                        <MessageSquare class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-[#25D366]">{{ formatNumber(overview.total_messages) }}</div>
                        <div class="flex items-center gap-1 text-xs text-muted-foreground">
                            <TrendingUp class="h-3 w-3 text-[#25D366]" />
                            <span>{{ t('reports.messages_sent', 'Messages sent') }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('reports.total_contacts', 'Total Contacts') }}
                        </CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatNumber(overview.total_contacts) }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('reports.in_database', 'In your database') }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('reports.average_success_rate', 'Avg Success Rate') }}
                        </CardTitle>
                        <CheckCircle class="h-4 w-4 text-[#25D366]" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-[#25D366]">{{ overview.average_success_rate }}%</div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('reports.delivery_success', 'Delivery success rate') }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Campaign Performance -->
            <Card>
                <CardHeader>
                    <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-center justify-between">
                        <CardTitle>{{ t('reports.campaign_performance', 'Campaign Performance') }}</CardTitle>
                        <Button variant="outline" size="sm" @click="exportReport('campaigns')">
                            <Download class="mr-2 h-4 w-4" />
                            {{ t('common.export', 'Export') }}
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="campaign in campaignPerformance"
                            :key="campaign.id"
                            class="rounded-lg border p-4"
                        >
                            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-center justify-between">
                                <div :class="isRTL() ? 'text-right' : 'text-left'" class="flex-1">
                                    <h4 class="font-medium">{{ campaign.name }}</h4>
                                    <div class="mt-1 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                        <span>{{ campaign.total_recipients }} {{ t('reports.recipients', 'recipients') }}</span>
                                        <span>{{ campaign.messages_delivered }} {{ t('reports.delivered', 'delivered') }}</span>
                                        <span>{{ campaign.messages_failed }} {{ t('reports.failed', 'failed') }}</span>
                                    </div>
                                </div>
                                <div :class="isRTL() ? 'text-left' : 'text-right'">
                                    <div class="text-lg font-bold text-[#25D366]">{{ campaign.success_rate }}%</div>
                                    <div class="text-xs text-muted-foreground">{{ t('reports.success', 'Success') }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-if="campaignPerformance.length === 0" class="py-8 text-center text-muted-foreground">
                            {{ t('reports.no_campaigns', 'No campaigns in this period') }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Message Trends -->
            <Card>
                <CardHeader>
                    <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-center justify-between">
                        <CardTitle>{{ t('reports.message_trends', 'Message Delivery Trends') }}</CardTitle>
                        <Button variant="outline" size="sm" @click="exportReport('messages')">
                            <Download class="mr-2 h-4 w-4" />
                            {{ t('common.export', 'Export') }}
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="trend in messageTrends.slice(0, 10)"
                            :key="trend.date"
                            class="flex items-center justify-between rounded-lg border p-3"
                        >
                            <span class="text-sm font-medium">{{ new Date(trend.date).toLocaleDateString() }}</span>
                            <div class="flex items-center gap-4 text-sm">
                                <span class="flex items-center gap-1 text-[#25D366]">
                                    <CheckCircle class="h-3 w-3" />
                                    {{ trend.delivered }}
                                </span>
                                <span class="flex items-center gap-1 text-red-600">
                                    <XCircle class="h-3 w-3" />
                                    {{ trend.failed }}
                                </span>
                                <span class="font-medium">{{ trend.success_rate }}%</span>
                            </div>
                        </div>

                        <div v-if="messageTrends.length === 0" class="py-8 text-center text-muted-foreground">
                            {{ t('reports.no_data', 'No data available for this period') }}
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Contact Growth -->
            <Card>
                <CardHeader>
                    <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-center justify-between">
                        <CardTitle>{{ t('reports.contact_growth', 'Contact Growth') }}</CardTitle>
                        <Button variant="outline" size="sm" @click="exportReport('contacts')">
                            <Download class="mr-2 h-4 w-4" />
                            {{ t('common.export', 'Export') }}
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div
                            v-for="growth in contactGrowth.slice(0, 10)"
                            :key="growth.date"
                            class="flex items-center justify-between rounded-lg border p-3"
                        >
                            <span class="text-sm font-medium">{{ new Date(growth.date).toLocaleDateString() }}</span>
                            <span class="flex items-center gap-2">
                                <TrendingUp class="h-4 w-4 text-[#25D366]" />
                                <span class="font-medium text-[#25D366]">+{{ growth.count }}</span>
                                <span class="text-xs text-muted-foreground">{{ t('reports.new_contacts', 'new contacts') }}</span>
                            </span>
                        </div>

                        <div v-if="contactGrowth.length === 0" class="py-8 text-center text-muted-foreground">
                            {{ t('reports.no_data', 'No data available for this period') }}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
