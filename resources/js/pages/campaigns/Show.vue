<script lang="ts" setup>
import CampaignProgressBar from '@/components/CampaignProgressBar.vue';
import CampaignStatusBadge from '@/components/CampaignStatusBadge.vue';
import Heading from '@/components/Heading.vue';
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
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as dashboard } from '@/routes/dashboard';
import {
    destroy,
    edit,
    index,
    pause,
    results,
    resume,
    send,
} from '@/routes/dashboard/campaigns';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle,
    Edit2,
    Eye,
    MessageSquare,
    Pause as PauseIcon,
    Play,
    Send as SendIcon,
    Trash2,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Contact {
    id: number;
    first_name: string;
    last_name: string | null;
    phone_number: string;
}

interface CampaignRecipient {
    contact: Contact;
    status: string;
}

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
    message_type: string;
    message_content: string;
    message_caption: string | null;
    media_url: string | null;
    total_recipients: number;
    messages_sent: number;
    messages_delivered: number;
    messages_failed: number;
    progress_percentage: number;
    success_rate: number;
    created_at: string;
    started_at: string | null;
    scheduled_at: string | null;
    completed_at: string | null;
    template?: { name: string };
    recipients: CampaignRecipient[];
    can_be_paused: boolean;
    can_be_resumed: boolean;
    can_be_edited: boolean;
    can_be_sent: boolean;
}

interface Props {
    campaign: Campaign;
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('dashboard.title', 'Dashboard'), href: dashboard().url },
    { title: t('campaigns.title', 'Campaigns'), href: index().url },
    { title: props.campaign.name },
];

const confirmDelete = ref(false);

