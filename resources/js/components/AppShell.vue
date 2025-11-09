<script lang="ts" setup>
import { SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>();

const page = usePage();
const isOpen = page.props.sidebarOpen ?? true;
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider
        v-else
        :class="isRTL ? 'rtl' : 'ltr'"
        :default-open="isOpen"
        :dir="isRTL ? 'rtl' : 'ltr'"
    >
        <slot />
    </SidebarProvider>
</template>
