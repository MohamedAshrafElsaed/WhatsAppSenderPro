<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { useTranslation } from '@/composables/useTranslation';
import { computed } from 'vue';

type CampaignStatus =
    | 'draft'
    | 'scheduled'
    | 'running'
    | 'paused'
    | 'completed'
    | 'failed';

interface Props {
    status: CampaignStatus;
}

const props = defineProps<Props>();
const { t } = useTranslation();

const statusConfig = computed(() => {
    const configs: Record<CampaignStatus, { variant: string; class: string }> =
        {
            draft: {
                variant: 'secondary',
                class: 'bg-gray-500 text-white hover:bg-gray-600',
            },
            scheduled: {
                variant: 'default',
                class: 'bg-blue-500 text-white hover:bg-blue-600',
            },
            running: {
                variant: 'default',
                class: 'bg-[#25D366] text-white hover:bg-[#25D366]/90',
            },
            paused: {
                variant: 'default',
                class: 'bg-yellow-500 text-white hover:bg-yellow-600',
            },
            completed: {
                variant: 'secondary',
                class: 'bg-gray-500 text-white hover:bg-gray-600',
            },
            failed: {
                variant: 'destructive',
                class: 'bg-red-500 text-white hover:bg-red-600',
            },
        };

    return configs[props.status];
});

const statusLabel = computed(() => {
    return t(`campaigns.status.${props.status}`, props.status);
});
</script>

<template>
    <Badge :class="statusConfig.class">
        {{ statusLabel }}
    </Badge>
</template>
