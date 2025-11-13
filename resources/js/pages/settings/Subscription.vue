<script lang="ts" setup>
import { Head, router } from '@inertiajs/vue3';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { index as dashboard } from '@/routes/dashboard';
import { index } from '@/routes/dashboard/settings/subscription';
import { cancel } from '@/routes/dashboard/settings/subscription';
import { upgrade } from '@/routes/dashboard/settings/subscription';

import { type BreadcrumbItem } from '@/types';
import {
    AlertCircle,
    CheckCircle,
    CreditCard,
    Package,
    TrendingUp,
    Clock,
    Zap,
    Star,
    Crown
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface UsageItem {
    used: number;
    limit: number | string;
    remaining: number | string;
}

interface Usage {
    messages: UsageItem;
    contacts: UsageItem;
    numbers: UsageItem;
    templates: UsageItem;
}

interface PackageInfo {
    id: number;
    name: string;
    name_en: string;
    name_ar: string;
    slug: string;
    price: number;
    billing_cycle: string;
    features: Record<string, any>;
    limits: Record<string, any>;
    color?: string;
}

interface Subscription {
    id: number;
    status: string;
    package: PackageInfo;
    starts_at: string;
    ends_at: string;
    trial_ends_at?: string;
    days_remaining: number;
    is_trial: boolean;
    is_expired: boolean;
    auto_renew: boolean;
}

interface Transaction {
    id: number;
    transaction_id: string;
    amount: number;
    currency: string;
    status: string;
    package_name: string;
    payment_gateway: string;
    paid_at?: string;
    created_at: string;
}

interface AvailablePackage {
    id: number;
    name: string;
    slug: string;
    price: number;
    billing_cycle: string;
    features: Record<string, any>;
    limits: Record<string, any>;
    is_popular: boolean;
    is_best_value: boolean;
    color?: string;
}

interface Props {
    subscription?: Subscription;
    usage: Usage;
    packages: AvailablePackage[];
    transactions: Transaction[];
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: t('dashboard.title', 'Dashboard'),
        href: dashboard().url,
    },
    {
        title: t('settings.title', 'Settings'),
        href: index().url,
    },
    {
        title: t('settings.subscription', 'Subscription'),
        href: index().url,
    },
];

const showCancelDialog = ref(false);

// Get package icon based on slug
const getPackageIcon = (slug: string) => {
    const icons: Record<string, any> = {
        basic: Zap,
        pro: Star,
        golden: Crown,
    };
    return icons[slug] || Package;
};

// Calculate usage percentages
const calculatePercentage = (used: number, limit: number | string): number => {
    if (limit === 'unlimited' || limit === '∞' || typeof limit === 'string') return 0;
    if (limit <= 0) return 0;
    return Math.min(100, Math.round((used / limit) * 100));
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
    return typeof limit === 'number' ? limit.toLocaleString() : limit;
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString(isRTL() ? 'ar-EG' : 'en-US');
};

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        active: 'default',
        trial: 'secondary',
        cancelled: 'destructive',
        expired: 'destructive',
        completed: 'default',
        pending: 'secondary',
        failed: 'destructive',
        refunded: 'outline',
    };
    return colors[status] || 'default';
};

const getStatusIcon = (status: string) => {
    if (status === 'active' || status === 'completed') return CheckCircle;
    if (status === 'trial') return Clock;
    return AlertCircle;
};

const handleCancelSubscription = () => {
    router.post(cancel(), {}, {
        preserveState: true,
        onSuccess: () => {
            showCancelDialog.value = false;
        },
    });
};

const handleUpgrade = () => {
    router.visit(upgrade());
};

