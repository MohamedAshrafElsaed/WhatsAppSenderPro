<script lang="ts" setup>
import CampaignStatusBadge from '@/components/CampaignStatusBadge.vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { index as dashboard } from '@/routes/dashboard';
import {
    create,
    destroy,
    edit,
    pause,
    results,
    resume,
    send,
    show,
} from '@/routes/dashboard/campaigns';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
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
    status:
        | 'draft'
        | 'scheduled'
        | 'running'
        | 'paused'
        | 'completed'
        | 'failed';
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
const statusFilter = ref(props.filters.status || 'all');
const confirmDelete = ref<Campaign | null>(null);
const selectedCampaigns = ref<number[]>([]);

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

const applyFilters = () => {
    router.get(
        location.pathname,
        {
            search: searchQuery.value || undefined,
            status:
                statusFilter.value === 'all' ? undefined : statusFilter.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    searchQuery.value = '';
    statusFilter.value = 'all';
    applyFilters();
};

const toggleSelectAll = () => {
    if (selectedCampaigns.value.length === props.campaigns.data.length) {
        selectedCampaigns.value = [];
    } else {
        selectedCampaigns.value = props.campaigns.data.map((c) => c.id);
    }
};

const toggleSelect = (campaignId: number) => {
    const index = selectedCampaigns.value.indexOf(campaignId);
    if (index > -1) {
        selectedCampaigns.value.splice(index, 1);
    } else {
        selectedCampaigns.value.push(campaignId);
    }
};

const isSelected = (campaignId: number) =>
    selectedCampaigns.value.includes(campaignId);
const allSelected = computed(
    () =>
        props.campaigns.data.length > 0 &&
        selectedCampaigns.value.length === props.campaigns.data.length,
);

const handlePause = (campaign: Campaign) => {
    router.post(
        pause(campaign.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const handleResume = (campaign: Campaign) => {
    router.post(
        resume(campaign.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const handleSend = (campaign: Campaign) => {
    router.post(
        send(campaign.id),
        {},
        {
            preserveScroll: true,
        },
    );
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
    });
};

const formatDateTime = (date: string) => {
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

        <div
            :class="isRTL() ? 'text-right' : 'text-left'"
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
        >
            <!-- Header -->
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <Heading
                    :description="
                        t(
                            'campaigns.description',
                            'Create and manage your WhatsApp bulk messaging campaigns',
                        )
                    "
                    :title="t('campaigns.title', 'Campaigns')"
                />
                <Button as-child class="bg-[#25D366] hover:bg-[#128C7E]">
                    <Link :href="create()">
                        <Plus
                            :class="isRTL() ? 'ml-2' : 'mr-2'"
                            class="h-4 w-4"
                        />
                        {{ t('campaigns.create', 'Create Campaign') }}
                    </Link>
                </Button>
            </div>

            <!-- Usage Warning -->
            <Alert
                v-if="usagePercentage >= 70"
                class="border-yellow-500 bg-yellow-50 dark:bg-yellow-950"
            >
                <AlertCircle class="h-4 w-4 text-yellow-600" />
                <AlertDescription class="text-yellow-800 dark:text-yellow-200">
                    <span>{{
                        t(
                            'campaigns.usage_warning',
                            `You have used ${usagePercentage}% of your monthly message quota.`,
                        )
                    }}</span>
                    <span class="ml-1">{{
                        t(
                            'campaigns.remaining_messages',
                            `${usage.remaining} messages remaining.`,
                        )
                    }}</span>
                </AlertDescription>
            </Alert>

            <!-- Filters -->
            <div class="flex flex-col gap-4 lg:flex-row">
                <!-- Search -->
                <div class="relative flex-1">
                    <Search
                        :class="isRTL() ? 'right-3' : 'left-3'"
                        class="absolute top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="searchQuery"
                        :class="isRTL() ? 'pr-10' : 'pl-10'"
                        :placeholder="
                            t('campaigns.search', 'Search campaigns...')
                        "
                        class="focus-visible:ring-[#25D366]"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <!-- Status Filter -->
                <div class="flex flex-wrap gap-2">
                    <Select
                        v-model="statusFilter"
                        @update:model-value="applyFilters"
                    >
                        <SelectTrigger class="w-[180px]">
                            <SelectValue
                                :placeholder="
                                    t('campaigns.all_statuses', 'All Statuses')
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">{{
                                t('campaigns.all_statuses', 'All Statuses')
                            }}</SelectItem>
                            <SelectItem value="draft">{{
                                t('campaigns.status.draft', 'Draft')
                            }}</SelectItem>
                            <SelectItem value="scheduled"
                                >{{
                                    t('campaigns.status.scheduled', 'Scheduled')
                                }}
                            </SelectItem>
                            <SelectItem value="running">{{
                                t('campaigns.status.running', 'Running')
                            }}</SelectItem>
                            <SelectItem value="paused">{{
                                t('campaigns.status.paused', 'Paused')
                            }}</SelectItem>
                            <SelectItem value="completed"
                                >{{
                                    t('campaigns.status.completed', 'Completed')
                                }}
                            </SelectItem>
                            <SelectItem value="failed">{{
                                t('campaigns.status.failed', 'Failed')
                            }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button size="sm" variant="ghost" @click="clearFilters">
                        {{ t('common.clear', 'Clear') }}
                    </Button>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div
                v-if="selectedCampaigns.length > 0"
                class="flex items-center justify-between rounded-lg border border-[#25D366]/20 bg-[#25D366]/10 p-4"
            >
                <span class="text-sm font-medium">
                    {{
                        t(
                            'campaigns.selected',
                            `${selectedCampaigns.length} campaigns selected`,
                        )
                    }}
                </span>
                <div class="flex gap-2">
                    <Button size="sm" variant="destructive">
                        {{ t('campaigns.delete_selected', 'Delete Selected') }}
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="w-12"
                            >
                                <input
                                    :checked="allSelected"
                                    class="rounded border-gray-300 text-[#25D366] focus:ring-[#25D366]"
                                    type="checkbox"
                                    @change="toggleSelectAll"
                                />
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{ t('campaigns.fields.name', 'Name') }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{ t('campaigns.fields.status', 'Status') }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{
                                    t(
                                        'campaigns.fields.recipients',
                                        'Recipients',
                                    )
                                }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{ t('campaigns.fields.progress', 'Progress') }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{
                                    t(
                                        'campaigns.fields.success_rate',
                                        'Success Rate',
                                    )
                                }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{ t('campaigns.fields.date', 'Date') }}
                            </TableHead>
                            <TableHead
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                {{ t('common.actions', 'Actions') }}
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <!-- Empty State -->
                        <TableRow v-if="campaigns.data.length === 0">
                            <TableCell class="py-12 text-center" colspan="8">
                                <div class="flex flex-col items-center gap-2">
                                    <MessageSquare
                                        class="h-12 w-12 text-muted-foreground"
                                    />
                                    <h3 class="text-lg font-semibold">
                                        {{
                                            t(
                                                'campaigns.no_campaigns',
                                                'No campaigns yet',
                                            )
                                        }}
                                    </h3>
                                    <p class="text-sm text-muted-foreground">
                                        {{
                                            t(
                                                'campaigns.create_first',
                                                'Get started by creating your first campaign',
                                            )
                                        }}
                                    </p>
                                    <Button
                                        as-child
                                        class="mt-4 bg-[#25D366] hover:bg-[#128C7E]"
                                    >
                                        <Link :href="create()">
                                            <Plus
                                                :class="
                                                    isRTL() ? 'ml-2' : 'mr-2'
                                                "
                                                class="h-4 w-4"
                                            />
                                            {{
                                                t(
                                                    'campaigns.create',
                                                    'Create Campaign',
                                                )
                                            }}
                                        </Link>
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Campaign Rows -->
                        <TableRow
                            v-for="campaign in campaigns.data"
                            :key="campaign.id"
                        >
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                <input
                                    :checked="isSelected(campaign.id)"
                                    class="rounded border-gray-300 text-[#25D366] focus:ring-[#25D366]"
                                    type="checkbox"
                                    @change="toggleSelect(campaign.id)"
                                />
                            </TableCell>
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="font-medium"
                            >
                                <div>
                                    {{ campaign.name }}
                                    <Badge
                                        v-if="campaign.template"
                                        class="ml-2"
                                        variant="outline"
                                    >
                                        {{ campaign.template.name }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                <CampaignStatusBadge
                                    :status="campaign.status"
                                />
                            </TableCell>
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                <div class="flex items-center gap-2">
                                    <Badge variant="secondary">
                                        {{ campaign.total_recipients }}
                                    </Badge>
                                </div>
                            </TableCell>
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                <div class="flex items-center gap-2">
                                    <Progress
                                        :class="[
                                            campaign.progress_percentage === 100
                                                ? '[&>*]:bg-[#25D366]'
                                                : campaign.progress_percentage >
                                                    0
                                                  ? '[&>*]:bg-yellow-500'
                                                  : '[&>*]:bg-gray-300',
                                        ]"
                                        :value="campaign.progress_percentage"
                                        class="h-2 w-24"
                                    />
                                    <span class="text-sm text-muted-foreground">
                                        {{ campaign.progress_percentage }}%
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                <div class="flex flex-col gap-1">
                                    <Badge
                                        :class="[
                                            campaign.success_rate >= 80
                                                ? 'border-[#25D366]/20 bg-[#25D366]/10 text-[#25D366]'
                                                : campaign.success_rate >= 60
                                                  ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
                                                  : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        ]"
                                        variant="outline"
                                    >
                                        {{ campaign.success_rate }}%
                                    </Badge>
                                    <div class="text-xs text-muted-foreground">
                                        <span class="text-[#25D366]">{{
                                            campaign.messages_delivered
                                        }}</span>
                                        /
                                        <span class="text-red-500">{{
                                            campaign.messages_failed
                                        }}</span>
                                    </div>
                                </div>
                            </TableCell>
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                                class="text-sm text-muted-foreground"
                            >
                                <div v-if="campaign.scheduled_at">
                                    {{
                                        t(
                                            'campaigns.scheduled_for',
                                            'Scheduled',
                                        )
                                    }}<br />
                                    {{ formatDateTime(campaign.scheduled_at) }}
                                </div>
                                <div v-else-if="campaign.started_at">
                                    {{ t('campaigns.started_at', 'Started')
                                    }}<br />
                                    {{ formatDateTime(campaign.started_at) }}
                                </div>
                                <div v-else>
                                    {{ t('campaigns.created_at', 'Created')
                                    }}<br />
                                    {{ formatDate(campaign.created_at) }}
                                </div>
                            </TableCell>
                            <TableCell
                                :class="isRTL() ? 'text-right' : 'text-left'"
                            >
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button size="sm" variant="ghost">
                                            <MoreVertical class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        :align="isRTL() ? 'start' : 'end'"
                                    >
                                        <DropdownMenuItem as-child>
                                            <Link :href="show(campaign.id)">
                                                <Eye
                                                    :class="
                                                        isRTL()
                                                            ? 'ml-2'
                                                            : 'mr-2'
                                                    "
                                                    class="h-4 w-4"
                                                />
                                                {{ t('common.view', 'View') }}
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="campaign.status === 'draft'"
                                            as-child
                                        >
                                            <Link :href="edit(campaign.id)">
                                                {{ t('common.edit', 'Edit') }}
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="campaign.status === 'running'"
                                            @click="handlePause(campaign)"
                                        >
                                            <Pause
                                                :class="
                                                    isRTL() ? 'ml-2' : 'mr-2'
                                                "
                                                class="h-4 w-4"
                                            />
                                            {{
                                                t(
                                                    'campaigns.actions.pause',
                                                    'Pause',
                                                )
                                            }}
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="campaign.status === 'paused'"
                                            @click="handleResume(campaign)"
                                        >
                                            <Play
                                                :class="
                                                    isRTL() ? 'ml-2' : 'mr-2'
                                                "
                                                class="h-4 w-4"
                                            />
                                            {{
                                                t(
                                                    'campaigns.actions.resume',
                                                    'Resume',
                                                )
                                            }}
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            v-if="
                                                [
                                                    'draft',
                                                    'scheduled',
                                                    'paused',
                                                ].includes(campaign.status)
                                            "
                                            @click="handleSend(campaign)"
                                        >
                                            <SendIcon
                                                :class="
                                                    isRTL() ? 'ml-2' : 'mr-2'
                                                "
                                                class="h-4 w-4"
                                            />
                                            {{
                                                t(
                                                    'campaigns.actions.send',
                                                    'Send Now',
                                                )
                                            }}
                                        </DropdownMenuItem>

                                        <DropdownMenuItem as-child>
                                            <Link :href="results(campaign.id)">
                                                {{
                                                    t(
                                                        'campaigns.actions.view_results',
                                                        'View Results',
                                                    )
                                                }}
                                            </Link>
                                        </DropdownMenuItem>

                                        <DropdownMenuItem
                                            class="text-destructive"
                                            @click="confirmDelete = campaign"
                                        >
                                            <Trash2
                                                :class="
                                                    isRTL() ? 'ml-2' : 'mr-2'
                                                "
                                                class="h-4 w-4"
                                            />
                                            {{ t('common.delete', 'Delete') }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <!-- Pagination -->
            <div
                v-if="campaigns.meta && campaigns.meta.last_page > 1"
                class="flex justify-center"
            >
                <div class="flex gap-2">
                    <Button
                        v-for="link in campaigns.links"
                        :key="link.label"
                        :class="
                            link.active ? 'bg-[#25D366] hover:bg-[#128C7E]' : ''
                        "
                        :disabled="!link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        @click="link.url && router.get(link.url)"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            :open="!!confirmDelete"
            @update:open="(open) => !open && (confirmDelete = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        t('campaigns.delete_confirm_title', 'Delete Campaign?')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            t(
                                'campaigns.delete_confirm_description',
                                'Are you sure you want to delete this campaign? This action cannot be undone.',
                            )
                        }}
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
