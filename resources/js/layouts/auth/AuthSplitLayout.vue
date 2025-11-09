<script lang="ts" setup>
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
        :class="[isRTL ? 'rtl' : 'ltr', isRTL ? 'lg:grid-flow-col-dense' : '']"
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            :class="[
                isRTL
                    ? 'lg:col-start-2 dark:border-r-0 dark:border-l'
                    : 'dark:border-r',
                'bg-[#128C7E]',
            ]"
            class="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r"
        >
            <div class="absolute inset-0 bg-[#128C7E]" />
            <Link
                :class="isRTL ? 'flex-row-reverse' : ''"
                :href="home()"
                class="relative z-20 flex items-center text-lg font-medium"
            >
                <AppLogoIcon
                    :class="isRTL ? 'ml-2' : 'mr-2'"
                    class="size-8 fill-current text-white"
                />
                {{ name }}
            </Link>
            <div
                v-if="quote"
                :class="isRTL ? 'text-right' : 'text-left'"
                class="relative z-20 mt-auto"
            >
                <blockquote class="space-y-2">
                    <p class="text-lg">
                        {{ isRTL ? '»' : '&ldquo;' }}{{ quote.message
                        }}{{ isRTL ? '«' : '&rdquo;' }}
                    </p>
                    <footer class="text-sm text-neutral-300">
                        {{ quote.author }}
                    </footer>
                </blockquote>
            </div>
        </div>
        <div :class="isRTL ? 'lg:col-start-1' : ''" class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div
                    :class="isRTL ? 'text-right' : 'text-left'"
                    class="flex flex-col space-y-2 text-center"
                >
                    <h1 v-if="title" class="text-xl font-medium tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
