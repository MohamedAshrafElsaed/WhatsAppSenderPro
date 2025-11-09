import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useTranslation() {
    const page = usePage();

    const locale = computed(() => page.props.locale || 'en');
    const translations = computed(() => page.props.translations || {});

    /**
     * Translate a key with optional fallback
     * Supports dot notation for nested keys
     */
    const t = (
        key: string,
        fallback?: string,
        replacements?: Record<string, any>,
    ): string => {
        const keys = key.split('.');
        let value: any = translations.value;

        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                value = fallback || key;
                break;
            }
        }

        let result = typeof value === 'string' ? value : fallback || key;

        // Handle replacements like {name}, {count}, etc.
        if (replacements) {
            Object.keys(replacements).forEach((replaceKey) => {
                const replaceValue = replacements[replaceKey];
                result = result.replace(
                    new RegExp(`{${replaceKey}}`, 'g'),
                    replaceValue,
                );
            });
        }

        return result;
    };

    /**
     * Check if current locale is RTL
     */
    const isRTL = (): boolean => {
        return locale.value === 'ar';
    };

    /**
     * Get current locale
     */
    const getLocale = (): string => {
        return <string>locale.value;
    };

    /**
     * Get text direction based on locale
     */
    const getDirection = (): 'rtl' | 'ltr' => {
        return isRTL() ? 'rtl' : 'ltr';
    };

    return {
        t,
        isRTL,
        getLocale,
        getDirection,
        locale,
    };
}
