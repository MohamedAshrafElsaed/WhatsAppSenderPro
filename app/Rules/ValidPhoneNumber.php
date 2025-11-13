<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;

class ValidPhoneNumber implements Rule
{
    private ?int $countryId;
    private string $errorMessage;

    public function __construct(?int $countryId = null)
    {
        $this->countryId = $countryId;
        $this->errorMessage = trans('validation.invalid_phone_number');
    }

    /**
     * Determine if the validation rule passes
     */
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return false;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $countryCode = $this->getCountryCode();

            // Parse the phone number with country context
            $numberProto = $phoneUtil->parse($value, $countryCode);

            // Check if it's a valid number
            if (!$phoneUtil->isValidNumber($numberProto)) {
                $this->errorMessage = trans('contacts.phone_validation.invalid_format');
                return false;
            }

            // Check if number matches the selected country (if country is provided)
            if ($this->countryId) {
                $numberCountry = $phoneUtil->getRegionCodeForNumber($numberProto);
                if ($numberCountry !== $countryCode) {
                    $this->errorMessage = trans('contacts.phone_validation.invalid_country');
                    return false;
                }
            }

            return true;

        } catch (NumberParseException $e) {
            $this->errorMessage = trans('contacts.phone_validation.invalid_format');
            return false;
        }
    }

    /**
     * Get country ISO code from country ID
     */
    private function getCountryCode(): string
    {
        if ($this->countryId) {
            $country = \App\Models\Country::find($this->countryId);
            return $country?->iso_code ?? 'EG';
        }

        return 'EG'; // Default to Egypt
    }

    /**
     * Get the validation error message
     */
    public function message(): string
    {
        return $this->errorMessage;
    }
}
