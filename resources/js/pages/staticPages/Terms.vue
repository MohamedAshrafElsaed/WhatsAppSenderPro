<script lang="ts" setup>
import LanguageToggle from '@/components/LanguageToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import { home as landing } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { MessageCircle } from 'lucide-vue-next';

const { t } = useTranslation();
</script>

<template>
    <Head>
        <title>{{ t('static.terms.meta_title') }}</title>
        <meta
            :content="t('static.terms.meta_description')"
            name="description"
        />
    </Head>

    <div class="min-h-screen bg-background">
        <!-- Header/Navigation -->
        <header
            class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur"
        >
            <div
                class="container mx-auto flex h-16 items-center justify-between px-4"
            >
                <Link :href="landing()" class="flex items-center gap-2">
                    <div
                        class="flex items-center justify-center rounded-lg bg-[#25D366] p-2"
                    >
                        <MessageCircle class="size-6 text-white" />
                    </div>
                    <span class="text-xl font-bold">{{
                        t('landing.brand_name')
                    }}</span>
                </Link>
                <div class="flex items-center gap-3">
                    <LanguageToggle />
                    <Link :href="landing()">
                        <Button variant="outline">{{
                            t('static.back_home')
                        }}</Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section
            class="border-b border-border bg-gradient-to-br from-[#25D366]/10 to-transparent py-16"
        >
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-3xl text-center">
                    <Badge class="mb-4 bg-[#25D366]">{{
                        t('static.terms.badge')
                    }}</Badge>
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                        {{ t('static.terms.title') }}
                    </h1>
                    <p class="mt-6 text-lg text-muted-foreground">
                        {{ t('static.terms.subtitle') }}
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ t('static.terms.last_updated') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Terms Content -->
        <section class="py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-4xl">
                    <Card>
                        <CardContent
                            class="prose prose-gray dark:prose-invert max-w-none p-8"
                        >
                            <h2>{{ t('static.terms.section1_title') }}</h2>
                            <p>{{ t('static.terms.section1_text') }}</p>

                            <h2>{{ t('static.terms.section2_title') }}</h2>
                            <p>{{ t('static.terms.section2_text') }}</p>

                            <h2>{{ t('static.terms.section3_title') }}</h2>
                            <p>{{ t('static.terms.section3_text') }}</p>
                            <ul>
                                <li>{{ t('static.terms.section3_item1') }}</li>
                                <li>{{ t('static.terms.section3_item2') }}</li>
                                <li>{{ t('static.terms.section3_item3') }}</li>
                                <li>{{ t('static.terms.section3_item4') }}</li>
                            </ul>

                            <h2>{{ t('static.terms.section4_title') }}</h2>
                            <p>{{ t('static.terms.section4_text') }}</p>

                            <h2>{{ t('static.terms.section5_title') }}</h2>
                            <p>{{ t('static.terms.section5_text') }}</p>

                            <h2>{{ t('static.terms.section6_title') }}</h2>
                            <p>{{ t('static.terms.section6_text') }}</p>

                            <h2>{{ t('static.terms.section7_title') }}</h2>
                            <p>{{ t('static.terms.section7_text') }}</p>

                            <h2>{{ t('static.terms.section8_title') }}</h2>
                            <p>{{ t('static.terms.section8_text') }}</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border bg-muted/50 py-8">
            <div class="container mx-auto px-4">
                <div
                    class="flex flex-col items-center justify-between gap-4 sm:flex-row"
                >
                    <p class="text-sm text-muted-foreground">
                        {{ t('landing.footer.copyright') }}
                    </p>
                    <div class="flex gap-6 text-sm">
                        <Link href="/about" class="hover:text-[#25D366]">{{ t('static.about.link') }}</Link>
                        <Link href="/contact" class="hover:text-[#25D366]">{{ t('static.contact.link') }}</Link>
                        <Link href="/privacy" class="hover:text-[#25D366]">{{ t('static.privacy.link') }}</Link>
                        <Link href="/terms" class="hover:text-[#25D366]">{{ t('static.terms.link') }}</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
