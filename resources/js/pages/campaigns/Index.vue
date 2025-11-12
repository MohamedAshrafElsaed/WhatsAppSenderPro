<script setup lang="ts">
import CampaignProgressBar from '@/components/CampaignProgressBar.vue';
import CampaignStatusBadge from '@/components/CampaignStatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { create, destroy, edit, pause, resume, results, send, show } from '@/routes/dashboard/campaigns';
import { index as dashboard } from '@/routes/dashboard';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    AlertCircle,
    Eye,
    MessageSquare,
    MoreVertical,
    Pause,
    Play,
    Plus,
    Search,
    Send as SendIcon,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Campaign {
    id: number;
    name: string;
    status: 'draft' | 'scheduled' | 'running' | 'paused' | 'completed' | 'failed';
    total_recipients: number;
    messages_sent: number;
    messages_delivered: number;
    messages_failed: number;
    progress_percentage: number;
    success_rate: number;
    created_at: string;
    started_at?: string;
    scheduled_at?: string;
    template?: { name: string };
}

interface UsageStats {
    used: number;
    limit: number | string;
    remaining: number | string;
}

interface Props {
    campaigns: {
        data: Campaign[];
        links: any;
        meta: any;
    };
    filters: {
        search?: string;
        status?: string;
        sort_by: string;
        sort_order: string;
    };
    usage: UsageStats;
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('dashboard.title', 'Dashboard'), href: dashboard().url },
    { title: t('campaigns.title', 'Campaigns') },
];

const searchQuery = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const confirmDelete = ref<Campaign | null>(null);

const usagePercentage = computed(() => {
    if (props.usage.limit === 'unlimited' || props.usage.limit === 0) return 0;
    return Math.round((props.usage.used / (props.usage.limit as number)) * 100);
});

const usageColor = computed(() => {
    const pct = usagePercentage.value;
    if (pct >= 90) return 'bg-red-500';
    if (pct >= 70) return 'bg-yellow-500';
    return 'bg-[#25D366]';
});

const formatLimit = (limit: number | string): string => {
    if (limit === 'unlimited' || limit === '∞') {
        return t('subscription.unlimited', 'Unlimited');
    }
    return limit.toString();
};

const applyFilters = () => {
    router.get(
        location.pathname,
        {
            search: searchQuery.value || undefined,
            status: statusFilter.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const handlePause = (campaign: Campaign) => {
    router.post(pause(campaign.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Toast will show from session flash
        },
    });
};

const handleResume = (campaign: Campaign) => {
    router.post(resume(campaign.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Toast will show from session flash
        },
    });
};

const handleSend = (campaign: Campaign) => {
    router.post(send(campaign.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Toast will show from session flash
        },
    });
};

