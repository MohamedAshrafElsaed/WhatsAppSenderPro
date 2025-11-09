<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();

const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');
</script>

<template>
    <div :class="isRTL ? 'text-right' : 'text-left'" class="mb-8 space-y-0.5">
        <h2 class="text-xl font-semibold tracking-tight">{{ title }}</h2>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </div>
</template>
