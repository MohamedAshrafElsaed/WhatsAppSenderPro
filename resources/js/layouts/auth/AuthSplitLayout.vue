<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const name = page.props.name;
const quote = page.props.quote;
const locale = computed(() => page.props.locale || 'en');
const isRTL = computed(() => locale.value === 'ar');

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
        :class="[isRTL ? 'rtl' : 'ltr', isRTL ? 'lg:grid-flow-col-dense' : '']"
    >
        <div
            class="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r"
            :class="[
                isRTL ? 'lg:col-start-2 dark:border-l dark:border-r-0' : 'dark:border-r',
                'bg-[#128C7E]'
            ]"
        >
            <div class="absolute inset-0 bg-[#128C7E]" />
            <Link
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
                :class="isRTL ? 'flex-row-reverse' : ''"
            >
                <AppLogoIcon
                    :class="isRTL ? 'ml-2' : 'mr-2'"
                    class="size-8 fill-current text-white"
                />
                {{ name }}
            </Link>
            <div
                v-if="quote"
                class="relative z-20 mt-auto"
                :class="isRTL ? 'text-right' : 'text-left'"
            >
                <blockquote class="space-y-2">
                    <p class="text-lg">
                        {{ isRTL ? '»' : '&ldquo;' }}{{ quote.message }}{{ isRTL ? '«' : '&rdquo;' }}
                    </p>
                    <footer class="text-sm text-neutral-300">
                        {{ quote.author }}
                    </footer>
                </blockquote>
            </div>
        </div>
        <div
            class="lg:p-8"
            :class="isRTL ? 'lg:col-start-1' : ''"
        >
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div
                    class="flex flex-col space-y-2 text-center"
                    :class="isRTL ? 'text-right' : 'text-left'"
                >
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-sm text-muted-foreground" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
