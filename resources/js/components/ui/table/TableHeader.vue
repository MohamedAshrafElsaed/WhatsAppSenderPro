<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class']
}>();
</script>

<template>
    <thead
        :class="cn('border-b border-border bg-muted/50', props.class)"
        data-slot="table-header"
    >
    <slot />
    </thead>
</template>
