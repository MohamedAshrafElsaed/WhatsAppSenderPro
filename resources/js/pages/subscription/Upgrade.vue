<script lang="ts" setup>
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/vue3';
import { AlertCircle, Check } from 'lucide-vue-next';
import { useTranslation } from '@/composables/useTranslation';

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
const { t } = useTranslation();

const props = defineProps<Props>();

const getBadgeText = (pkg: Package) => {
    if (pkg.is_current) {
        return t('subscription.current_plan');
    }
    if (pkg.is_popular) {
        return t('subscription.popular');
    }
    if (pkg.is_best_value) {
        return t('subscription.best_value');
    }
    return '';
};

const getFeatureList = (pkg: Package) => {
    const features: string[] = [];

    if (pkg.slug === 'basic') {
        features.push(
            t('packages.basic.messages'),
            t('packages.basic.contacts'),
            t('packages.basic.numbers'),
            t('packages.basic.templates'),
        );
    } else if (pkg.slug === 'pro') {
        features.push(
            t('packages.pro.messages'),
            t('packages.pro.contacts'),
            t('packages.pro.numbers'),
            t('packages.pro.templates'),
            t('packages.pro.features.media'),
        );
    } else if (pkg.slug === 'golden') {
        features.push(
            t('packages.golden.messages'),
            t('packages.golden.contacts'),
            t('packages.golden.numbers'),
            t('packages.golden.templates'),
            t('packages.golden.features.full_media'),
        );
    }

    return features;
};

const handleUpgrade = (packageId: number) => {
    alert(t('subscription.payment_coming_soon'));
};
</script>

<template>
    <Head :title="t('subscription.upgrade_title')" />

    <div class="space-y-6">
        <Heading
            :description="t('subscription.upgrade_description')"
            :title="t('subscription.upgrade_title')"
        />

        <Alert v-if="currentSubscription.is_trial">
            <AlertCircle class="size-4" />
            <AlertDescription>
                {{
                    t('subscription.trial_ending_soon', {
                        days: currentSubscription.days_remaining,
                    })
                }}
            </AlertDescription>
        </Alert>

        <div class="grid gap-6 lg:grid-cols-3">
            <Card
                v-for="pkg in packages"
                :key="pkg.id"
                :class="[
                    pkg.is_current ? 'border-2 border-muted-foreground' : '',
                    pkg.is_popular ? 'border-2 border-[#25D366] shadow-lg' : '',
                ]"
                class="relative"
            >
                <Badge
                    v-if="pkg.is_current || pkg.is_popular || pkg.is_best_value"
                    :class="
                        pkg.is_current ? 'bg-muted-foreground' : 'bg-[#25D366]'
                    "
                    class="absolute top-4 right-4"
                >
                    {{ getBadgeText(pkg) }}
                </Badge>

                <CardHeader>
                    <CardTitle class="text-2xl">{{ pkg.name }}</CardTitle>
                    <div class="mt-4">
                        <span class="text-4xl font-bold">{{
                            pkg.formatted_price
                        }}</span>
                    </div>
                </CardHeader>

                <CardContent class="space-y-6">
                    <ul class="space-y-2">
                        <li
                            v-for="(feature, index) in getFeatureList(pkg)"
                            :key="index"
                            class="flex items-start gap-2"
                        >
                            <Check
                                class="mt-0.5 size-5 shrink-0 text-[#25D366]"
                            />
                            <span class="text-sm">{{ feature }}</span>
                        </li>
                    </ul>

                    <Button
                        v-if="!pkg.is_current"
                        :class="
                            pkg.is_popular
                                ? 'bg-[#25D366] hover:bg-[#128C7E]'
                                : ''
                        "
                        :variant="pkg.is_popular ? 'default' : 'outline'"
                        class="w-full"
                        @click="handleUpgrade(pkg.id)"
                    >
                        {{ t('subscription.upgrade_to_plan') }}
                    </Button>
                    <Button v-else class="w-full" disabled variant="outline">
                        {{ t('subscription.current_plan') }}
                    </Button>
                </CardContent>
            </Card>
        </div>

        <Alert>
            <AlertCircle class="size-4" />
            <AlertDescription>
                {{ t('subscription.payment_integration_note') }}
            </AlertDescription>
        </Alert>
    </div>
</template>
