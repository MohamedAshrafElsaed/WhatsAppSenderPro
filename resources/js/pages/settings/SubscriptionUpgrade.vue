<script lang="ts" setup>
import { Head, router } from '@inertiajs/vue3';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { index as dashboard } from '@/routes/dashboard';
import { index as subscriptionIndex } from '@/routes/dashboard/settings/subscription';
import { selectPackage } from '@/routes/dashboard/settings/subscription';
import { type BreadcrumbItem } from '@/types';
import {
    AlertCircle,
    Check,
    Zap,
    Star,
    Crown,
    ArrowRight,
    MessageSquare,
    Users,
    FileText,
    Smartphone
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Package {
    id: number;
    name: string;
    name_en: string;
    name_ar: string;
    slug: string;
    price: number;
    formatted_price: string;
    features: Record<string, any>;
    limits: Record<string, any>;
    is_popular: boolean;
    is_best_value: boolean;
    is_current: boolean;
    color: string;
}

interface SubscriptionSummary {
    has_subscription: boolean;
    status: string;
    package?: string;
    days_remaining?: number;
    ends_at?: string;
    is_trial?: boolean;
}

interface Props {
    packages: Package[];
    currentSubscription: SubscriptionSummary;
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
        href: subscriptionIndex().url,
    },
    {
        title: t('settings.subscription', 'Subscription'),
        href: subscriptionIndex().url,
    },
    {
        title: t('subscription.upgrade_title', 'Upgrade Plan'),
    },
];

const selectedPackageId = ref<number | null>(null);
const isProcessing = ref(false);

// Get package icon
const getPackageIcon = (slug: string) => {
    const icons: Record<string, any> = {
        basic: Zap,
        pro: Star,
        golden: Crown,
    };
    return icons[slug] || Zap;
};

// Get badge text for package
const getBadgeText = (pkg: Package) => {
    if (pkg.is_current) {
        return t('subscription.current_plan', 'Current Plan');
    }
    if (pkg.is_popular) {
        return t('subscription.popular', 'Most Popular');
    }
    if (pkg.is_best_value) {
        return t('subscription.best_value', 'Best Value');
    }
    return '';
};

// Get feature list based on package
const getFeatureList = (pkg: Package) => {
    const features: Array<{ icon: any; text: string }> = [];

    // Messages
    const messageLimit = pkg.limits?.messages_per_month;
    features.push({
        icon: MessageSquare,
        text: messageLimit === 'unlimited' ?
            t('packages.unlimited_messages', 'Unlimited messages') :
            t('packages.messages_per_month', `${messageLimit} messages/month`)
    });

    // Contacts
    const contactLimit = pkg.limits?.contacts_validation_per_month;
    features.push({
        icon: Users,
        text: contactLimit === 'unlimited' ?
            t('packages.unlimited_contacts', 'Unlimited contact validation') :
            t('packages.contacts_per_month', `${contactLimit} contacts validation/month`)
    });

    // WhatsApp Numbers
    const numberLimit = pkg.limits?.connected_numbers;
    features.push({
        icon: Smartphone,
        text: t('packages.connected_numbers', `${numberLimit} WhatsApp number${numberLimit > 1 ? 's' : ''}`)
    });

    // Templates
    const templateLimit = pkg.limits?.message_templates;
    features.push({
        icon: FileText,
        text: templateLimit === 'unlimited' ?
            t('packages.unlimited_templates', 'Unlimited message templates') :
            t('packages.templates', `${templateLimit} message template${templateLimit > 1 ? 's' : ''}`)
    });

    // Additional features based on package
    if (pkg.slug === 'pro' || pkg.slug === 'golden') {
        features.push({
            icon: Check,
            text: t('packages.media_support', 'Media messages (images, videos, documents)')
        });
    }

    if (pkg.slug === 'golden') {
        features.push({
            icon: Check,
            text: t('packages.priority_support', 'Priority support')
        });
        features.push({
            icon: Check,
            text: t('packages.api_access', 'API access')
        });
    }

    return features;
};

