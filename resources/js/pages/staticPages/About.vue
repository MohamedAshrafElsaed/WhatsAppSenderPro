<script lang="ts" setup>
import LanguageToggle from '@/components/LanguageToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import { home, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import {
    Heart,
    MessageCircle,
    Shield,
    Target,
    Users,
    Zap,
} from 'lucide-vue-next';

const { t } = useTranslation();
</script>

<template>
    <Head>
        <title>{{ t('static.about.meta_title') }}</title>
        <meta
            :content="t('static.about.meta_description')"
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
                <Link :href="home()" class="flex items-center gap-2">
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
                    <Link :href="home()">
                        <Button variant="outline"
                            >{{ t('static.back_home') }}
                        </Button>
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
                    <Badge class="mb-4 bg-[#25D366]"
                        >{{ t('static.about.badge') }}
                    </Badge>
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                        {{ t('static.about.title') }}
                    </h1>
                    <p class="mt-6 text-xl text-muted-foreground">
                        {{ t('static.about.subtitle') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Mission & Vision -->
        <section class="py-16">
            <div class="container mx-auto px-4">
                <div class="grid gap-8 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <div
                                class="mb-4 flex size-12 items-center justify-center rounded-lg bg-[#DCF8C6]"
                            >
                                <Target class="size-6 text-[#128C7E]" />
                            </div>
                            <CardTitle class="text-2xl"
                                >{{ t('static.about.mission_title') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-muted-foreground">
                                {{ t('static.about.mission_text') }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div
                                class="mb-4 flex size-12 items-center justify-center rounded-lg bg-[#DCF8C6]"
                            >
                                <Heart class="size-6 text-[#128C7E]" />
                            </div>
                            <CardTitle class="text-2xl"
                                >{{ t('static.about.vision_title') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-muted-foreground">
                                {{ t('static.about.vision_text') }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Our Values -->
        <section class="border-t border-border bg-muted/50 py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto mb-12 max-w-2xl text-center">
                    <h2 class="text-3xl font-bold">
                        {{ t('static.about.values_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        {{ t('static.about.values_subtitle') }}
                    </p>
                </div>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <div
                                class="mb-3 flex size-10 items-center justify-center rounded-lg bg-[#25D366]"
                            >
                                <Shield class="size-5 text-white" />
                            </div>
                            <CardTitle
                                >{{ t('static.about.value1_title') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                {{ t('static.about.value1_text') }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div
                                class="mb-3 flex size-10 items-center justify-center rounded-lg bg-[#25D366]"
                            >
                                <Zap class="size-5 text-white" />
                            </div>
                            <CardTitle
                                >{{ t('static.about.value2_title') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                {{ t('static.about.value2_text') }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <div
                                class="mb-3 flex size-10 items-center justify-center rounded-lg bg-[#25D366]"
                            >
                                <Users class="size-5 text-white" />
                            </div>
                            <CardTitle
                                >{{ t('static.about.value3_title') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                {{ t('static.about.value3_text') }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="border-t border-border py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold">
                        {{ t('static.about.cta_title') }}
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        {{ t('static.about.cta_text') }}
                    </p>
                    <div class="mt-8">
                        <Link :href="register()">
                            <Button
                                class="bg-[#25D366] hover:bg-[#128C7E]"
                                size="lg"
                            >
                                {{ t('static.get_started') }}
                            </Button>
                        </Link>
                    </div>
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
                        <Link class="hover:text-[#25D366]" href="/about">{{
                            t('static.about.link')
                        }}</Link>
                        <Link class="hover:text-[#25D366]" href="/contact">{{
                            t('static.contact.link')
                        }}</Link>
                        <Link class="hover:text-[#25D366]" href="/privacy">{{
                            t('static.privacy.link')
                        }}</Link>
                        <Link class="hover:text-[#25D366]" href="/terms">{{
                            t('static.terms.link')
                        }}</Link>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
