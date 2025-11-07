<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
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
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertCircle, Calendar, CreditCard } from 'lucide-vue-next';
import { ref } from 'vue';
import { useTranslation } from '@/composables/useTranslation';

interface SubscriptionSummary {
    has_subscription: boolean;
    status: string;
    package?: string;
    days_remaining?: number;
    ends_at?: string;
    is_trial?: boolean;
    usage?: {
        messages_sent: number;
        messages_limit: number | string;
        contacts_validated: number;
        contacts_limit: number | string;
    };
}

interface Props {
    subscription: SubscriptionSummary;
}

const props = defineProps<Props>();
const { t } = useTranslation();

const showCancelDialog = ref(false);

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        trial: 'bg-blue-500',
        active: 'bg-green-500',
        expired: 'bg-red-500',
        cancelled: 'bg-gray-500',
    };
    return colors[status] || 'bg-gray-500';
};

const handleCancelSubscription = () => {
    router.post(
        '/subscription/cancel',
        {},
        {
            onSuccess: () => {
                showCancelDialog.value = false;
            },
        },
    );
};

const formatLimit = (limit: number | string) => {
    return limit === 'unlimited'
        ? t('subscription.unlimited')
        : limit.toString();
};

const calculatePercentage = (used: number, limit: number | string) => {
    if (limit === 'unlimited') return 0;
    return Math.min(100, (used / Number(limit)) * 100);
};
</script>

<template>
    <Head :title="t('subscription.manage_title')" />

    <div class="space-y-6">
        <Heading
            :description="t('subscription.manage_description')"
            :title="t('subscription.manage_title')"
        />

        <div v-if="!subscription.has_subscription">
            <Alert>
                <AlertCircle class="size-4" />
                <AlertDescription>
                    {{ t('subscription.no_subscription') }}
                </AlertDescription>
            </Alert>
            <Link class="mt-4 inline-block" href="/subscription/upgrade">
                <Button class="bg-[#25D366] hover:bg-[#128C7E]">
                    {{ t('subscription.view_plans') }}
                </Button>
            </Link>
        </div>

        <div v-else class="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <CardTitle>{{
                            t('subscription.current_plan')
                        }}</CardTitle>
                        <Badge :class="getStatusColor(subscription.status)">
                            {{ t(`subscription.${subscription.status}`) }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center gap-3">
                        <CreditCard class="size-5 text-muted-foreground" />
                        <div>
                            <p class="text-sm text-muted-foreground">
                                {{ t('subscription.plan') }}
                            </p>
                            <p class="font-semibold">
                                {{ subscription.package }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="subscription.is_trial"
                        class="flex items-center gap-3"
                    >
                        <Calendar class="size-5 text-muted-foreground" />
                        <div>
                            <p class="text-sm text-muted-foreground">
                                {{ t('subscription.trial_ends') }}
                            </p>
                            <p class="font-semibold">
                                {{ subscription.days_remaining }}
                                {{ t('subscription.days_remaining') }}
                            </p>
                        </div>
                    </div>

                    <div class="pt-4">
                        <Link href="/subscription/upgrade">
                            <Button class="w-full" variant="outline">
                                {{ t('subscription.upgrade_plan') }}
                            </Button>
                        </Link>
                    </div>

                    <Button
                        v-if="!subscription.is_trial"
                        class="w-full"
                        variant="destructive"
                        @click="showCancelDialog = true"
                    >
                        {{ t('subscription.cancel_subscription') }}
                    </Button>
                </CardContent>
            </Card>

            <Card v-if="subscription.usage">
                <CardHeader>
                    <CardTitle>{{ t('subscription.usage_title') }}</CardTitle>
                    <CardDescription>{{
                        t('subscription.usage_description')
                    }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-sm font-medium">
                                {{ t('subscription.messages') }}
                            </span>
                            <span class="text-sm text-muted-foreground">
                                {{ subscription.usage.messages_sent }} /
                                {{
                                    formatLimit(
                                        subscription.usage.messages_limit,
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            class="h-2 w-full overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                :style="{
                                    width: `${calculatePercentage(
                                        subscription.usage.messages_sent,
                                        subscription.usage.messages_limit,
                                    )}%`,
                                }"
                                class="h-full bg-[#25D366] transition-all"
                            ></div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-sm font-medium">
                                {{ t('subscription.contacts') }}
                            </span>
                            <span class="text-sm text-muted-foreground">
                                {{ subscription.usage.contacts_validated }} /
                                {{
                                    formatLimit(
                                        subscription.usage.contacts_limit,
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            class="h-2 w-full overflow-hidden rounded-full bg-muted"
                        >
                            <div
                                :style="{
                                    width: `${calculatePercentage(
                                        subscription.usage.contacts_validated,
                                        subscription.usage.contacts_limit,
                                    )}%`,
                                }"
                                class="h-full bg-[#25D366] transition-all"
                            ></div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog v-model:open="showCancelDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        t('subscription.confirm_cancel_title')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{ t('subscription.confirm_cancel_description') }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showCancelDialog = false">
                        {{ t('common.cancel') }}
                    </Button>
                    <Button
                        variant="destructive"
                        @click="handleCancelSubscription"
                    >
                        {{ t('subscription.confirm_cancel') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
