<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { useTranslation } from '@/composables/useTranslation';
import { router } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2, X } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface TourStep {
    title: string;
    description: string;
    action?: string;
    actionLabel?: string;
}

const { t } = useTranslation();

const showTour = ref(false);
const currentStep = ref(0);

const steps = computed<TourStep[]>(() => [
    {
        title: t('onboarding.step1.title'),
        description: t('onboarding.step1.desc'),
    },
    {
        title: t('onboarding.step2.title'),
        description: t('onboarding.step2.desc'),
        action: '/whatsapp/connection',
        actionLabel: t('onboarding.step2.action'),
    },
    {
        title: t('onboarding.step3.title'),
        description: t('onboarding.step3.desc'),
        action: '/contacts',
        actionLabel: t('onboarding.step3.action'),
    },
    {
        title: t('onboarding.step4.title'),
        description: t('onboarding.step4.desc'),
        action: '/templates',
        actionLabel: t('onboarding.step4.action'),
    },
    {
        title: t('onboarding.step5.title'),
        description: t('onboarding.step5.desc'),
        action: '/campaigns/create',
        actionLabel: t('onboarding.step5.action'),
    },
]);

const progressPercentage = computed(() => {
    return ((currentStep.value + 1) / steps.value.length) * 100;
});

const nextStep = () => {
    if (currentStep.value < steps.value.length - 1) {
        currentStep.value++;
    } else {
        completeTour();
    }
};

const previousStep = () => {
    if (currentStep.value > 0) {
        currentStep.value--;
    }
};

const skipTour = () => {
    if (confirm(t('onboarding.skip_confirm'))) {
        completeTour();
    }
};

const completeTour = async () => {
    try {
        await fetch('/onboarding/complete-step', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') || '',
            },
            body: JSON.stringify({ step: 'tour_completed' }),
        });

        showTour.value = false;
        localStorage.setItem('onboarding_tour_completed', 'true');
    } catch (error) {
        console.error('Failed to complete tour:', error);
    }
};

const goToAction = (action: string) => {
    router.visit(action);
    showTour.value = false;
};

onMounted(async () => {
    // Always check backend, don't rely only on localStorage
    try {
        const response = await fetch('dashboard/onboarding/status');
        const data = await response.json();

        if (data.success && !data.data.tour_completed) {
            // Show tour if not completed
            showTour.value = true;
        } else if (data.data.tour_completed) {
            // Sync localStorage if completed
            localStorage.setItem('onboarding_tour_completed', 'true');
        }
    } catch (error) {
        console.error('Failed to check onboarding status:', error);
        // If API fails, check localStorage as fallback
        const tourCompleted = localStorage.getItem('onboarding_tour_completed');
        if (!tourCompleted) {
            showTour.value = true;
        }
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="showTour"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <Card class="w-full max-w-2xl">
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center gap-2">
                                <CheckCircle2 class="size-5 text-[#25D366]" />
                                <CardTitle
                                    >{{ steps[currentStep].title }}
                                </CardTitle>
                            </div>
                            <CardDescription
                                >{{ steps[currentStep].description }}
                            </CardDescription>
                        </div>
                        <Button size="sm" variant="ghost" @click="skipTour">
                            <X class="size-4" />
                        </Button>
                    </div>
                </CardHeader>

                <CardContent>
                    <div class="space-y-4">
                        <Progress
                            :model-value="progressPercentage"
                            class="h-2"
                        />

                        <div class="text-center text-sm text-muted-foreground">
                            {{ t('onboarding.progress') }}
                            {{ currentStep + 1 }} {{ t('onboarding.of') }}
                            {{ steps.length }}
                        </div>
                    </div>
                </CardContent>

                <CardFooter class="flex justify-between gap-2">
                    <Button
                        :disabled="currentStep === 0"
                        variant="outline"
                        @click="previousStep"
                    >
                        {{ t('common.previous') }}
                    </Button>

                    <div class="flex gap-2">
                        <Button
                            v-if="steps[currentStep].action"
                            class="bg-[#25D366] hover:bg-[#128C7E]"
                            @click="goToAction(steps[currentStep].action!)"
                        >
                            {{ steps[currentStep].actionLabel }}
                            <ArrowRight class="ml-2 size-4" />
                        </Button>

                        <Button
                            v-if="currentStep < steps.length - 1"
                            @click="nextStep"
                        >
                            {{ t('common.next') }}
                            <ArrowRight class="ml-2 size-4" />
                        </Button>

                        <Button
                            v-else
                            class="bg-[#25D366] hover:bg-[#128C7E]"
                            @click="completeTour"
                        >
                            {{ t('onboarding.finish') }}
                        </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>
    </Teleport>
</template>
