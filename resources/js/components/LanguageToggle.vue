<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePage } from '@inertiajs/vue3';
import { Globe } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const currentLocale = computed(() => page.props.locale || 'ar');

const languages = [
    { code: 'en', name: 'English', nativeName: 'English', flag: '🇬🇧' },
    { code: 'ar', name: 'Arabic', nativeName: 'العربية', flag: '🇪🇬' },
];

const currentLanguage = computed(() =>
    languages.find((lang) => lang.code === currentLocale.value),
);

const switchLanguage = (locale: string) => {
    console.log('Current locale:', currentLocale.value);
    console.log('Switching to:', locale);

    if (locale === currentLocale.value) {
        console.log('Already on this locale, skipping');
        return;
    }

    // Force full page reload instead of Inertia navigation
    window.location.href = `/lang/${locale}`;
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                class="gap-2 transition-colors hover:border-[#25D366] hover:bg-[#25D366] hover:text-white"
                size="sm"
                variant="outline"
            >
                <Globe class="h-4 w-4" />
                <span class="hidden sm:inline">
                    {{ currentLanguage?.nativeName }}
                </span>
                <span class="sm:hidden">
                    {{ currentLocale.toUpperCase() }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="lang in languages"
                :key="lang.code"
                :class="{
                    'bg-accent': currentLocale === lang.code,
                }"
                class="cursor-pointer gap-2"
                @click="switchLanguage(lang.code)"
            >
                <span>{{ lang.flag }}</span>
                <span>{{ lang.nativeName }}</span>
                <span class="text-xs text-muted-foreground"
                    >({{ lang.name }})</span
                >
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