// Usage items with labels
const usageItems = computed(() => [
    {
        key: 'messages',
        label: t('subscription.messages', 'Messages'),
        icon: '💬',
        data: props.usage.messages,
    },
    {
        key: 'contacts',
        label: t('subscription.contacts', 'Contacts Validated'),
        icon: '✅',
        data: props.usage.contacts,
    },
    {
        key: 'numbers',
        label: t('subscription.connected_numbers', 'Connected Numbers'),
        icon: '📱',
        data: props.usage.numbers,
    },
    {
        key: 'templates',
        label: t('subscription.templates', 'Templates'),
        icon: '📄',
        data: props.usage.templates,
    },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('settings.subscription_settings', 'Subscription Settings')" />

        <SettingsLayout>
            <div :dir="isRTL() ? 'rtl' : 'ltr'" class="space-y-8">
                <!-- Current Plan Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Package class="h-5 w-5 text-[#25D366]" />
                            {{ t('subscription.current_plan', 'Current Plan') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('subscription.manage_description', 'View and manage your current subscription') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="subscription" class="space-y-6">
                            <!-- Plan Header -->
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div :class="[
                                        'p-3 rounded-lg',
                                        subscription.package.color ?
                                            `bg-[${subscription.package.color}]/10` :
                                            'bg-[#25D366]/10'
                                    ]">
                                        <component
                                            :is="getPackageIcon(subscription.package.slug)"
                                            :class="[
                                                'h-6 w-6',
                                                subscription.package.color ?
                                                    `text-[${subscription.package.color}]` :
                                                    'text-[#25D366]'
                                            ]"
                                        />
                                    </div>
                                    <div>
                                        <h3 class="text-2xl font-bold">
                                            {{ isRTL() ? subscription.package.name_ar : subscription.package.name_en }}
                                        </h3>
                                        <p class="text-muted-foreground">
                                            {{ subscription.package.price }}
                                            {{ t('common.currency', 'EGP') }} /
                                            {{ t('common.month', 'month') }}
                                        </p>
                                    </div>
                                </div>
                                <Badge :variant="getStatusColor(subscription.status)" class="text-lg px-3 py-1">
                                    <component :is="getStatusIcon(subscription.status)" class="h-4 w-4 mr-1" />
                                    {{ subscription.is_trial ? t('subscription.trial', 'Trial') : t(`subscription.${subscription.status}`) }}
                                </Badge>
                            </div>

                            <!-- Subscription Dates -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-muted/50 rounded-lg">
                                <div>
                                    <p class="text-sm text-muted-foreground">{{ t('subscription.started', 'Started') }}</p>
                                    <p class="font-medium">{{ formatDate(subscription.starts_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">
                                        {{ subscription.is_trial ? t('subscription.trial_ends', 'Trial Ends') : t('subscription.expires', 'Expires') }}
                                    </p>
                                    <p class="font-medium">{{ formatDate(subscription.ends_at) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-muted-foreground">{{ t('subscription.days_remaining', 'Days Remaining') }}</p>
                                    <p class="font-medium text-lg">{{ subscription.days_remaining }} {{ t('common.days', 'days') }}</p>
                                </div>
                            </div>

                            <!-- Trial Warning -->
                            <Alert v-if="subscription.is_trial && subscription.days_remaining <= 3" class="border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20">
                                <AlertCircle class="h-4 w-4 text-yellow-600" />
                                <AlertDescription>
                                    {{ t('subscription.trial_ending_soon', `Your trial ends in ${subscription.days_remaining} days. Upgrade now to keep access to all features.`) }}
                                </AlertDescription>
                            </Alert>

                            <!-- Expired Warning -->
                            <Alert v-if="subscription.is_expired" class="border-red-500 bg-red-50 dark:bg-red-900/20">
                                <AlertCircle class="h-4 w-4 text-red-600" />
                                <AlertDescription>
                                    {{ t('subscription.subscription_expired', 'Your subscription has expired. Please renew to continue.') }}
                                </AlertDescription>
                            </Alert>

                            <!-- Action Buttons -->
                            <div class="flex gap-2">
                                <Button
                                    @click="handleUpgrade"
                                    class="bg-[#25D366] hover:bg-[#128C7E]"
                                >
                                    {{ subscription.is_trial || subscription.is_expired ?
                                    t('subscription.upgrade_plan', 'Upgrade Plan') :
                                    t('subscription.change_plan', 'Change Plan')
                                    }}
                                </Button>
                                <Button
                                    v-if="subscription.auto_renew && !subscription.is_expired"
                                    variant="destructive"
                                    @click="showCancelDialog = true"
                                >
                                    {{ t('subscription.cancel_subscription', 'Cancel Subscription') }}
                                </Button>
                            </div>
                        </div>

                        <!-- No Subscription State -->
                        <div v-else class="text-center py-8">
                            <div class="mb-4">
                                <Package class="h-12 w-12 text-muted-foreground mx-auto" />
                            </div>
                            <p class="text-muted-foreground mb-4">
                                {{ t('subscription.no_subscription', "You don't have an active subscription") }}
                            </p>
                            <Button @click="handleUpgrade" class="bg-[#25D366] hover:bg-[#128C7E]">
                                {{ t('subscription.view_plans', 'View Plans') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Usage Statistics -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <TrendingUp class="h-5 w-5 text-[#25D366]" />
                            {{ t('subscription.usage_title', 'Monthly Usage') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('subscription.usage_description', 'Your current usage for this billing period') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div
                            v-for="item in usageItems"
                            :key="item.key"
                            class="space-y-2"
                        >
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-2">
                                    <span class="text-xl">{{ item.icon }}</span>
                                    <span class="font-medium">{{ item.label }}</span>
                                </div>
                                <span class="text-sm font-medium">
                                    {{ item.data.used.toLocaleString() }} / {{ formatLimit(item.data.limit) }}
                                </span>
                            </div>
                            <Progress
                                :value="calculatePercentage(item.data.used, item.data.limit)"
                                :class="getProgressColor(calculatePercentage(item.data.used, item.data.limit))"
                                class="h-2"
                            />
                            <div class="flex justify-between text-xs text-muted-foreground">
                                <span>{{ t('subscription.used', 'Used') }}: {{ item.data.used.toLocaleString() }}</span>
                                <span>{{ t('subscription.remaining', 'Remaining') }}: {{ formatLimit(item.data.remaining) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment History -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <CreditCard class="h-5 w-5 text-[#25D366]" />
                            {{ t('settings.payment_history', 'Payment History') }}
                        </CardTitle>
                        <CardDescription>
                            {{ t('settings.payment_history_description', 'Your recent transactions') }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="transactions.length > 0" class="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{{ t('settings.date', 'Date') }}</TableHead>
                                        <TableHead>{{ t('settings.package', 'Package') }}</TableHead>
                                        <TableHead>{{ t('settings.amount', 'Amount') }}</TableHead>
                                        <TableHead>{{ t('subscription.status', 'Status') }}</TableHead>
                                        <TableHead>{{ t('settings.method', 'Method') }}</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="transaction in transactions" :key="transaction.id">
                                        <TableCell>{{ formatDate(transaction.created_at) }}</TableCell>
                                        <TableCell>{{ transaction.package_name }}</TableCell>
                                        <TableCell>{{ transaction.amount }} {{ transaction.currency }}</TableCell>
                                        <TableCell>
                                            <Badge :variant="getStatusColor(transaction.status)">
                                                {{ t(`subscription.${transaction.status}`) }}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{{ transaction.payment_gateway }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                        <div v-else class="text-center py-8 text-muted-foreground">
                            {{ t('settings.no_transactions', 'No transactions yet') }}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>

        <!-- Cancel Subscription Dialog -->
        <Dialog v-model:open="showCancelDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ t('subscription.confirm_cancel_title', 'Cancel Subscription?') }}</DialogTitle>
                    <DialogDescription>
                        {{ t('subscription.confirm_cancel_description', 'Are you sure you want to cancel your subscription? You will lose access to premium features at the end of your billing period.') }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showCancelDialog = false">
                        {{ t('common.cancel', 'Cancel') }}
                    </Button>
                    <Button variant="destructive" @click="handleCancelSubscription">
                        {{ t('subscription.confirm_cancel', 'Yes, Cancel Subscription') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
