<script lang="ts" setup>
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Progress } from '@/components/ui/progress';
import { useTranslation } from '@/composables/useTranslation';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    FileText,
    MessageSquare,
    Play,
    Plus,
    Upload,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface SubscriptionUsage {
    messages_sent: number;
    messages_limit: number | string;
    contacts_validated: number;
    contacts_limit: number | string;
}

interface SubscriptionSummary {
    has_subscription: boolean;
    status: string;
    package?: string;
    days_remaining?: number;
    ends_at?: string;
    is_trial?: boolean;
    usage?: SubscriptionUsage;
}

interface Tutorial {
    videoId: string;
    title: string;
    description: string;
    duration: string;
}

interface Props {
    subscription: SubscriptionSummary;
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();
const page = usePage();

const userName = computed(() => {
    const user = page.props.auth?.user;
    return user?.first_name || '';
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('dashboard.title', 'Dashboard'),
        href: dashboard().url,
    },
];

// Subscription Status
const getStatusColor = computed(() => {
    if (!props.subscription.has_subscription) return 'destructive';
    const status = props.subscription.status;
    const colors: Record<string, string> = {
        trial: 'secondary',
        active: 'default',
        expired: 'destructive',
        cancelled: 'outline',
    };
    return colors[status] || 'secondary';
});

const showTrialWarning = computed(() => {
    return (
        props.subscription.has_subscription &&
        props.subscription.is_trial &&
        props.subscription.days_remaining &&
        props.subscription.days_remaining <= 3
    );
});

// Usage calculations
const calculatePercentage = (used: number, limit: number | string): number => {
    if (limit === 'unlimited' || limit === '∞' || limit <= 0) {
        return 0;
    }
    const percentage = (used / (limit as number)) * 100;
    return Math.min(100, Math.round(percentage));
};

const getProgressColor = (percentage: number): string => {
    if (percentage >= 90) return 'bg-red-500';
    if (percentage >= 70) return 'bg-yellow-500';
    return 'bg-[#25D366]';
};

const formatLimit = (limit: number | string): string => {
    if (limit === 'unlimited' || limit === '∞') {
        return t('subscription.unlimited', '∞');
    }
    return limit.toString();
};

// Quick Actions
const quickActions = computed(() => [
    {
        title: t('dashboard.quick_actions.connect_whatsapp', 'Connect WhatsApp'),
        description: t('dashboard.quick_actions.connect_whatsapp_desc', 'Link your WhatsApp number'),
        icon: Plus,
        href: '/whatsapp/connect',
    },
    {
        title: t('dashboard.quick_actions.import_contacts', 'Import Contacts'),
        description: t('dashboard.quick_actions.import_contacts_desc', 'Upload your contact list'),
        icon: Upload,
        href: '/contacts/import',
    },
    {
        title: t('dashboard.quick_actions.create_campaign', 'Create Campaign'),
        description: t('dashboard.quick_actions.create_campaign_desc', 'Send bulk messages'),
        icon: MessageSquare,
        href: '/campaigns/create',
    },
    {
        title: t('dashboard.quick_actions.view_templates', 'View Templates'),
        description: t('dashboard.quick_actions.view_templates_desc', 'Manage message templates'),
        icon: FileText,
        href: '/templates',
    },
]);

// Getting Started Steps
const gettingStartedSteps = computed(() => [
    {
        title: t('dashboard.getting_started.step1', 'Connect your WhatsApp number'),
        description: t('dashboard.getting_started.step1_desc', 'Scan QR code to link your WhatsApp'),
        completed: false,
    },
    {
        title: t('dashboard.getting_started.step2', 'Import your contacts'),
        description: t('dashboard.getting_started.step2_desc', 'Upload CSV or connect Google Sheets'),
        completed: false,
    },
    {
        title: t('dashboard.getting_started.step3', 'Create your first campaign'),
        description: t('dashboard.getting_started.step3_desc', 'Send personalized messages to your contacts'),
        completed: false,
    },
]);

// Video Tutorials Data
const tutorials: Tutorial[] = [
    {
        videoId: 'dQw4w9WgXcQ', // Replace with actual YouTube video IDs
        title: 'dashboard.learning.video1_title',
        description: 'dashboard.learning.video1_desc',
        duration: '5:30',
    },
    {
        videoId: 'dQw4w9WgXcQ',
        title: 'dashboard.learning.video2_title',
        description: 'dashboard.learning.video2_desc',
        duration: '8:15',
    },
    {
        videoId: 'dQw4w9WgXcQ',
        title: 'dashboard.learning.video3_title',
        description: 'dashboard.learning.video3_desc',
        duration: '6:45',
    },
    {
        videoId: 'dQw4w9WgXcQ',
        title: 'dashboard.learning.video4_title',
        description: 'dashboard.learning.video4_desc',
        duration: '10:20',
    }
];

// Video Modal State
const videoModalOpen = ref(false);
const currentVideo = ref<Tutorial | null>(null);

const openVideoModal = (video: Tutorial) => {
    currentVideo.value = video;
    videoModalOpen.value = true;
};
</script>

<template>
    <Head :title="t('dashboard.title', 'Dashboard')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div
            class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6"
            :class="isRTL() ? 'text-right' : 'text-left'"
        >
            <!-- Welcome Section -->
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('dashboard.welcome', 'Welcome Back') }}{{ userName ? `, ${userName}` : '' }}!
                </h1>
                <p class="text-muted-foreground">
                    {{ t('dashboard.subtitle', "Here's what's happening with your WhatsApp campaigns today") }}
                </p>
            </div>

            <!-- Trial Warning Alert -->
            <Alert v-if="showTrialWarning" variant="default" class="border-[#25D366]">
                <AlertCircle class="size-4 text-[#25D366]" />
                <AlertDescription>
                    {{ t('dashboard.trial_warning', 'Your trial expires in') }}
                    <strong>{{ subscription.days_remaining }}</strong>
                    {{ t('dashboard.trial_warning_days', 'days. Upgrade now to continue using all features!') }}
                    <Link href="/subscription/upgrade" class="ml-2 font-semibold text-[#25D366] hover:underline">
                        {{ t('dashboard.upgrade_now', 'Upgrade Now') }}
                    </Link>
                </AlertDescription>
            </Alert>

            <!-- Subscription Status Card -->
            <Card v-if="subscription.has_subscription">
                <CardHeader>
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <CardTitle>{{ t('subscription.current_plan', 'Current Plan') }}</CardTitle>
                            <CardDescription class="mt-1">
                                {{ t('subscription.status', 'Status') }}:
                                <Badge :variant="getStatusColor" class="ml-2">
                                    {{ t(`subscription.${subscription.status}`, subscription.status) }}
                                </Badge>
                            </CardDescription>
                        </div>
                        <Link href="/subscription">
                            <Button variant="outline" size="sm">
                                {{ t('subscription.manage', 'Manage Plan') }}
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2">
                        <!-- Messages Usage -->
                        <div v-if="subscription.usage" class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">
                                    {{ t('subscription.messages_sent', 'Messages Sent') }}
                                </span>
                                <span class="font-medium">
                                    {{ subscription.usage.messages_sent }} /
                                    {{ formatLimit(subscription.usage.messages_limit) }}
                                </span>
                            </div>
                            <Progress
                                :model-value="calculatePercentage(
                                    subscription.usage.messages_sent,
                                    subscription.usage.messages_limit
                                )"
                                :class="getProgressColor(
                                    calculatePercentage(
                                        subscription.usage.messages_sent,
                                        subscription.usage.messages_limit
                                    )
                                )"
                            />
                        </div>

                        <!-- Contacts Usage -->
                        <div v-if="subscription.usage" class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-muted-foreground">
                                    {{ t('subscription.contacts_validated', 'Contacts Validated') }}
                                </span>
                                <span class="font-medium">
                                    {{ subscription.usage.contacts_validated }} /
                                    {{ formatLimit(subscription.usage.contacts_limit) }}
                                </span>
                            </div>
                            <Progress
                                :model-value="calculatePercentage(
                                    subscription.usage.contacts_validated,
                                    subscription.usage.contacts_limit
                                )"
                                :class="getProgressColor(
                                    calculatePercentage(
                                        subscription.usage.contacts_validated,
                                        subscription.usage.contacts_limit
                                    )
                                )"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Zap class="size-5 text-[#25D366]" />
                        {{ t('dashboard.quick_actions.title', 'Quick Actions') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('dashboard.quick_actions.description', 'Get started with these common tasks') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Link
                            v-for="(action, index) in quickActions"
                            :key="index"
                            :href="action.href"
                            class="flex flex-col items-center gap-3 rounded-lg border border-border bg-card p-6 text-center transition-all hover:border-[#25D366] hover:bg-accent hover:shadow-md"
                        >
                            <div class="flex size-12 items-center justify-center rounded-full bg-[#25D366]/10">
                                <component :is="action.icon" class="size-6 text-[#25D366]" />
                            </div>
                            <div class="space-y-1">
                                <h3 class="font-medium leading-tight">{{ action.title }}</h3>
                                <p class="text-xs text-muted-foreground">{{ action.description }}</p>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>

            <!-- Learning Center / Video Tutorials -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <svg class="size-5 text-[#25D366]" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M10 16.5l6-4.5-6-4.5v9zM12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                                </svg>
                                {{ t('dashboard.learning.title', 'Learning Center') }}
                            </CardTitle>
                            <CardDescription>{{ t('dashboard.learning.description', 'Watch tutorials to master WhatsApp Sender Pro') }}</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Card
                            v-for="(tutorial, index) in tutorials"
                            :key="index"
                            class="group cursor-pointer overflow-hidden transition-all hover:shadow-lg"
                            @click="openVideoModal(tutorial)"
                        >
                            <div class="relative aspect-video overflow-hidden bg-muted">
                                <img
                                    :src="`https://img.youtube.com/vi/${tutorial.videoId}/maxresdefault.jpg`"
                                    :alt="t(tutorial.title)"
                                    class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                                />
                                <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                                    <div class="flex size-12 items-center justify-center rounded-full bg-[#25D366] shadow-lg">
                                        <Play class="size-6 fill-white text-white" />
                                    </div>
                                </div>
                            </div>
                            <CardHeader class="p-3 pb-2">
                                <Badge variant="secondary" class="w-fit text-xs">{{ tutorial.duration }}</Badge>
                                <CardTitle class="mt-1.5 text-sm line-clamp-2 leading-tight">{{ t(tutorial.title) }}</CardTitle>
                                <CardDescription class="text-xs line-clamp-2 leading-tight">{{ t(tutorial.description) }}</CardDescription>
                            </CardHeader>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- Getting Started Guide -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <CheckCircle2 class="size-5 text-[#25D366]" />
                        {{ t('dashboard.getting_started.title', 'Getting Started') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('dashboard.getting_started.description', 'Complete these steps to get the most out of the platform') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="(step, index) in gettingStartedSteps"
                            :key="index"
                            class="flex items-start gap-4"
                            :class="isRTL() ? 'flex-row-reverse' : ''"
                        >
                            <div
                                class="flex size-8 shrink-0 items-center justify-center rounded-full"
                                :class="step.completed ? 'bg-[#25D366]' : 'bg-muted'"
                            >
                                <span
                                    class="text-sm font-bold"
                                    :class="step.completed ? 'text-white' : ''"
                                >
                                    {{ index + 1 }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-medium">{{ step.title }}</h3>
                                <p class="text-sm text-muted-foreground">{{ step.description }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Video Modal -->
        <Dialog v-model:open="videoModalOpen">
            <DialogContent class="max-w-4xl p-0">
                <DialogHeader class="p-6 pb-4">
                    <DialogTitle>{{ currentVideo ? t(currentVideo.title) : '' }}</DialogTitle>
                    <DialogDescription>{{ currentVideo ? t(currentVideo.description) : '' }}</DialogDescription>
                </DialogHeader>
                <div class="aspect-video w-full">
                    <iframe
                        v-if="currentVideo"
                        :src="`https://www.youtube.com/embed/${currentVideo.videoId}?autoplay=1`"
                        class="size-full"
                        allowfullscreen
                        allow="autoplay"
                    ></iframe>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
