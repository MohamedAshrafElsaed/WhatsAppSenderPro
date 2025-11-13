<script lang="ts" setup>
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { useTranslation } from '@/composables/useTranslation';
import { AlertCircle, FileText } from 'lucide-vue-next';
import { computed } from 'vue';

interface UsageStats {
    used: number;
    limit: number | string;
    remaining: number | string;
}

interface Props {
    usage: UsageStats;
}

const props = defineProps<Props>();
const { t } = useTranslation();

const isUnlimited = computed(() => {
    return props.usage.limit === 'unlimited' || props.usage.limit === '∞';
});

const percentage = computed(() => {
    if (isUnlimited.value) return 0;
    const limit = props.usage.limit as number;
    if (limit <= 0) return 0;
    return Math.min(100, Math.round((props.usage.used / limit) * 100));
});

const getProgressColor = computed(() => {
    const pct = percentage.value;
    if (pct >= 90) return 'bg-red-500';
    if (pct >= 70) return 'bg-yellow-500';
    return 'bg-[#25D366]';
});

const showWarning = computed(() => {
    return !isUnlimited.value && percentage.value >= 80;
});

const formatLimit = (value: number | string): string => {
    if (value === 'unlimited' || value === '∞') {
        return t('templates.usage.unlimited', 'Unlimited');
    }
    return value.toString();
};
</script>

<template>
    <Card>
        <CardContent class="p-4">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <FileText class="h-5 w-5 text-muted-foreground" />
                        <span class="font-medium">
                            {{ t('templates.usage.title', 'Template Usage') }}
                        </span>
                    </div>
                    <Badge
                        :variant="
                            showWarning
                                ? 'destructive'
                                : percentage >= 50
                                  ? 'secondary'
                                  : 'default'
                        "
                    >
                        {{
                            isUnlimited
                                ? formatLimit(usage.limit)
                                : t(
                                      'templates.usage.used_of_limit',
                                      '{used} of {limit}',
                                      {
                                          used: usage.used,
                                          limit: formatLimit(usage.limit),
                                      },
                                  )
                        }}
                    </Badge>
                </div>

                <div v-if="!isUnlimited" class="space-y-2">
                    <Progress
                        :class="getProgressColor"
                        :model-value="percentage"
                    />
                    <p class="text-sm text-muted-foreground">
                        {{
                            t(
                                'templates.usage.remaining',
                                '{remaining} remaining',
                                {
                                    remaining: usage.remaining,
                                },
                            )
                        }}
                    </p>
                </div>

                <Alert v-if="showWarning" class="mt-3" variant="destructive">
                    <AlertCircle class="h-4 w-4" />
                    <AlertDescription>
                        {{
                            t(
                                'templates.usage.warning',
                                'You are approaching your template limit. Consider upgrading your plan.',
                            )
                        }}
                    </AlertDescription>
                </Alert>
            </div>
        </CardContent>
    </Card>
</template>
