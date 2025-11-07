<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    modelValue?: number;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: 0,
});

const percentage = computed(() => {
    return Math.min(100, Math.max(0, props.modelValue));
});
</script>

<template>
    <div class="relative h-2 w-full overflow-hidden rounded-full bg-muted">
        <div
            :class="props.class"
            class="h-full transition-all duration-300 ease-in-out"
            :style="{ width: `${percentage}%` }"
        />
    </div>
</template>
