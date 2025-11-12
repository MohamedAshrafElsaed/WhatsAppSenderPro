<script setup lang="ts">
import CampaignProgressBar from '@/components/CampaignProgressBar.vue';
import CampaignStatusBadge from '@/components/CampaignStatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index, show, export as exportResults } from '@/routes/dashboard/campaigns';
import { index as dashboard } from '@/routes/dashboard';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle,
    Clock,
    Download,
    FileText,
    MessageSquare,
    RefreshCw,
    Search,
    TrendingDown,
    TrendingUp,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Contact {
    id: number;
    first_name: string;
    last_name: string | null;
    phone_number: string;
}

interface CampaignRecipient {
    id: number;
    status: string;
    sent_at: string | null;
    delivered_at: string | null;
    error_message: string | null;
    message_id: string | null;
    contact: Contact;
}

interface Campaign {
    id: number;
    name: string;
    status: string;
    message_type: string;
    created_at: string;
}

interface Stats {
    total: number;
    sent: number;
    delivered: number;
    failed: number;
    pending: number;
    success_rate: number;
    failure_rate: number;
    progress_percentage: number;
}

interface Props {
    campaign: Campaign;
    recipients: {
        data: CampaignRecipient[];
        links: any;
        meta: any;
    };
    stats: Stats;
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('dashboard.title', 'Dashboard'), href: dashboard().url },
    { title: t('campaigns.title', 'Campaigns'), href: index().url },
    { title: props.campaign.name, href: show(props.campaign.id).url },
    { title: t('campaigns.results', 'Results') },
];

const searchQuery = ref('');

const filteredRecipients = computed(() => {
    if (!searchQuery.value) return props.recipients.data;

    const query = searchQuery.value.toLowerCase();
    return props.recipients.data.filter((r) => {
        const name = `${r.contact.first_name} ${r.contact.last_name || ''}`.toLowerCase();
        const phone = r.contact.phone_number.toLowerCase();
        return name.includes(query) || phone.includes(query);
    });
});

const handleExport = () => {
    window.location.href = exportResults(props.campaign.id).url;
};

