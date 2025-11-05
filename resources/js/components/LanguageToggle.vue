<script setup lang="ts">
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Globe } from 'lucide-vue-next';

const page = usePage();
const currentLocale = computed(() => page.props.locale || 'ar');

const languages = [
    { code: 'en', name: 'English', nativeName: 'English', flag: '🇬🇧' },
    { code: 'ar', name: 'Arabic', nativeName: 'العربية', flag: '🇪🇬' },
];

const currentLanguage = computed(() =>
    languages.find(lang => lang.code === currentLocale.value)
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
                variant="outline"
                size="sm"
                class="gap-2 hover:bg-[#25D366] hover:text-white hover:border-[#25D366] transition-colors"
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
                @click="switchLanguage(lang.code)"
                :class="{
                    'bg-accent': currentLocale === lang.code,
                }"
                class="cursor-pointer gap-2"
            >
                <span>{{ lang.flag }}</span>
                <span>{{ lang.nativeName }}</span>
                <span class="text-muted-foreground text-xs">({{ lang.name }})</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
