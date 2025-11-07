<script setup lang="ts">
import { MessageCircle } from 'lucide-vue-next';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-[#25D366] text-white"
    >
        <MessageCircle class="size-5" />
    </div>
    <div
        class="grid flex-1 text-left text-sm"
        :class="isRTL ? 'mr-1 text-right' : 'ml-1 text-left'"
    >
        <span class="mb-0.5 truncate font-semibold leading-tight">
            {{ isRTL ? 'واتساب سيندر برو' : 'WhatsApp Sender Pro' }}
        </span>
    </div>
</template>
