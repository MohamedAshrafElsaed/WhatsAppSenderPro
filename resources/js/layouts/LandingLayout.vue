<script lang="ts" setup>
import LanguageToggle from '@/components/LanguageToggle.vue';
import { useTranslation } from '@/composables/useTranslation';
import { Link } from '@inertiajs/vue3';
import { MessageCircle } from 'lucide-vue-next';

const { t, isRTL } = useTranslation();
</script>

<template>
    <div :class="{ rtl: isRTL() }" class="min-h-screen bg-background">
        <!-- Simple Header (No Dashboard Navigation) -->
        <header
            class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div
                class="container mx-auto flex h-16 items-center justify-between px-4"
            >
                <!-- Logo -->
                <Link class="flex items-center gap-2" href="/">
                    <div
                        class="flex items-center justify-center rounded-lg bg-[#25D366] p-2"
                    >
                        <MessageCircle class="size-6 text-white" />
                    </div>
                    <span class="text-xl font-bold">{{
                        t('landing.brand_name', 'WA Sender Pro')
                    }}</span>
                </Link>

                <!-- Language Toggle -->
                <div class="flex items-center gap-3">
                    <LanguageToggle />
                </div>
            </div>
        </header>

        <!-- Content -->
        <main>
            <slot />
        </main>

        <!-- Simple Footer -->
        <footer class="border-t border-border bg-muted/50 py-8">
            <div class="container mx-auto px-4 text-center">
                <p class="text-sm text-muted-foreground">
                    © 2024 {{ t('landing.brand_name', 'WA Sender Pro') }}.
                    {{ t('landing.footer.rights', 'All rights reserved.') }}
                </p>
            </div>
        </footer>
    </div>
</template>