// Handle package selection
const handleSelectPackage = (packageId: number) => {
    selectedPackageId.value = packageId;
    isProcessing.value = true;

    router.post(selectPackage(), {
        package_id: packageId
    }, {
        preserveState: true,
        onError: () => {
            isProcessing.value = false;
        }
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('subscription.upgrade_title', 'Upgrade Your Plan')" />

        <SettingsLayout>
            <div :dir="isRTL() ? 'rtl' : 'ltr'" class="space-y-8">
                <!-- Header -->
                <div class="text-center">
                    <h2 class="text-3xl font-bold">{{ t('subscription.upgrade_title', 'Upgrade Your Plan') }}</h2>
                    <p class="mt-2 text-muted-foreground">
                        {{ t('subscription.upgrade_description', 'Choose the perfect plan for your business needs') }}
                    </p>
                </div>

                <!-- Trial Alert -->
                <Alert v-if="currentSubscription.is_trial && currentSubscription.days_remaining" class="border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20">
                    <AlertCircle class="h-4 w-4 text-yellow-600" />
                    <AlertDescription>
                        {{ t('subscription.trial_ending_soon', `Your trial ends in ${currentSubscription.days_remaining} days. Upgrade now to keep access to all features.`) }}
                    </AlertDescription>
                </Alert>

                <!-- Package Cards -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="pkg in packages"
                        :key="pkg.id"
                        :class="[
                            'relative transition-all hover:shadow-lg',
                            pkg.is_current ? 'border-2 border-muted-foreground' : '',
                            pkg.is_popular ? 'border-2 border-[#25D366] shadow-lg transform scale-105' : '',
                        ]"
                    >
                        <!-- Badge -->
                        <Badge
                            v-if="getBadgeText(pkg)"
                            :class="[
                                'absolute -top-3 left-1/2 transform -translate-x-1/2',
                                pkg.is_current ? 'bg-muted-foreground' : 'bg-[#25D366]'
                            ]"
                        >
                            {{ getBadgeText(pkg) }}
                        </Badge>

                        <CardHeader class="text-center">
                            <!-- Package Icon -->
                            <div :class="[
                                'mx-auto p-3 rounded-full mb-4',
                                pkg.color ? `bg-[${pkg.color}]/10` : 'bg-[#25D366]/10'
                            ]">
                                <component
                                    :is="getPackageIcon(pkg.slug)"
                                    :class="[
                                        'h-8 w-8',
                                        pkg.color ? `text-[${pkg.color}]` : 'text-[#25D366]'
                                    ]"
                                />
                            </div>

                            <!-- Package Name -->
                            <CardTitle class="text-2xl">
                                {{ isRTL() ? pkg.name_ar : pkg.name_en }}
                            </CardTitle>

                            <!-- Price -->
                            <div class="mt-4">
                                <span class="text-4xl font-bold">{{ pkg.formatted_price }}</span>
                                <span class="text-muted-foreground"> {{ t('common.currency', 'EGP') }}/{{ t('common.month', 'month') }}</span>
                            </div>
                        </CardHeader>

                        <CardContent class="space-y-6">
                            <!-- Features List -->
                            <ul class="space-y-3">
                                <li
                                    v-for="(feature, index) in getFeatureList(pkg)"
                                    :key="index"
                                    class="flex items-start gap-3"
                                >
                                    <component
                                        :is="feature.icon"
                                        class="h-5 w-5 text-[#25D366] shrink-0 mt-0.5"
                                    />
                                    <span class="text-sm">{{ feature.text }}</span>
                                </li>
                            </ul>

                            <!-- Action Button -->
                            <!-- Show select button if not current plan OR if current plan but on trial -->
                            <Button
                                v-if="!pkg.is_current || (pkg.is_current && currentSubscription.is_trial)"
                                :class="[
                                    'w-full',
                                    pkg.is_popular || (pkg.is_current && currentSubscription.is_trial) ? 'bg-[#25D366] hover:bg-[#128C7E]' : ''
                                ]"
                                :variant="pkg.is_popular || (pkg.is_current && currentSubscription.is_trial) ? 'default' : 'outline'"
                                :disabled="isProcessing"
                                @click="handleSelectPackage(pkg.id)"
                            >
                                <span v-if="pkg.is_current && currentSubscription.is_trial">
                                    {{ t('subscription.activate_plan', 'Activate This Plan') }}
                                </span>
                                <span v-else>
                                    {{ t('subscription.select_plan', 'Select This Plan') }}
                                </span>
                                <ArrowRight class="h-4 w-4 ml-2" />
                            </Button>

                            <!-- Only show disabled button if current plan AND not on trial -->
                            <Button
                                v-else-if="pkg.is_current && !currentSubscription.is_trial"
                                class="w-full"
                                disabled
                                variant="secondary"
                            >
                                {{ t('subscription.current_plan', 'Current Plan') }}
                            </Button>
                        </CardContent>
                    </Card>
                </div>

                <!-- Note -->
                <Alert>
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>
                        {{ t('subscription.payment_note', 'You will be redirected to secure payment page after selecting a plan. All payments are processed securely.') }}
                    </AlertDescription>
                </Alert>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
