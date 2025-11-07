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
    Plus,
    TrendingUp,
    Upload,
    Users,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';

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

interface Props {
    subscription: SubscriptionSummary;
}

const props = defineProps<Props>();
const { t } = useTranslation();
const page = usePage();

const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');
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
</script>

<template>
    <Head :title="t('dashboard.title', 'Dashboard')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Welcome Section -->
            <div class="space-y-2">
                <h1 class="text-3xl font-bold tracking-tight">
                    {{ t('dashboard.welcome', 'Welcome Back') }}{{ userName ? `, ${userName}` : '' }}!
                </h1>
                <p class="text-muted-foreground">
                    {{ t('dashboard.subtitle', "Here's an overview of your WhatsApp campaigns") }}
                </p>
            </div>

            <!-- Trial Warning Alert -->
            <Alert
                v-if="showTrialWarning"
                class="border-yellow-500/50 bg-yellow-50 dark:bg-yellow-950/20"
            >
                <AlertCircle class="size-4 text-yellow-600 dark:text-yellow-500" />
                <AlertDescription class="text-yellow-800 dark:text-yellow-200">
                    {{ t('subscription.trial_ending_soon', `Your trial ends in ${subscription.days_remaining} days`) }}
                    <Link
                        class="font-medium underline"
                        :class="isRTL ? 'mr-2' : 'ml-2'"
                        href="/subscription/upgrade"
                    >
                        {{ t('subscription.upgrade_plan', 'Upgrade Now') }}
                    </Link>
                </AlertDescription>
            </Alert>

            <!-- No Subscription Alert -->
            <Alert v-if="!subscription.has_subscription" variant="destructive">
                <AlertCircle class="size-4" />
                <AlertDescription>
                    {{ t('subscription.no_active_subscription', 'No active subscription') }}
                    <Link
                        class="font-medium underline"
                        :class="isRTL ? 'mr-2' : 'ml-2'"
                        href="/subscription/upgrade"
                    >
                        {{ t('subscription.view_plans', 'View Plans') }}
                    </Link>
                </AlertDescription>
            </Alert>

            <!-- Subscription Overview Card -->
            <Card v-if="subscription.has_subscription">
                <CardHeader>
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="space-y-1">
                            <CardTitle class="flex flex-wrap items-center gap-2">
                                {{ t('subscription.current_plan', 'Current Plan') }}
                                <Badge :variant="getStatusColor">
                                    {{ subscription.package }}
                                </Badge>
                            </CardTitle>
                            <CardDescription>
                                <template v-if="subscription.is_trial">
                                    {{ t('subscription.trial_ends', 'Trial ends in') }}
                                    {{ subscription.days_remaining }}
                                    {{ t('subscription.days_remaining', 'days') }}
                                </template>
                                <template v-else>
                                    {{ subscription.days_remaining }}
                                    {{ t('subscription.days_remaining', 'days remaining') }}
                                </template>
                            </CardDescription>
                        </div>
                        <Link href="/subscription/upgrade">
                            <Button class="whitespace-nowrap">
                                {{ t('subscription.upgrade_plan', 'Upgrade Plan') }}
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
            </Card>

            <!-- Usage Statistics -->
            <div
                v-if="subscription.has_subscription && subscription.usage"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <!-- Messages Sent -->
                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium">
                                {{ t('dashboard.usage.messages', 'Messages Sent') }}
                            </CardTitle>
                            <MessageSquare class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-baseline justify-between">
                                <div class="text-2xl font-bold">
                                    {{ subscription.usage.messages_sent }}
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    / {{ formatLimit(subscription.usage.messages_limit) }}
                                </span>
                            </div>
                            <Progress
                                :class="getProgressColor(calculatePercentage(subscription.usage.messages_sent, subscription.usage.messages_limit))"
                                :model-value="calculatePercentage(subscription.usage.messages_sent, subscription.usage.messages_limit)"
                            />
                            <p class="text-xs text-muted-foreground">
                                {{ calculatePercentage(subscription.usage.messages_sent, subscription.usage.messages_limit) }}%
                                {{ t('dashboard.usage.used', 'used') }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Contacts Validated -->
                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium">
                                {{ t('dashboard.usage.contacts', 'Contacts Validated') }}
                            </CardTitle>
                            <Users class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="flex items-baseline justify-between">
                                <div class="text-2xl font-bold">
                                    {{ subscription.usage.contacts_validated }}
                                </div>
                                <span class="text-sm text-muted-foreground">
                                    / {{ formatLimit(subscription.usage.contacts_limit) }}
                                </span>
                            </div>
                            <Progress
                                :class="getProgressColor(calculatePercentage(subscription.usage.contacts_validated, subscription.usage.contacts_limit))"
                                :model-value="calculatePercentage(subscription.usage.contacts_validated, subscription.usage.contacts_limit)"
                            />
                            <p class="text-xs text-muted-foreground">
                                {{ calculatePercentage(subscription.usage.contacts_validated, subscription.usage.contacts_limit) }}%
                                {{ t('dashboard.usage.used', 'used') }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Connected Numbers -->
                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium">
                                {{ t('dashboard.usage.numbers', 'Connected Numbers') }}
                            </CardTitle>
                            <Zap class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="text-2xl font-bold">0</div>
                            <p class="text-xs text-muted-foreground">
                                {{ t('dashboard.usage.numbers_desc', 'Active WhatsApp connections') }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Templates Created -->
                <Card>
                    <CardHeader class="pb-2">
                        <div class="flex items-center justify-between">
                            <CardTitle class="text-sm font-medium">
                                {{ t('dashboard.usage.templates', 'Message Templates') }}
                            </CardTitle>
                            <FileText class="size-4 text-muted-foreground" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="text-2xl font-bold">0</div>
                            <p class="text-xs text-muted-foreground">
                                {{ t('dashboard.usage.templates_desc', 'Saved message templates') }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Quick Actions -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <TrendingUp class="size-5 text-[#25D366]" />
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
                            class="flex flex-col items-center gap-2 rounded-lg border border-border p-4 text-center transition-all hover:border-[#25D366] hover:bg-accent"
                        >
                            <div class="flex size-12 items-center justify-center rounded-full bg-[#25D366]/10">
                                <component :is="action.icon" class="size-6 text-[#25D366]" />
                            </div>
                            <h3 class="font-medium">{{ action.title }}</h3>
                            <p class="text-xs text-muted-foreground">{{ action.description }}</p>
                        </Link>
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
    </AppLayout>
</template>
