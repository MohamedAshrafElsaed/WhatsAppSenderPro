<script lang="ts" setup>
import LanguageToggle from '@/components/LanguageToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslation } from '@/composables/useTranslation';
import { about, contact, privacy, terms } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import {
    BarChart,
    Check,
    CheckCircle2,
    Clock,
    Facebook,
    Globe,
    Instagram,
    Lock,
    MessageCircle,
    Send,
    Shield,
    Smartphone,
    Star,
    TrendingUp,
    Twitter,
    Upload,
    Users,
    X,
    Zap,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Package {
    id: number;
    name: string;
    name_en: string;
    name_ar: string;
    slug: string;
    price: number;
    formatted_price: string;
    features: Record<string, any>;
    limits: Record<string, any>;
    is_popular: boolean;
    is_best_value: boolean;
    color: string;
}

interface Props {
    packages: Package[];
    canRegister: boolean;
}

const props = defineProps<Props>();

const { t, isRTL } = useTranslation();

const features = computed(() => [
    {
        icon: Smartphone,
        title: t('landing.features.multi_device'),
        description: t('landing.features.multi_device_desc'),
    },
    {
        icon: Upload,
        title: t('landing.features.import_contacts'),
        description: t('landing.features.import_contacts_desc'),
    },
    {
        icon: MessageCircle,
        title: t('landing.features.personalized'),
        description: t('landing.features.personalized_desc'),
    },
    {
        icon: Shield,
        title: t('landing.features.rate_limiting'),
        description: t('landing.features.rate_limiting_desc'),
    },
    {
        icon: BarChart,
        title: t('landing.features.analytics'),
        description: t('landing.features.analytics_desc'),
    },
    {
        icon: Zap,
        title: t('landing.features.campaign'),
        description: t('landing.features.campaign_desc'),
    },
]);

const steps = computed(() => [
    {
        number: '1',
        title: t('landing.how_it_works.step1_title'),
        description: t('landing.how_it_works.step1_desc'),
        icon: Smartphone,
    },
    {
        number: '2',
        title: t('landing.how_it_works.step2_title'),
        description: t('landing.how_it_works.step2_desc'),
        icon: Upload,
    },
    {
        number: '3',
        title: t('landing.how_it_works.step3_title'),
        description: t('landing.how_it_works.step3_desc'),
        icon: Send,
    },
    {
        number: '4',
        title: t('landing.how_it_works.step4_title'),
        description: t('landing.how_it_works.step4_desc'),
        icon: TrendingUp,
    },
]);

const trustIndicators = computed(() => [
    {
        icon: Users,
        value: '10,000+',
        label: t('landing.trust.users'),
    },
    {
        icon: MessageCircle,
        value: '1M+',
        label: t('landing.trust.messages'),
    },
    {
        icon: Star,
        value: '4.8/5',
        label: t('landing.trust.rating'),
    },
    {
        icon: Globe,
        value: '50+',
        label: t('landing.trust.countries'),
    },
]);

const getFeatureTranslations = (
    packageSlug: string,
    features: Record<string, any>,
) => {
    const featureList: Array<{
        text: string;
        available: boolean;
        comingSoon: boolean;
    }> = [];

    if (packageSlug === 'basic') {
        featureList.push(
            {
                text: t('packages.basic.messages'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.basic.contacts'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.basic.numbers'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.basic.templates'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.basic.features.csv_import'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.basic.features.text_messages'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.basic.features.basic_reports'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.basic.features.manual_sending'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.basic.features.browser_only'),
                available: true,
                comingSoon: false,
            },
        );
    } else if (packageSlug === 'pro') {
        featureList.push(
            {
                text: t('packages.pro.messages'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.contacts'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.numbers'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.templates'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.features.media'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.features.groups'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.features.tagging'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.features.scheduling'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.features.dashboard'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.features.sheets'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.pro.features.priority_support'),
                available: true,
                comingSoon: false,
            },
        );
    } else if (packageSlug === 'golden') {
        featureList.push(
            {
                text: t('packages.golden.messages'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.contacts'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.numbers'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.templates'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.features.full_media'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.features.drip'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.features.advanced_segmentation'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.features.inbox'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.features.unsubscribe'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.features.teams'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.features.api'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.features.analytics'),
                available: true,
                comingSoon: false,
            },
            {
                text: t('packages.golden.features.premium_support'),
                available: true,
                comingSoon: false,
            },
        );
    }

    return featureList;
};

const getBadgeText = (pkg: Package) => {
    if (pkg.is_popular) {
        return t('subscription.popular');
    }
    if (pkg.is_best_value) {
        return t('subscription.best_value');
    }
    return '';
};
</script>
<template>
    <Head>
        <title>{{ t('landing.meta.title') }}</title>
        <meta :content="t('landing.meta.description')" name="description" />
    </Head>

    <div :class="{ rtl: isRTL }" class="min-h-screen bg-background">
        <!-- Header/Navigation -->
        <header
            class="sticky top-0 z-50 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60"
        >
            <div
                class="container mx-auto flex h-16 items-center justify-between px-4"
            >
                <div class="flex items-center gap-2">
                    <div
                        class="flex items-center justify-center rounded-lg bg-[#25D366] p-2"
                    >
                        <MessageCircle class="size-6 text-white" />
                    </div>
                    <span class="text-xl font-bold">{{
                        t('landing.brand_name')
                    }}</span>
                </div>
                <nav class="hidden items-center gap-6 md:flex">
                    <a
                        class="text-sm font-medium transition-colors hover:text-[#25D366]"
                        href="#features"
                    >
                        {{ t('landing.nav.features') }}
                    </a>
                    <a
                        class="text-sm font-medium transition-colors hover:text-[#25D366]"
                        href="#pricing"
                    >
                        {{ t('landing.nav.pricing') }}
                    </a>
                    <a
                        class="text-sm font-medium transition-colors hover:text-[#25D366]"
                        href="#how-it-works"
                    >
                        {{ t('landing.nav.how_it_works') }}
                    </a>
                </nav>
                <div class="flex items-center gap-3">
                    <!-- Language Toggle - NEW! -->
                    <LanguageToggle />
                    <Link
                        class="hidden text-sm font-medium md:inline-flex"
                        href="/login"
                    >
                        {{ t('landing.nav.login') }}
                    </Link>
                    <Link v-if="canRegister" href="/register">
                        <Button class="bg-[#25D366] hover:bg-[#128C7E]">
                            {{ t('landing.nav.get_started') }}
                        </Button>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-16 md:py-24">
            <div class="grid items-center gap-8 lg:grid-cols-2">
                <div class="space-y-6">
                    <Badge
                        class="bg-[#DCF8C6] text-[#128C7E] hover:bg-[#DCF8C6]/80"
                    >
                        {{ t('landing.hero.badge') }}
                    </Badge>
                    <h1
                        class="text-4xl font-bold tracking-tight sm:text-5xl md:text-6xl"
                    >
                        {{ t('landing.hero.title') }}
                    </h1>
                    <p class="text-lg text-muted-foreground md:text-xl">
                        {{ t('landing.hero.subtitle') }}
                    </p>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link v-if="canRegister" href="/register">
                            <Button
                                class="bg-[#25D366] hover:bg-[#128C7E]"
                                size="lg"
                            >
                                {{ t('landing.hero.cta_primary') }}
                            </Button>
                        </Link>
                        <Button
                            size="lg"
                            variant="outline"
                            @click="
                                () =>
                                    document
                                        .getElementById('pricing')
                                        ?.scrollIntoView({ behavior: 'smooth' })
                            "
                        >
                            {{ t('landing.hero.cta_secondary') }}
                        </Button>
                    </div>
                    <div class="flex flex-wrap items-center gap-6 pt-4">
                        <div
                            v-for="indicator in trustIndicators"
                            :key="indicator.label"
                            class="flex items-center gap-2"
                        >
                            <component
                                :is="indicator.icon"
                                class="size-5 text-[#25D366]"
                            />
                            <div>
                                <div class="text-xl font-bold">
                                    {{ indicator.value }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ indicator.label }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div
                        class="relative rounded-2xl border border-border bg-gradient-to-br from-[#DCF8C6] to-[#25D366]/20 p-8"
                    >
                        <div class="space-y-4">
                            <div
                                class="flex items-center gap-3 rounded-lg bg-white p-4 shadow-sm dark:bg-gray-900"
                            >
                                <MessageCircle class="size-8 text-[#25D366]" />
                                <div class="flex-1">
                                    <div class="text-sm font-medium">
                                        {{ t('landing.hero.demo.title') }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ t('landing.hero.demo.subtitle') }}
                                    </div>
                                </div>
                                <CheckCircle2 class="size-5 text-[#25D366]" />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-900"
                                >
                                    <div
                                        class="text-2xl font-bold text-[#25D366]"
                                    >
                                        1000
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ t('landing.hero.demo.devices') }}
                                    </div>
                                </div>
                                <div
                                    class="rounded-lg bg-white p-4 shadow-sm dark:bg-gray-900"
                                >
                                    <div
                                        class="text-2xl font-bold text-[#25D366]"
                                    >
                                        99.9%
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ t('landing.hero.demo.success') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section
            id="features"
            class="border-t border-border bg-muted/50 py-16 md:py-24"
        >
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl text-center">
                    <Badge class="mb-4"
                        >{{ t('landing.features.badge') }}
                    </Badge>
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        {{ t('landing.features.title') }}
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        {{ t('landing.features.subtitle') }}
                    </p>
                </div>
                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Card
                        v-for="feature in features"
                        :key="feature.title"
                        class="transition-all hover:shadow-md"
                    >
                        <CardHeader>
                            <div
                                class="mb-4 flex size-12 items-center justify-center rounded-lg bg-[#DCF8C6]"
                            >
                                <component
                                    :is="feature.icon"
                                    class="size-6 text-[#128C7E]"
                                />
                            </div>
                            <CardTitle class="text-xl"
                                >{{ feature.title }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <CardDescription class="text-base"
                                >{{ feature.description }}
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section id="how-it-works" class="py-16 md:py-24">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl text-center">
                    <Badge class="mb-4"
                        >{{ t('landing.how_it_works.badge') }}
                    </Badge>
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        {{ t('landing.how_it_works.title') }}
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        {{ t('landing.how_it_works.subtitle') }}
                    </p>
                </div>
                <div class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="step in steps"
                        :key="step.number"
                        class="relative"
                    >
                        <div class="flex flex-col items-center text-center">
                            <div
                                class="mb-4 flex size-16 items-center justify-center rounded-full bg-[#25D366] text-2xl font-bold text-white"
                            >
                                {{ step.number }}
                            </div>
                            <component
                                :is="step.icon"
                                class="mb-4 size-8 text-[#25D366]"
                            />
                            <h3 class="mb-2 text-xl font-semibold">
                                {{ step.title }}
                            </h3>
                            <p class="text-muted-foreground">
                                {{ step.description }}
                            </p>
                        </div>
                        <div
                            v-if="Number(step.number) < 4"
                            :class="isRTL ? 'right-full' : 'left-full'"
                            class="absolute top-8 hidden w-full lg:block"
                        >
                            <div
                                :class="isRTL ? 'rotate-180' : ''"
                                class="h-0.5 w-full bg-gradient-to-r from-[#25D366] to-transparent"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section
            id="pricing"
            class="border-t border-border bg-muted/50 py-16 md:py-24"
        >
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl text-center">
                    <Badge class="mb-4">{{ t('landing.pricing.badge') }}</Badge>
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        {{ t('landing.pricing.title') }}
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        {{ t('landing.pricing.subtitle') }}
                    </p>
                </div>

                <!-- Free Trial Highlight -->
                <div class="mx-auto mt-8 max-w-2xl">
                    <Card
                        class="border-2 border-[#25D366] bg-gradient-to-br from-[#DCF8C6]/20 to-transparent"
                    >
                        <CardHeader>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <Clock class="size-8 text-[#25D366]" />
                                    <div>
                                        <CardTitle class="text-2xl"
                                            >{{
                                                t('landing.pricing.free_trial')
                                            }}
                                        </CardTitle>
                                        <CardDescription class="text-base"
                                            >{{
                                                t(
                                                    'landing.pricing.free_trial_desc',
                                                )
                                            }}
                                        </CardDescription>
                                    </div>
                                </div>
                                <Link v-if="canRegister" href="/register">
                                    <Button
                                        class="bg-[#25D366] hover:bg-[#128C7E]"
                                    >
                                        {{ t('landing.pricing.start_trial') }}
                                    </Button>
                                </Link>
                            </div>
                        </CardHeader>
                    </Card>
                </div>

                <!-- Pricing Plans - FIXED VERSION -->
                <div class="mt-12 grid gap-8 lg:grid-cols-3">
                    <Card
                        v-for="pkg in packages"
                        :key="pkg.id"
                        :class="
                            pkg.is_popular
                                ? 'border-2 border-[#25D366] shadow-lg'
                                : ''
                        "
                        class="relative flex flex-col transition-all hover:shadow-lg"
                    >
                        <!-- Badge - Fixed positioning to not overlap title -->
                        <Badge
                            v-if="pkg.is_popular || pkg.is_best_value"
                            :class="isRTL ? 'top-4 left-4' : 'top-4 right-4'"
                            class="absolute bg-[#25D366]"
                        >
                            {{ getBadgeText(pkg) }}
                        </Badge>

                        <!-- Header with extra padding if badge exists -->
                        <CardHeader
                            :class="
                                pkg.is_popular || pkg.is_best_value
                                    ? isRTL
                                        ? 'pl-24'
                                        : 'pr-24'
                                    : ''
                            "
                        >
                            <CardTitle class="text-2xl"
                                >{{ pkg.name }}
                            </CardTitle>
                            <div class="mt-4">
                                <span class="text-4xl font-bold">{{
                                    pkg.formatted_price
                                }}</span>
                            </div>
                        </CardHeader>

                        <!-- Content - Flex grow to push button to bottom -->
                        <CardContent class="flex flex-1 flex-col space-y-6">
                            <div class="flex-1 space-y-3">
                                <div
                                    class="text-sm font-semibold text-muted-foreground"
                                >
                                    {{ t('landing.pricing.core_features') }}
                                </div>
                                <ul class="space-y-2">
                                    <li
                                        v-for="(
                                            feature, index
                                        ) in getFeatureTranslations(
                                            pkg.slug,
                                            pkg.features,
                                        )"
                                        :key="index"
                                        class="flex items-start gap-2"
                                    >
                                        <Check
                                            v-if="
                                                feature.available &&
                                                !feature.comingSoon
                                            "
                                            class="mt-0.5 size-5 shrink-0 text-[#25D366]"
                                        />
                                        <X
                                            v-else
                                            class="mt-0.5 size-5 shrink-0 text-muted-foreground"
                                        />
                                        <span
                                            :class="
                                                !feature.available ||
                                                feature.comingSoon
                                                    ? 'text-muted-foreground'
                                                    : ''
                                            "
                                            class="text-sm"
                                        >
                                            {{ feature.text }}
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Button - Fixed at bottom -->
                            <div class="pt-4">
                                <Link
                                    v-if="canRegister"
                                    class="block"
                                    href="/register"
                                >
                                    <Button
                                        :class="
                                            pkg.is_popular
                                                ? 'bg-[#25D366] hover:bg-[#128C7E]'
                                                : ''
                                        "
                                        :variant="
                                            pkg.is_popular
                                                ? 'default'
                                                : 'outline'
                                        "
                                        class="w-full"
                                    >
                                        {{ t('landing.pricing.choose_plan') }}
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Trust Section -->
        <section class="border-t border-border py-16 md:py-24">
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-4xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        {{ t('landing.trust.title') }}
                    </h2>
                    <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="item in [
                                {
                                    icon: Lock,
                                    title: t('landing.trust.secure'),
                                },
                                {
                                    icon: Shield,
                                    title: t('landing.trust.ban_protection'),
                                },
                                { icon: Star, title: t('landing.trust.rated') },
                                {
                                    icon: Users,
                                    title: t('landing.trust.support'),
                                },
                            ]"
                            :key="item.title"
                            class="flex flex-col items-center"
                        >
                            <div
                                class="mb-4 flex size-16 items-center justify-center rounded-full bg-[#DCF8C6]"
                            >
                                <component
                                    :is="item.icon"
                                    class="size-8 text-[#128C7E]"
                                />
                            </div>
                            <h3 class="font-semibold">{{ item.title }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section
            class="border-t border-border bg-gradient-to-br from-[#25D366]/10 to-transparent py-16 md:py-24"
        >
            <div class="container mx-auto px-4">
                <div class="mx-auto max-w-2xl text-center">
                    <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                        {{ t('landing.cta.title') }}
                    </h2>
                    <p class="mt-4 text-lg text-muted-foreground">
                        {{ t('landing.cta.subtitle') }}
                    </p>
                    <div
                        class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center"
                    >
                        <Link v-if="canRegister" href="/register">
                            <Button
                                class="bg-[#25D366] hover:bg-[#128C7E]"
                                size="lg"
                            >
                                {{ t('landing.cta.start_free') }}
                            </Button>
                        </Link>
                        <Button size="lg" variant="outline">
                            {{ t('landing.cta.contact_sales') }}
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border bg-muted/50 py-12">
            <div class="container mx-auto px-4">
                <div class="grid gap-8 sm:grid-cols-2 md:grid-cols-4">
                    <div>
                        <div class="mb-4 flex items-center gap-2">
                            <div
                                class="flex items-center justify-center rounded-lg bg-[#25D366] p-2"
                            >
                                <MessageCircle class="size-5 text-white" />
                            </div>
                            <span class="font-bold">{{
                                t('landing.brand_name')
                            }}</span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ t('landing.footer.tagline') }}
                        </p>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold">
                            {{ t('landing.footer.product') }}
                        </h3>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li>
                                <a
                                    class="hover:text-foreground"
                                    href="#features"
                                    >{{ t('landing.nav.features') }}</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-foreground"
                                    href="#pricing"
                                    >{{ t('landing.nav.pricing') }}</a
                                >
                            </li>
                            <li>
                                <a
                                    class="hover:text-foreground"
                                    href="#how-it-works"
                                    >{{ t('landing.nav.how_it_works') }}</a
                                >
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold">
                            {{ t('landing.footer.company') }}
                        </h3>
                        <ul class="space-y-2 text-sm text-muted-foreground">
                            <li>
                                <Link
                                    :href="about()"
                                    class="hover:text-foreground"
                                    >{{ t('static.about.link') }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="contact()"
                                    class="hover:text-foreground"
                                    >{{ t('static.contact.link') }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="privacy()"
                                    class="hover:text-foreground"
                                    >{{ t('static.privacy.link') }}
                                </Link>
                            </li>
                            <li>
                                <Link
                                    :href="terms()"
                                    class="hover:text-foreground"
                                    >{{ t('static.terms.link') }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="mb-4 font-semibold">
                            {{ t('landing.footer.follow_us') }}
                        </h3>
                        <div class="flex gap-3">
                            <a
                                class="flex size-10 items-center justify-center rounded-full bg-muted transition-colors hover:bg-[#25D366] hover:text-white"
                                href="#"
                            >
                                <Facebook class="size-5" />
                            </a>
                            <a
                                class="flex size-10 items-center justify-center rounded-full bg-muted transition-colors hover:bg-[#25D366] hover:text-white"
                                href="#"
                            >
                                <Twitter class="size-5" />
                            </a>
                            <a
                                class="flex size-10 items-center justify-center rounded-full bg-muted transition-colors hover:bg-[#25D366] hover:text-white"
                                href="#"
                            >
                                <Instagram class="size-5" />
                            </a>
                        </div>
                    </div>
                </div>
                <div
                    class="mt-8 border-t border-border pt-8 text-center text-sm text-muted-foreground"
                >
                    <p>{{ t('landing.footer.copyright') }}</p>
                </div>
            </div>
        </footer>
    </div>
</template>
