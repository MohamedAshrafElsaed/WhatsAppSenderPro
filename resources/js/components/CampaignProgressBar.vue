<script lang="ts" setup>
import { Progress } from '@/components/ui/progress';
import { computed } from 'vue';

interface Props {
    sent: number;
    total: number;
    showLabel?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showLabel: true,
});

const percentage = computed(() => {
    if (props.total === 0) return 0;
    return Math.round((props.sent / props.total) * 100);
});

const progressColor = computed(() => {
    const pct = percentage.value;
    if (pct >= 90) return '[&>div]:bg-red-500';
    if (pct >= 70) return '[&>div]:bg-yellow-500';
    return '[&>div]:bg-[#25D366]';
});

const progressLabel = computed(() => {
    return `${props.sent} / ${props.total} (${percentage.value}%)`;
});
</script>

<template>
    <div class="space-y-2">
        <Progress
            :class="progressColor"
            :model-value="percentage"
            class="h-2"
        />
        <div
            v-if="showLabel"
            class="flex justify-between text-sm text-muted-foreground"
        >
            <span>{{ progressLabel }}</span>
            <span>{{ percentage }}%</span>
        </div>
    </div>
</template>
