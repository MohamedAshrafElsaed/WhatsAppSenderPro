<script setup lang="ts">
import { MessageCircle } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');

const appName = computed(() => {
    return isRTL.value ? 'واتساب سيندر برو' : 'WhatsApp Sender Pro';
});
</script>

<template>
    <div class="flex items-center gap-2">
        <div
            class="flex aspect-square size-8 items-center justify-center rounded-md bg-[#25D366] text-white"
        >
            <MessageCircle class="size-5" />
        </div>
        <div
            class="grid flex-1 text-sm leading-tight"
            :class="isRTL ? 'text-right' : 'text-left'"
        >
            <span class="truncate font-semibold">
                {{ appName }}
            </span>
        </div>
    </div>
</template>
