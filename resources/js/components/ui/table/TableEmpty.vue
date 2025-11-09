<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{
    colspan?: number
    class?: HTMLAttributes['class']
}>(), {
    colspan: 100
})
</script>

<template>
    <tr data-slot="table-empty">
        <td
            :colspan="colspan"
            :class="cn(
        'h-24 text-center text-muted-foreground',
        props.class
      )"
        >
            <slot>
                <div class="flex flex-col items-center justify-center gap-2 py-8">
                    <p>No results found.</p>
                </div>
            </slot>
        </td>
    </tr>
</template>
