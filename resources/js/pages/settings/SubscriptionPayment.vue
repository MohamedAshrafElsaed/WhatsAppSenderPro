<script lang="ts" setup>
import { Head, router } from '@inertiajs/vue3';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { useTranslation } from '@/composables/useTranslation';
import { index as dashboard } from '@/routes/dashboard';
import {
    index as subscriptionIndex,
    processPayment,
} from '@/routes/dashboard/settings/subscription';
import { type BreadcrumbItem } from '@/types';
import { CreditCard, Lock, Shield, Smartphone, Wallet } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Package {
    id: number;
    name: string;
    name_en: string;
    name_ar: string;
    slug: string;
    price: number;
    features: Record<string, any>;
    limits: Record<string, any>;
}

interface CurrentSubscription {
    package_name: string;
    price: number;
    is_trial: boolean;
}

interface PaymentMethod {
    id: number;
    value: string;
    label: string;
    label_en: string;
    label_ar: string;
    description: string;
    icon: any;
    gateway: string;
}

interface PaymentFees {
    base_amount: number;
    fee_amount: number;
    fee_percentage: number;
    total_amount: number;
}

interface Props {
    package: Package;
    currentSubscription?: CurrentSubscription;
    paymentMethods: PaymentMethod[];
    fees: PaymentFees;
    upgradePrice: number | null;
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
        title: t('subscription.payment', 'Payment'),
        href: '#',
    },
];

const selectedPaymentMethod = ref<number>(props.paymentMethods[0]?.id || 1);
const isProcessing = ref(false);

// Calculate price to pay
const priceToPay = computed(() => {
    return props.fees.total_amount;
});

// Show upgrade calculation only when upgrading from a paid plan
const showUpgradeCalculation = computed(() => {
    return props.currentSubscription &&
        !props.currentSubscription.is_trial &&
        props.upgradePrice !== null;
});

const handlePayment = () => {
    isProcessing.value = true;

    router.post(
        processPayment(),
        {
            package_id: props.package.id,
            payment_method_id: selectedPaymentMethod.value,
        },
        {
            preserveState: false,
            onError: () => {
                isProcessing.value = false;
            },
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('subscription.payment', 'Payment')" />

        <SettingsLayout>
            <div
                :dir="isRTL() ? 'rtl' : 'ltr'"
                class="mx-auto max-w-4xl space-y-8"
            >
                <!-- Order Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('payment.order_summary', 'Order Summary') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Selected Package -->
                        <div class="flex justify-between items-center p-4 bg-muted rounded-lg">
                            <div>
                                <h3 class="font-semibold">
                                    {{ isRTL() ? package.name_ar : package.name_en }}
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    {{ t('payment.monthly_subscription', 'Monthly Subscription') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold">{{ fees.base_amount }} {{ t('common.currency', 'EGP') }}</p>
                                <p class="text-sm text-muted-foreground">{{ t('payment.per_month', '/month') }}</p>
                            </div>
                        </div>

                        <!-- Upgrade Calculation (if applicable) -->
                        <div v-if="showUpgradeCalculation" class="p-4 border rounded-lg space-y-2">
                            <div class="flex justify-between text-sm">
                                <span>{{ t('payment.new_plan', 'New Plan') }}: {{ isRTL() ? package.name_ar : package.name_en }}</span>
                                <span>{{ package.price }} {{ t('common.currency', 'EGP') }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span>{{ t('payment.current_plan', 'Current Plan') }}: {{ currentSubscription!.package_name }}</span>
                                <span class="text-muted-foreground">-{{ currentSubscription!.price }} {{ t('common.currency', 'EGP') }}</span>
                            </div>
                            <div class="border-t pt-2">
                                <div class="flex justify-between font-semibold">
                                    <span>{{ t('payment.upgrade_difference', 'Upgrade Difference') }}</span>
                                    <span>{{ upgradePrice }} {{ t('common.currency', 'EGP') }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Processing Fee -->
                        <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="text-sm">{{ t('payment.processing_fee', 'Processing Fee') }} ({{ fees.fee_percentage }}%)</span>
                                <span class="text-sm font-medium">{{ fees.fee_amount }} {{ t('common.currency', 'EGP') }}</span>
                            </div>
                        </div>

                        <!-- Total to Pay -->
                        <div class="p-4 border-2 border-[#25D366] rounded-lg bg-[#25D366]/5">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold">{{ t('payment.total_to_pay', 'Total to Pay') }}</span>
                                <span class="text-2xl font-bold text-[#25D366]">{{ priceToPay }} {{ t('common.currency', 'EGP') }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Payment Methods -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{
                                t('payment.payment_method', 'Payment Method')
                            }}</CardTitle>
                        <CardDescription>
                            {{
                                t(
                                    'payment.select_payment_method',
                                    'Select your preferred payment method',
                                )
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <RadioGroup
                            v-model="selectedPaymentMethod"
                            class="space-y-4"
                        >
                            <div
                                v-for="method in paymentMethods"
                                :key="method.id"
                                :class="[
                                    'flex cursor-pointer items-center space-x-3 rounded-lg border p-4 transition-all',
                                    selectedPaymentMethod === method.id
                                        ? 'border-[#25D366] bg-[#25D366]/5'
                                        : 'hover:border-gray-300',
                                ]"
                                @click="selectedPaymentMethod = method.id"
                            >
                                <RadioGroupItem :value="method.id" />
                                <div
                                    :class="isRTL() ? 'mr-3' : 'ml-3'"
                                    class="flex-1"
                                >
                                    <div class="flex items-center gap-2">
                                        <component
                                            :is="method.icon === 'credit-card' ? CreditCard : method.icon === 'smartphone' ? Smartphone : Wallet"
                                            class="h-5 w-5 text-[#25D366]"
                                        />
                                        <Label
                                            class="cursor-pointer font-medium"
                                        >{{ isRTL() ? method.label_ar : method.label_en }}</Label>
                                    </div>
                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        {{ method.description }}
                                    </p>
                                </div>
                            </div>
                        </RadioGroup>
                    </CardContent>
                </Card>

                <!-- Security Note -->
                <Alert>
                    <Shield class="h-4 w-4" />
                    <AlertDescription>
                        <div class="flex items-center gap-2">
                            <Lock class="h-4 w-4" />
                            <span>{{
                                    t(
                                        'payment.secure_payment',
                                        'Your payment information is encrypted and secure',
                                    )
                                }}</span>
                        </div>
                    </AlertDescription>
                </Alert>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-4">
                    <Button
                        :disabled="isProcessing"
                        variant="outline"
                        @click="router.visit(subscriptionIndex())"
                    >
                        {{ t('common.cancel', 'Cancel') }}
                    </Button>
                    <Button
                        :disabled="isProcessing"
                        class="bg-[#25D366] hover:bg-[#128C7E]"
                        @click="handlePayment"
                    >
                        <CreditCard class="mr-2 h-4 w-4" />
                        {{
                            t(
                                'payment.proceed_to_payment',
                                'Proceed to Payment',
                            )
                        }}
                    </Button>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
