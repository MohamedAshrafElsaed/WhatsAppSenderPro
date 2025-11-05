import { parsePhoneNumber, isValidPhoneNumber } from 'libphonenumber-js';
import { ref, watch, Ref } from 'vue';

interface Country {
    id: number;
    phone_code: string;
    iso_code: string;
}

export function usePhoneNumber(selectedCountry: Ref<number | null>, countries: Country[]) {
    const mobileNumber = ref('');
    const formattedNumber = ref('');
    const isValid = ref(false);

    /**
     * Get country phone code by country ID
     */
    const getPhoneCode = (countryId: number | null): string => {
        if (!countryId) return '';
        const country = countries.find(c => c.id === countryId);
        return country?.phone_code || '';
    };

    /**
     * Get country ISO code by country ID
     */
    const getISOCode = (countryId: number | null): string => {
        if (!countryId) return '';
        const country = countries.find(c => c.id === countryId);
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
            new RegExp(`^\\+${phoneCode}`),  // +20
            new RegExp(`^00${phoneCode}`),   // 0020
            new RegExp(`^${phoneCode}`),     // 20
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
    const validatePhoneNumber = (value: string, countryCode: string): boolean => {
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
     * Handle phone number input
     */
    const handlePhoneInput = (value: string) => {
        const phoneCode = getPhoneCode(selectedCountry.value);
        const isoCode = getISOCode(selectedCountry.value);

        // Remove country code if user typed it
        const cleanedNumber = removeCountryCode(value, phoneCode);

        // Update the actual value
        mobileNumber.value = cleanedNumber;

        // Format for display (optional)
        formattedNumber.value = formatPhoneNumber(cleanedNumber, isoCode);

        // Validate
        isValid.value = validatePhoneNumber(cleanedNumber, isoCode);

        return cleanedNumber;
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
    };
}
