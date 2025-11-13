<script lang="ts" setup>
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2 } from 'lucide-vue-next';
import { useTranslation } from '@/composables/useTranslation';
import { onMounted, ref } from 'vue';

interface Props {
    paymentData: {
        checkout_url: string;
        public_key: string;
        client_secret: string;
        intention_id: string;
        base_amount: number;
        fee_amount: number;
        total_amount: number;
    };
    transaction: {
        id: number;
        transaction_id: string;
    };
    package: {
        name: string;
        price: number;
    };
}

const props = defineProps<Props>();
const { t, isRTL } = useTranslation();
const isLoading = ref(true);

onMounted(() => {
    // Redirect to Paymob unified checkout
    // This will open in the same window for better UX
    window.location.href = props.paymentData.checkout_url;
});
</script>

<template>
    <div class="min-h-screen bg-background py-8">
        <Head :title="t('payment.processing', 'Processing Payment')" />

        <div class="container mx-auto max-w-4xl px-4">
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>{{ t('payment.redirecting', 'Redirecting to Payment') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <!-- Order Summary -->
                    <div class="rounded-lg border p-4">
                        <h3 class="font-semibold mb-2">{{ t('payment.order_summary', 'Order Summary') }}</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>{{ package.name }}</span>
                                <span>{{ paymentData.base_amount }} {{ t('common.currency', 'EGP') }}</span>
                            </div>
                            <div class="flex justify-between text-muted-foreground">
                                <span>{{ t('payment.processing_fee', 'Processing Fee') }} (2%)</span>
                                <span>{{ paymentData.fee_amount }} {{ t('common.currency', 'EGP') }}</span>
                            </div>
                            <div class="border-t pt-2 font-semibold">
                                <div class="flex justify-between">
                                    <span>{{ t('payment.total', 'Total') }}</span>
                                    <span class="text-[#25D366]">{{ paymentData.total_amount }} {{ t('common.currency', 'EGP') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transaction Info -->
                    <Alert>
                        <AlertDescription>
                            {{ t('payment.transaction_id', 'Transaction ID') }}: {{ transaction.transaction_id }}
                        </AlertDescription>
                    </Alert>

                    <!-- Loading indicator -->
                    <div class="flex items-center justify-center py-8">
                        <div class="text-center">
                            <Loader2 class="h-8 w-8 animate-spin text-[#25D366] mx-auto mb-4" />
                            <p class="text-lg font-medium">{{ t('payment.redirecting_to_payment', 'Redirecting to secure payment page...') }}</p>
                            <p class="text-sm text-muted-foreground mt-2">
                                {{ t('payment.please_wait', 'Please wait, you will be redirected automatically.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Manual redirect link -->
                    <div class="text-center mt-4">
                        <p class="text-sm text-muted-foreground">
                            {{ t('payment.not_redirected', "If you're not redirected automatically,") }}
                            <a
                                :href="paymentData.checkout_url"
                                class="text-[#25D366] hover:underline font-medium"
                            >
                                {{ t('payment.click_here', 'click here') }}
                            </a>
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