const handleDelete = () => {
    if (!confirmDelete.value) return;

    router.delete(destroy(confirmDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmDelete.value = null;
        },
    });
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('campaigns.title', 'Campaigns')" />

        <div :class="isRTL() ? 'text-right' : 'text-left'" class="space-y-6">
            <!-- Header -->
            <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-center justify-between">
                <Heading
                    :description="t('campaigns.description', 'Create and manage your WhatsApp bulk messaging campaigns')"
                    :title="t('campaigns.title', 'Campaigns')"
                >
                    <template #icon>
                        <div class="flex items-center justify-center rounded-lg bg-[#25D366] p-2">
                            <MessageSquare class="h-5 w-5 text-white" />
                        </div>
                    </template>
                </Heading>

                <Button as-child>
                    <Link :href="create()">
                        <Plus class="mr-2 h-4 w-4" />
                        {{ t('campaigns.create', 'Create Campaign') }}
                    </Link>
                </Button>
            </div>

            <!-- Usage Warning -->
            <Alert v-if="usagePercentage >= 70" class="border-yellow-500 bg-yellow-50 dark:bg-yellow-950">
                <AlertCircle class="h-4 w-4 text-yellow-600" />
                <AlertDescription class="text-yellow-800 dark:text-yellow-200">
                    {{ t('campaigns.usage_warning', "You've used {{percentage}}% of your monthly message quota.", { percentage: usagePercentage }) }}
                    {{ t('campaigns.remaining_messages', '{{remaining}} messages remaining.', { remaining: usage.remaining }) }}
                </AlertDescription>
            </Alert>

            <!-- Filters -->
            <Card>
                <CardContent class="pt-6">
                    <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex gap-4">
                        <div class="relative flex-1">
                            <Search
                                :class="isRTL() ? 'right-3' : 'left-3'"
                                class="absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="searchQuery"
                                :class="isRTL() ? 'pr-10' : 'pl-10'"
                                :placeholder="t('campaigns.search', 'Search campaigns...')"
                                @keyup.enter="applyFilters"
                            />
                        </div>

                        <Select v-model="statusFilter" @update:model-value="applyFilters">
                            <SelectTrigger class="w-48">
                                <SelectValue :placeholder="t('campaigns.all_statuses', 'All Statuses')" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">{{ t('campaigns.all_statuses', 'All Statuses') }}</SelectItem>
                                <SelectItem value="draft">{{ t('campaigns.status.draft', 'Draft') }}</SelectItem>
                                <SelectItem value="scheduled">{{ t('campaigns.status.scheduled', 'Scheduled') }}</SelectItem>
                                <SelectItem value="running">{{ t('campaigns.status.running', 'Running') }}</SelectItem>
                                <SelectItem value="paused">{{ t('campaigns.status.paused', 'Paused') }}</SelectItem>
                                <SelectItem value="completed">{{ t('campaigns.status.completed', 'Completed') }}</SelectItem>
                                <SelectItem value="failed">{{ t('campaigns.status.failed', 'Failed') }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </CardContent>
            </Card>

            <!-- Campaigns List -->
            <div class="grid gap-4">
                <Card
                    v-for="campaign in campaigns.data"
                    :key="campaign.id"
                    class="transition-shadow hover:shadow-md"
                >
                    <CardHeader>
                        <div :class="isRTL() ? 'flex-row-reverse' : ''" class="flex items-start justify-between">
                            <div :class="isRTL() ? 'text-right' : 'text-left'" class="flex-1">
                                <CardTitle class="text-lg">{{ campaign.name }}</CardTitle>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <CampaignStatusBadge :status="campaign.status" />
                                    <Badge v-if="campaign.template" variant="outline">
                                        {{ campaign.template.name }}
                                    </Badge>
                                    <Badge variant="secondary">
                                        {{ campaign.total_recipients }} {{ t('campaigns.recipients', 'recipients') }}
                                    </Badge>
                                </div>
                            </div>

                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon">
                                        <MoreVertical class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent :align="isRTL() ? 'end' : 'start'">
                                    <DropdownMenuItem as-child>
                                        <Link :href="show(campaign.id)">
                                            <Eye class="mr-2 h-4 w-4" />
                                            {{ t('common.view', 'View') }}
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem v-if="campaign.status === 'draft'" as-child>
                                        <Link :href="edit(campaign.id)">
                                            {{ t('common.edit', 'Edit') }}
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="campaign.status === 'running'"
                                        @click="handlePause(campaign)"
                                    >
                                        <Pause class="mr-2 h-4 w-4" />
                                        {{ t('campaigns.actions.pause', 'Pause') }}
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="campaign.status === 'paused'"
                                        @click="handleResume(campaign)"
                                    >
                                        <Play class="mr-2 h-4 w-4" />
                                        {{ t('campaigns.actions.resume', 'Resume') }}
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        v-if="['draft', 'scheduled', 'paused'].includes(campaign.status)"
                                        @click="handleSend(campaign)"
                                    >
                                        <SendIcon class="mr-2 h-4 w-4" />
                                        {{ t('campaigns.actions.send', 'Send Now') }}
                                    </DropdownMenuItem>

                                    <DropdownMenuItem as-child>
                                        <Link :href="results(campaign.id)">
                                            {{ t('campaigns.actions.view_results', 'View Results') }}
                                        </Link>
                                    </DropdownMenuItem>

                                    <DropdownMenuItem
                                        class="text-red-600"
                                        @click="confirmDelete = campaign"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        {{ t('common.delete', 'Delete') }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-4">
                        <!-- Progress -->
                        <CampaignProgressBar
                            :sent="campaign.messages_sent"
                            :total="campaign.total_recipients"
                        />

                        <!-- Statistics -->
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div class="rounded-lg bg-muted p-3">
                                <p class="text-xs text-muted-foreground">
                                    {{ t('campaigns.stats.sent', 'Sent') }}
                                </p>
                                <p class="text-lg font-bold">{{ campaign.messages_sent }}</p>
                            </div>

                            <div class="rounded-lg border border-[#25D366]/20 bg-[#25D366]/10 p-3">
                                <p class="text-xs text-muted-foreground">
                                    {{ t('campaigns.stats.delivered', 'Delivered') }}
                                </p>
                                <p class="text-lg font-bold text-[#25D366]">
                                    {{ campaign.messages_delivered }}
                                </p>
                            </div>

                            <div class="rounded-lg border border-red-200 bg-red-50 p-3 dark:bg-red-950">
                                <p class="text-xs text-muted-foreground">
                                    {{ t('campaigns.stats.failed', 'Failed') }}
                                </p>
                                <p class="text-lg font-bold text-red-600">
                                    {{ campaign.messages_failed }}
                                </p>
                            </div>

                            <div class="rounded-lg bg-muted p-3">
                                <p class="text-xs text-muted-foreground">
                                    {{ t('campaigns.stats.success_rate', 'Success Rate') }}
                                </p>
                                <p class="text-lg font-bold">{{ campaign.success_rate }}%</p>
                            </div>
                        </div>

                        <!-- Dates -->
                        <div :class="isRTL() ? 'text-right' : 'text-left'" class="text-sm text-muted-foreground">
                            <span v-if="campaign.scheduled_at">
                                {{ t('campaigns.scheduled_for', 'Scheduled for:') }} {{ formatDate(campaign.scheduled_at) }}
                            </span>
                            <span v-else-if="campaign.started_at">
                                {{ t('campaigns.started_at', 'Started:') }} {{ formatDate(campaign.started_at) }}
                            </span>
                            <span v-else>
                                {{ t('campaigns.created_at', 'Created:') }} {{ formatDate(campaign.created_at) }}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Empty State -->
                <Card v-if="campaigns.data.length === 0" class="py-12">
                    <CardContent class="text-center">
                        <MessageSquare class="mx-auto h-12 w-12 text-muted-foreground" />
                        <h3 class="mt-4 text-lg font-semibold">
                            {{ t('campaigns.no_campaigns', 'No campaigns yet') }}
                        </h3>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ t('campaigns.create_first', 'Get started by creating your first campaign') }}
                        </p>
                        <Button as-child class="mt-4">
                            <Link :href="create()">
                                <Plus class="mr-2 h-4 w-4" />
                                {{ t('campaigns.create', 'Create Campaign') }}
                            </Link>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Pagination -->
            <div v-if="campaigns.meta.last_page > 1" class="flex justify-center">
                <div class="flex gap-2">
                    <Button
                        v-for="link in campaigns.links"
                        :key="link.label"
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        @click="router.get(link.url)"
                    >
                        {{ link.label }}
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog :open="!!confirmDelete" @update:open="(open) => !open && (confirmDelete = null)">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ t('campaigns.delete_confirm_title', 'Delete Campaign?') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('campaigns.delete_confirm_description', 'Are you sure you want to delete this campaign? This action cannot be undone.') }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="confirmDelete = null">
                        {{ t('common.cancel', 'Cancel') }}
                    </Button>
                    <Button variant="destructive" @click="handleDelete">
                        {{ t('common.delete', 'Delete') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
