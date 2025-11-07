<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useTranslation } from '@/composables/useTranslation';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import LanguageToggle from '@/components/LanguageToggle.vue';
import { MessageCircle, Mail, MapPin, Phone } from 'lucide-vue-next';

const { t } = useTranslation();
</script>

<template>
    <Head>
        <title>{{ t('static.contact.meta_title') }}</title>
        <meta :content="t('static.contact.meta_description')" name="description" />
    </Head>

    <div class="min-h-screen bg-background">
        <!-- Header/Navigation -->
        <header class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur">
            <div class="container mx-auto flex h-16 items-center justify-between px-4">
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex items-center justify-center rounded-lg bg-[#25D366] p-2">
                        <MessageCircle class="size-6 text-white" />
                    </div>
                    <span class="text-xl font-bold">{{ t('landing.brand_name') }}</span>
                </Link>
                <div class="flex items-center gap-3">
                    <LanguageToggle />
                    <Link href="/">
                        <Button variant="outline">{{ t('static.back_home') }}</Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="border-b border-border bg-gradient-to-br from-[#25D366]/10 to-transparent py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-3xl text-center">
                    <Badge class="mb-4 bg-[#25D366]">{{ t('static.contact.badge') }}</Badge>
                    <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                        {{ t('static.contact.title') }}
                    </h1>
                    <p class="mt-6 text-xl text-muted-foreground">
                        {{ t('static.contact.subtitle') }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Contact Content -->
        <section class="py-16">
            <div class="container mx-auto px-4">
                <div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-2">
                    <!-- Contact Form -->
                    <Card>
                        <CardHeader>
                            <CardTitle>{{ t('static.contact.form_title') }}</CardTitle>
                            <CardDescription>{{ t('static.contact.form_description') }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form class="space-y-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-2">
                                        <Label for="first_name">{{ t('static.contact.first_name') }}</Label>
                                        <Input id="first_name" :placeholder="t('static.contact.first_name_placeholder')" />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="last_name">{{ t('static.contact.last_name') }}</Label>
                                        <Input id="last_name" :placeholder="t('static.contact.last_name_placeholder')" />
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label for="email">{{ t('static.contact.email') }}</Label>
                                    <Input id="email" type="email" :placeholder="t('static.contact.email_placeholder')" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="phone">{{ t('static.contact.phone') }}</Label>
                                    <Input id="phone" type="tel" :placeholder="t('static.contact.phone_placeholder')" />
                                </div>
                                <div class="space-y-2">
                                    <Label for="message">{{ t('static.contact.message') }}</Label>
                                    <textarea
                                        id="message"
                                        class="flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        :placeholder="t('static.contact.message_placeholder')"
                                    ></textarea>
                                </div>
                                <Button class="w-full bg-[#25D366] hover:bg-[#128C7E]">
                                    {{ t('static.contact.send_button') }}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <!-- Contact Info -->
                    <div class="space-y-6">
                        <Card>
                            <CardHeader>
                                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-[#DCF8C6]">
                                    <Mail class="size-5 text-[#128C7E]" />
                                </div>
                                <CardTitle>{{ t('static.contact.email_title') }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-muted-foreground">{{ t('static.contact.email_text') }}</p>
                                <a href="mailto:support@convertedin.com" class="mt-2 block font-medium text-[#25D366] hover:underline">
                                    support@convertedin.com
                                </a>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-[#DCF8C6]">
                                    <Phone class="size-5 text-[#128C7E]" />
                                </div>
                                <CardTitle>{{ t('static.contact.phone_title') }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-muted-foreground">{{ t('static.contact.phone_text') }}</p>
                                <a href="tel:+201234567890" class="mt-2 block font-medium text-[#25D366] hover:underline">
                                    +20 123 456 7890
                                </a>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <div class="mb-3 flex size-10 items-center justify-center rounded-lg bg-[#DCF8C6]">
                                    <MapPin class="size-5 text-[#128C7E]" />
                                </div>
                                <CardTitle>{{ t('static.contact.location_title') }}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-muted-foreground">{{ t('static.contact.location_text') }}</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border bg-muted/50 py-8">
            <div class="container mx-auto px-4">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <p class="text-sm text-muted-foreground">{{ t('landing.footer.copyright') }}</p>
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