const handlePause = () => {
    router.post(
        pause(props.campaign.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const handleResume = () => {
    router.post(
        resume(props.campaign.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const handleSend = () => {
    router.post(
        send(props.campaign.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const handleDelete = () => {
    router.delete(destroy(props.campaign.id), {
        onSuccess: () => {
            confirmDelete.value = false;
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
        <Head :title="campaign.name" />

        <div :class="isRTL() ? 'text-right' : 'text-left'" class="space-y-6">
            <!-- Header -->
            <div
                :class="isRTL() ? 'flex-row-reverse' : ''"
                class="flex items-center justify-between"
            >
                <Heading :title="campaign.name">
                    <template #icon>
                        <div
                            class="flex items-center justify-center rounded-lg bg-[#25D366] p-2"
                        >
                            <MessageSquare class="h-5 w-5 text-white" />
                        </div>
                    </template>
                </Heading>

                <div
                    :class="isRTL() ? 'flex-row-reverse' : ''"
                    class="flex gap-2"
                >
                    <Button
                        v-if="campaign.can_be_edited"
                        as-child
                        variant="outline"
                    >
                        <Link :href="edit(campaign.id)">
                            <Edit2 class="mr-2 h-4 w-4" />
                            {{ t('common.edit', 'Edit') }}
                        </Link>
                    </Button>

                    <Button
                        v-if="campaign.can_be_paused"
                        variant="outline"
                        @click="handlePause"
                    >
                        <PauseIcon class="mr-2 h-4 w-4" />
                        {{ t('campaigns.actions.pause', 'Pause') }}
                    </Button>

                    <Button
                        v-if="campaign.can_be_resumed"
                        variant="outline"
                        @click="handleResume"
                    >
                        <Play class="mr-2 h-4 w-4" />
                        {{ t('campaigns.actions.resume', 'Resume') }}
                    </Button>

                    <Button
                        v-if="campaign.can_be_sent"
                        class="bg-[#25D366] hover:bg-[#25D366]/90"
                        @click="handleSend"
                    >
                        <SendIcon class="mr-2 h-4 w-4" />
                        {{ t('campaigns.actions.send', 'Send Now') }}
                    </Button>

                    <Button as-child variant="outline">
                        <Link :href="results(campaign.id)">
                            <Eye class="mr-2 h-4 w-4" />
                            {{
                                t(
                                    'campaigns.actions.view_results',
                                    'View Results',
                                )
                            }}
                        </Link>
                    </Button>

                    <Button variant="destructive" @click="confirmDelete = true">
                        <Trash2 class="mr-2 h-4 w-4" />
                        {{ t('common.delete', 'Delete') }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Campaign Info -->
                    <Card>
                        <CardHeader>
                            <div
                                :class="isRTL() ? 'flex-row-reverse' : ''"
                                class="flex items-center justify-between"
                            >
                                <CardTitle>{{
                                    t(
                                        'campaigns.campaign_info',
                                        'Campaign Information',
                                    )
                                }}</CardTitle>
                                <CampaignStatusBadge
                                    :status="campaign.status"
                                />
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Progress -->
                            <div>
                                <div
                                    :class="isRTL() ? 'flex-row-reverse' : ''"
                                    class="mb-2 flex justify-between text-sm"
                                >
                                    <span class="font-medium">{{
                                        t('campaigns.progress', 'Progress')
                                    }}</span>
                                    <span class="text-muted-foreground">
                                        {{ campaign.progress_percentage }}%
                                    </span>
                                </div>
                                <CampaignProgressBar
                                    :sent="campaign.messages_sent"
                                    :total="campaign.total_recipients"
                                />
                            </div>

                            <!-- Statistics Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="rounded-lg bg-muted p-4">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <Users class="h-4 w-4" />
                                        <span class="text-sm">{{
                                            t(
                                                'campaigns.stats.total_recipients',
                                                'Total Recipients',
                                            )
                                        }}</span>
                                    </div>
                                    <p class="mt-2 text-2xl font-bold">
                                        {{ campaign.total_recipients }}
                                    </p>
                                </div>

                                <div class="rounded-lg bg-muted p-4">
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <SendIcon class="h-4 w-4" />
                                        <span class="text-sm">{{
                                            t('campaigns.stats.sent', 'Sent')
                                        }}</span>
                                    </div>
                                    <p class="mt-2 text-2xl font-bold">
                                        {{ campaign.messages_sent }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-lg border border-[#25D366]/20 bg-[#25D366]/10 p-4"
                                >
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <CheckCircle class="h-4 w-4" />
                                        <span class="text-sm">{{
                                            t(
                                                'campaigns.stats.delivered',
                                                'Delivered',
                                            )
                                        }}</span>
                                    </div>
                                    <p
                                        class="mt-2 text-2xl font-bold text-[#25D366]"
                                    >
                                        {{ campaign.messages_delivered }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-lg border border-red-200 bg-red-50 p-4 dark:bg-red-950"
                                >
                                    <div
                                        class="flex items-center gap-2 text-muted-foreground"
                                    >
                                        <XCircle class="h-4 w-4" />
                                        <span class="text-sm">{{
                                            t(
                                                'campaigns.stats.failed',
                                                'Failed',
                                            )
                                        }}</span>
                                    </div>
                                    <p
                                        class="mt-2 text-2xl font-bold text-red-600"
                                    >
                                        {{ campaign.messages_failed }}
                                    </p>
                                </div>
                            </div>

                            <!-- Message Content -->
                            <div>
                                <h4 class="mb-2 font-medium">
                                    {{
                                        t(
                                            'campaigns.message_content',
                                            'Message Content',
                                        )
                                    }}
                                </h4>
                                <div class="rounded-lg bg-muted p-4">
                                    <p class="text-sm whitespace-pre-wrap">
                                        {{ campaign.message_content }}
                                    </p>

                                    <div v-if="campaign.media_url" class="mt-4">
                                        <Badge>{{
                                            campaign.message_type
                                        }}</Badge>
                                        <img
                                            v-if="
                                                campaign.message_type ===
                                                'image'
                                            "
                                            :src="campaign.media_url"
                                            alt="Media"
                                            class="mt-2 max-h-48 rounded-lg"
                                        />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Recent Recipients -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{
                                t(
                                    'campaigns.recent_recipients',
                                    'Recent Recipients',
                                )
                            }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <div
                                    v-for="recipient in campaign.recipients.slice(
                                        0,
                                        5,
                                    )"
                                    :key="recipient.contact.id"
                                    :class="isRTL() ? 'flex-row-reverse' : ''"
                                    class="flex items-center justify-between rounded-lg border p-3"
                                >
                                    <div
                                        :class="
                                            isRTL() ? 'text-right' : 'text-left'
                                        "
                                    >
                                        <p class="font-medium">
                                            {{ recipient.contact.first_name }}
                                            {{ recipient.contact.last_name }}
                                        </p>
                                        <p
                                            class="text-sm text-muted-foreground"
                                        >
                                            {{ recipient.contact.phone_number }}
                                        </p>
                                    </div>
                                    <Badge
                                        :class="
                                            recipient.status === 'delivered'
                                                ? 'bg-[#25D366]'
                                                : recipient.status === 'failed'
                                                  ? 'bg-red-500'
                                                  : 'bg-gray-500'
                                        "
                                    >
                                        {{ recipient.status }}
                                    </Badge>
                                </div>

                                <Button
                                    as-child
                                    class="w-full"
                                    variant="outline"
                                >
                                    <Link :href="results(campaign.id)">
                                        {{
                                            t(
                                                'campaigns.view_all_recipients',
                                                'View All Recipients',
                                            )
                                        }}
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Dates Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{
                                t('campaigns.timeline', 'Timeline')
                            }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                :class="
                                    isRTL()
                                        ? 'flex-row-reverse text-right'
                                        : 'text-left'
                                "
                                class="flex items-start gap-3"
                            >
                                <Calendar
                                    class="mt-0.5 h-5 w-5 text-muted-foreground"
                                />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">
                                        {{
                                            t('campaigns.created_at', 'Created')
                                        }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatDate(campaign.created_at) }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="campaign.scheduled_at"
                                :class="
                                    isRTL()
                                        ? 'flex-row-reverse text-right'
                                        : 'text-left'
                                "
                                class="flex items-start gap-3"
                            >
                                <Calendar
                                    class="mt-0.5 h-5 w-5 text-blue-600"
                                />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">
                                        {{
                                            t(
                                                'campaigns.scheduled_for',
                                                'Scheduled',
                                            )
                                        }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatDate(campaign.scheduled_at) }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="campaign.started_at"
                                :class="
                                    isRTL()
                                        ? 'flex-row-reverse text-right'
                                        : 'text-left'
                                "
                                class="flex items-start gap-3"
                            >
                                <Play class="mt-0.5 h-5 w-5 text-[#25D366]" />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">
                                        {{
                                            t('campaigns.started_at', 'Started')
                                        }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatDate(campaign.started_at) }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="campaign.completed_at"
                                :class="
                                    isRTL()
                                        ? 'flex-row-reverse text-right'
                                        : 'text-left'
                                "
                                class="flex items-start gap-3"
                            >
                                <CheckCircle
                                    class="mt-0.5 h-5 w-5 text-gray-600"
                                />
                                <div class="flex-1">
                                    <p class="text-sm font-medium">
                                        {{
                                            t(
                                                'campaigns.completed_at',
                                                'Completed',
                                            )
                                        }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatDate(campaign.completed_at) }}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Details Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{
                                t('campaigns.details', 'Details')
                            }}</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div>
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        t(
                                            'campaigns.message_type',
                                            'Message Type',
                                        )
                                    }}
                                </p>
                                <p class="font-medium">
                                    {{ campaign.message_type }}
                                </p>
                            </div>

                            <div v-if="campaign.template">
                                <p class="text-sm text-muted-foreground">
                                    {{ t('campaigns.template', 'Template') }}
                                </p>
                                <p class="font-medium">
                                    {{ campaign.template.name }}
                                </p>
                            </div>

                            <div>
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        t(
                                            'campaigns.success_rate',
                                            'Success Rate',
                                        )
                                    }}
                                </p>
                                <p class="font-medium text-[#25D366]">
                                    {{ campaign.success_rate }}%
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <Dialog
            :open="confirmDelete"
            @update:open="(open) => !open && (confirmDelete = false)"
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
                    <Button variant="outline" @click="confirmDelete = false">
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
