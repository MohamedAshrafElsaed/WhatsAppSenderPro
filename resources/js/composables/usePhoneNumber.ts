import { isValidPhoneNumber, parsePhoneNumber } from 'libphonenumber-js';
import { computed, ref, Ref, watch } from 'vue';

interface Country {
    id: number;
    phone_code: string;
    iso_code: string;
}

export function usePhoneNumber(
    selectedCountry: Ref<number | null>,
    countries: Country[],
) {
    const mobileNumber = ref('');
    const formattedNumber = ref('');

    /**
     * Get country phone code by country ID
     */
    const getPhoneCode = (countryId: number | null): string => {
        if (!countryId) return '';
        const country = countries.find((c) => c.id === countryId);
        return country?.phone_code || '';
    };

    /**
     * Get country ISO code by country ID
     */
    const getISOCode = (countryId: number | null): string => {
        if (!countryId) return '';
        const country = countries.find((c) => c.id === countryId);
        return country?.iso_code || '';
    };

    /**
     * Remove country code from phone number if user typed it
     */
    const removeCountryCode = (value: string, phoneCode: string): string => {
        if (!value || !phoneCode) return value;

        // Remove all non-digit characters except +
        let cleaned = value.replace(/[^\d+]/g, '');

        // Check if starts with country code (with or without +)
        const patterns = [
            new RegExp(`^\\+${phoneCode}`), // +20
            new RegExp(`^00${phoneCode}`), // 0020
            new RegExp(`^${phoneCode}`), // 20
        ];

        for (const pattern of patterns) {
            if (pattern.test(cleaned)) {
                cleaned = cleaned.replace(pattern, '');
                break;
            }
        }

        // Remove leading zeros
        cleaned = cleaned.replace(/^0+/, '');

        return cleaned;
    };

    /**
     * Format phone number for display
     */
    const formatPhoneNumber = (value: string, countryCode: string): string => {
        if (!value) return '';

        try {
            const phoneNumber = parsePhoneNumber(value, countryCode as any);
            if (phoneNumber) {
                return phoneNumber.formatNational();
            }
        } catch (error) {
            // If parsing fails, return the original value
        }

        return value;
    };

    /**
     * Validate phone number
     */
    const validatePhoneNumber = (
        value: string,
        countryCode: string,
    ): boolean => {
        if (!value || !countryCode) return false;

        try {
            // Add country code for validation
            const fullNumber = `+${getPhoneCode(selectedCountry.value)}${value}`;
            return isValidPhoneNumber(fullNumber, countryCode as any);
        } catch (error) {
            return false;
        }
    };

    /**
     * Computed: Check if current number is valid
     */
    const isValid = computed(() => {
        if (!mobileNumber.value || !selectedCountry.value) return false;
        const isoCode = getISOCode(selectedCountry.value);
        return validatePhoneNumber(mobileNumber.value, isoCode);
    });

    /**
     * Handle phone number input - FIXED to accept both Event and string
     */
    const handlePhoneInput = (eventOrValue: Event | string): string => {
        // Extract value from Event or use string directly
        const value =
            typeof eventOrValue === 'string'
                ? eventOrValue
                : (eventOrValue.target as HTMLInputElement).value;

        const phoneCode = getPhoneCode(selectedCountry.value);
        const isoCode = getISOCode(selectedCountry.value);

        // Remove country code if user typed it
        const cleanedNumber = removeCountryCode(value, phoneCode);

        // Update the actual value
        mobileNumber.value = cleanedNumber;

        // Format for display (optional)
        formattedNumber.value = formatPhoneNumber(cleanedNumber, isoCode);

        return cleanedNumber;
    };

    /**
     * Get full E164 formatted number
     */
    const getE164Number = (): string | null => {
        if (!mobileNumber.value || !selectedCountry.value) return null;

        const phoneCode = getPhoneCode(selectedCountry.value);
        if (!phoneCode) return null;

        return `+${phoneCode}${mobileNumber.value}`;
    };

    /**
     * Watch for country changes and revalidate
     */
    watch(selectedCountry, () => {
        if (mobileNumber.value) {
            handlePhoneInput(mobileNumber.value);
        }
    });

    return {
        mobileNumber,
        formattedNumber,
        isValid,
        handlePhoneInput,
        validatePhoneNumber,
        getE164Number,
    };
}
