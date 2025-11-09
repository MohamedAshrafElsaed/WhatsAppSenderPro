<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class']
}>();
</script>

<template>
    <tfoot
        :class="cn(
      'border-t border-border bg-muted/50 font-medium [&>tr]:last:border-b-0',
      props.class
    )"
        data-slot="table-footer"
    >
    <slot />
    </tfoot>
</template>
