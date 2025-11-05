import { usePage } from '@inertiajs/vue3';

export function useTranslation() {
    const page = usePage();

    /**
     * Translate a key using dot notation
     * @param key - Translation key (e.g., 'landing.features.badge')
     * @param fallback - Optional fallback text if key not found
     * @returns Translated string
     */
    const t = (key: string, fallback?: string): string => {
        const translations = (page.props.translations as Record<string, any>) || {};

        // Split the key by dots (e.g., 'landing.features.badge' -> ['landing', 'features', 'badge'])
        const keys = key.split('.');

        // Navigate through the nested object
        let value: any = translations;
        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                // Key not found, return fallback or original key
                return fallback || key;
            }
        }

        // Return the value or fallback/key if undefined
        return (typeof value === 'string' ? value : null) || fallback || key;
    };

    /**
     * Get current locale
     */
    const locale = () => {
        return (page.props.locale as string) || 'en';
    };

    /**
     * Check if current locale is RTL
     */
    const isRTL = () => {
        return locale() === 'ar';
    };

    return {
        t,
        locale,
        isRTL
    };
}
