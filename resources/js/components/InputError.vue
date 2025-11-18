<script lang="ts" setup>
import { computed } from 'vue';

const props = defineProps<{
    message?: string | string[];
}>();

// Extract first message if array, otherwise use as-is
const displayMessage = computed(() => {
    if (!props.message) return '';

    if (Array.isArray(props.message)) {
        return props.message[0] || '';
    }

    return props.message;
});
</script>

<template>
    <div v-show="displayMessage">
        <p class="text-sm text-red-600 dark:text-red-500">
            {{ displayMessage }}
        </p>
    </div>
</template>
