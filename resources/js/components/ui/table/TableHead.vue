<script lang="ts" setup>
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    class?: HTMLAttributes['class']
}>();
</script>

<template>
    <th
        :class="cn(
      'h-11 px-4 text-left align-middle font-semibold text-muted-foreground [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-[2px]',
      props.class
    )"
        data-slot="table-head"
    >
        <slot />
    </th>
</template>
