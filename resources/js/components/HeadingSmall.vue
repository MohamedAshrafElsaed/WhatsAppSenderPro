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
    <header :class="isRTL ? 'text-right' : 'text-left'">
        <h3 class="mb-0.5 text-base font-medium">{{ title }}</h3>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