const handleRefresh = () => {
    router.reload({ only: ['recipients', 'stats'] });
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusBadgeClass = (status: string) => {
    const classes: Record<string, string> = {
        pending: 'bg-gray-500',
        sent: 'bg-blue-500',
        delivered: 'bg-[#25D366]',
        failed: 'bg-red-500',
    };
    return classes[status] || 'bg-gray-500';
};

const getStatusIcon = (status: string) => {
    const icons: Record<string, any> = {
        pending: Clock,
        sent: MessageSquare,
        delivered: CheckCircle,
        failed: XCircle,
    };
    return icons[status] || Clock;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${campaign.name} - ${t('campaigns.results', 'Results')}`" />

        <div :class="isRTL() ? 'text-right' : 'text-left'" class="space-y-6">
            <!-- Header -->
            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-center justify-between">
                <Heading
                    :title="t('campaigns.results_title', 'Campaign Results')"
                    :description="campaign.name"
                >
                    <template #icon>
                        <div class="flex items-center justify-center rounded-lg bg-[#25D366] p-2">
                            <FileText class="h-5 w-5 text-white" />
                        </div>
                    </template>
                </Heading>

                <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex gap-2">
                    <Button variant="outline" @click="handleRefresh">
                        <RefreshCw class="mr-2 h-4 w-4" />
                        {{ t('campaigns.refresh', 'Refresh') }}
                    </Button>

                    <Button variant="outline" @click="handleExport">
                        <Download class="mr-2 h-4 w-4" />
                        {{ t('campaigns.export', 'Export CSV') }}
                    </Button>

                    <Button variant="outline" as-child>
                        <Link :href="show(campaign.id)">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            {{ t('campaigns.back_to_campaign', 'Back to Campaign') }}
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('campaigns.stats.total_recipients', 'Total Recipients') }}
                        </CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('campaigns.contacts_in_campaign', 'Contacts in this campaign') }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('campaigns.stats.delivered', 'Delivered') }}
                        </CardTitle>
                        <CheckCircle class="h-4 w-4 text-[#25D366]" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-[#25D366]">{{ stats.delivered }}</div>
                        <div class="flex items-center gap-1 text-xs text-muted-foreground">
                            <TrendingUp class="h-3 w-3 text-[#25D366]" />
                            <span>{{ stats.success_rate }}% {{ t('campaigns.success_rate', 'success rate') }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('campaigns.stats.failed', 'Failed') }}
                        </CardTitle>
                        <XCircle class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ stats.failed }}</div>
                        <div class="flex items-center gap-1 text-xs text-muted-foreground">
                            <TrendingDown class="h-3 w-3 text-red-600" />
                            <span>{{ stats.failure_rate }}% {{ t('campaigns.failure_rate', 'failure rate') }}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardTitle class="text-sm font-medium">
                            {{ t('campaigns.stats.pending', 'Pending') }}
                        </CardTitle>
                        <Clock class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.pending }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ t('campaigns.awaiting_delivery', 'Awaiting delivery') }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Progress Bar -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('campaigns.delivery_progress', 'Delivery Progress') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <CampaignProgressBar
                        :sent="stats.sent"
                        :total="stats.total"
                    />
                </CardContent>
            </Card>

            <!-- Recipients Table -->
            <Card>
                <CardHeader>
                    <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-center justify-between">
                        <CardTitle>{{ t('campaigns.delivery_details', 'Delivery Details') }}</CardTitle>

                        <div class="relative w-64">
                            <Search
                                :class="isRTL() ? 'right-3' : 'left-3'"
                                class="absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="searchQuery"
                                :class="isRTL() ? 'pr-10' : 'pl-10'"
                                :placeholder="t('campaigns.search_recipients', 'Search recipients...')"
                            />
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead :class="isRTL() ? 'text-right' : 'text-left'">
                                        {{ t('campaigns.recipient', 'Recipient') }}
                                    </TableHead>
                                    <TableHead :class="isRTL() ? 'text-right' : 'text-left'">
                                        {{ t('campaigns.phone', 'Phone') }}
                                    </TableHead>
                                    <TableHead :class="isRTL() ? 'text-right' : 'text-left'">
                                        {{ t('campaigns.status', 'Status') }}
                                    </TableHead>
                                    <TableHead :class="isRTL() ? 'text-right' : 'text-left'">
                                        {{ t('campaigns.sent_at', 'Sent At') }}
                                    </TableHead>
                                    <TableHead :class="isRTL() ? 'text-right' : 'text-left'">
                                        {{ t('campaigns.delivered_at', 'Delivered At') }}
                                    </TableHead>
                                    <TableHead :class="isRTL() ? 'text-right' : 'text-left'">
                                        {{ t('campaigns.error', 'Error') }}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="recipient in filteredRecipients" :key="recipient.id">
                                    <TableCell :class="isRTL() ? 'text-right' : 'text-left'" class="font-medium">
                                        {{ recipient.contact.first_name }} {{ recipient.contact.last_name }}
                                    </TableCell>
                                    <TableCell :class="isRTL() ? 'text-right' : 'text-left'">
                                        {{ recipient.contact.phone_number }}
                                    </TableCell>
                                    <TableCell :class="isRTL() ? 'text-right' : 'text-left'">
                                        <Badge :class="getStatusBadgeClass(recipient.status)">
                                            <component :is="getStatusIcon(recipient.status)" class="mr-1 h-3 w-3" />
                                            {{ t(`campaigns.status_${recipient.status}`, recipient.status) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell :class="isRTL() ? 'text-right' : 'text-left'" class="text-sm text-muted-foreground">
                                        {{ recipient.sent_at ? formatDate(recipient.sent_at) : '-' }}
                                    </TableCell>
                                    <TableCell :class="isRTL() ? 'text-right' : 'text-left'" class="text-sm text-muted-foreground">
                                        {{ recipient.delivered_at ? formatDate(recipient.delivered_at) : '-' }}
                                    </TableCell>
                                    <TableCell :class="isRTL() ? 'text-right' : 'text-left'" class="max-w-xs truncate text-sm text-red-600">
                                        {{ recipient.error_message || '-' }}
                                    </TableCell>
                                </TableRow>

                                <TableRow v-if="filteredRecipients.length === 0">
                                    <TableCell colspan="6" class="text-center text-muted-foreground">
                                        {{ t('campaigns.no_results', 'No results found') }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="recipients.meta.last_page > 1" class="mt-4 flex justify-center">
                        <div class="flex gap-2">
                            <Button
                                v-for="link in recipients.links"
                                :key="link.label"
                                :disabled="!link.url"
                                :variant="link.active ? 'default' : 'outline'"
                                size="sm"
                                @click="link.url && router.get(link.url)"
                            >
                                {{ link.label }}
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
