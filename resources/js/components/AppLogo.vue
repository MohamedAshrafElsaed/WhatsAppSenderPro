<script lang="ts" setup>
import { usePage } from '@inertiajs/vue3';
import { MessageCircle } from 'lucide-vue-next';
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
            :class="isRTL ? 'text-right' : 'text-left'"
            class="grid flex-1 text-sm leading-tight"
        >
            <span class="truncate font-semibold">
                {{ appName }}
            </span>
        </div>
    </div>
</template>
