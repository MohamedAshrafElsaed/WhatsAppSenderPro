<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\NumberParseException;

readonly class ContactService
{
    public function __construct(
        private WhatsAppApiService   $whatsappApi,
        private UsageTrackingService $usageTracking
    ) {}

    /**
     * Normalize phone number to E164 format (+201xxxxxxxxx)
     */
    public function normalizePhoneNumber(
        string $phoneNumber,
        ?int $countryId = null
    ): ?string {
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();

            // Get country ISO code
            $countryISO = $this->getCountryISOCode($countryId);

            // Parse number with country context
            $numberProto = $phoneUtil->parse($phoneNumber, $countryISO);

            // Validate it's a valid number
            if (!$phoneUtil->isValidNumber($numberProto)) {
                Log::warning('Invalid phone number during normalization', [
                    'phone' => $phoneNumber,
                    'country_id' => $countryId,
                ]);
                return null;
            }

            // Return in E164 format (+201xxxxxxxxx)
            return $phoneUtil->format($numberProto, PhoneNumberFormat::E164);

        } catch (NumberParseException $e) {
            Log::warning('Phone normalization failed', [
                'phone' => $phoneNumber,
                'country_id' => $countryId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get country ISO code from country ID
     */
    private function getCountryISOCode(?int $countryId): string
    {
        if (!$countryId) {
            return 'EG'; // Default to Egypt
        }

        $country = \App\Models\Country::find($countryId);
        return $country?->iso_code ?? 'EG';
    }

    /**
     * Validate if phone number format is correct
     */
    public function validatePhoneFormat(string $phoneNumber, ?int $countryId = null): bool
    {
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $countryISO = $this->getCountryISOCode($countryId);
            $numberProto = $phoneUtil->parse($phoneNumber, $countryISO);
            return $phoneUtil->isValidNumber($numberProto);
        } catch (NumberParseException $e) {
            return false;
        }
    }

    /**
     * Create a new contact
     * @throws \Throwable
     */
    public function createContact(User $user, array $data): Contact
    {
        return DB::transaction(function () use ($user, $data) {
            // Normalize phone number if not already normalized
            if (!empty($data['phone_number']) && !str_starts_with($data['phone_number'], '+')) {
                $normalized = $this->normalizePhoneNumber(
                    $data['phone_number'],
                    $data['country_id'] ?? null
                );

                if ($normalized) {
                    $data['phone_number'] = $normalized;
                }
            }

            $contact = Contact::create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone_number' => $data['phone_number'],
                'email' => $data['email'] ?? null,
                'country_id' => $data['country_id'] ?? null,
                'source' => $data['source'] ?? 'manual',
                'import_id' => $data['import_id'] ?? null,
                'custom_fields' => $data['custom_fields'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Sync tags if provided
            if (!empty($data['tags'])) {
                $this->syncTags($contact, $data['tags']);
            }

            // Validate WhatsApp if requested
            if (!empty($data['validate_whatsapp'])) {
                $this->validateWhatsAppNumber($user, $contact);
            }

            return $contact->load(['tags', 'country']);
        });
    }

    /**
     * Update an existing contact
     * @throws \Throwable
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        return DB::transaction(function () use ($contact, $data) {
            // Normalize phone number if changed and not already normalized
            if (!empty($data['phone_number']) &&
                $data['phone_number'] !== $contact->phone_number &&
                !str_starts_with($data['phone_number'], '+')) {

                $normalized = $this->normalizePhoneNumber(
                    $data['phone_number'],
                    $data['country_id'] ?? $contact->country_id
                );

                if ($normalized) {
                    $data['phone_number'] = $normalized;
                }
            }

            $contact->update([
                'first_name' => $data['first_name'] ?? $contact->first_name,
                'last_name' => $data['last_name'] ?? $contact->last_name,
                'phone_number' => $data['phone_number'] ?? $contact->phone_number,
                'email' => $data['email'] ?? $contact->email,
                'country_id' => $data['country_id'] ?? $contact->country_id,
                'custom_fields' => $data['custom_fields'] ?? $contact->custom_fields,
                'notes' => $data['notes'] ?? $contact->notes,
            ]);

            // Sync tags if provided
            if (isset($data['tags'])) {
                $this->syncTags($contact, $data['tags']);
            }

            // Validate WhatsApp if requested and phone changed
            if (!empty($data['validate_whatsapp']) &&
                $contact->wasChanged('phone_number')) {
                $this->validateWhatsAppNumber($contact->user, $contact);
            }

            return $contact->load(['tags', 'country']);
        });
    }

    /**
     * Delete a contact
     * @throws \Throwable
     */
    public function deleteContact(Contact $contact): bool
    {
        return DB::transaction(function () use ($contact) {
            // Update tag counts
            foreach ($contact->tags as $tag) {
                $tag->updateContactsCount();
            }

            return $contact->delete();
        });
    }

    /**
     * Check if phone number is duplicate
     */
    public function isDuplicate(
        User $user,
        string $phoneNumber,
        ?int $excludeContactId = null
    ): bool {
        // Normalize the phone number for comparison
        $normalized = $this->normalizePhoneNumber($phoneNumber);
        if (!$normalized) {
            $normalized = $phoneNumber;
        }

        $query = Contact::forUser($user)
            ->where('phone_number', $normalized);

        if ($excludeContactId) {
            $query->where('id', '!=', $excludeContactId);
        }

        return $query->exists();
    }

    /**
     * Sync tags for a contact
     */
    private function syncTags(Contact $contact, array $tagIds): void
    {
        // Get old tag IDs before sync
        $oldTagIds = $contact->tags->pluck('id')->toArray();

        // Sync tags
        $contact->tags()->sync($tagIds);

        // Update counts for old and new tags
        $affectedTagIds = array_unique(array_merge($oldTagIds, $tagIds));
        foreach ($affectedTagIds as $tagId) {
            $tag = ContactTag::find($tagId);
            if ($tag) {
                $tag->updateContactsCount();
            }
        }
    }

    /**
     * Validate WhatsApp number using Go API
     * Only validates if user has an active WhatsApp session
     */
    public function validateWhatsAppNumber(User $user, Contact $contact): bool
    {
        try {
            // Check if user has an active WhatsApp session
            $hasActiveSession = \App\Models\WhatsAppSession::where('user_id', $user->id)
                ->where('status', 'connected')
                ->where('is_active', true)
                ->exists();

            if (!$hasActiveSession) {
                Log::info('Skipping WhatsApp validation - no active session', [
                    'user_id' => $user->id,
                    'contact_id' => $contact->id,
                ]);
                return false;
            }

            // Check usage limits
            if (!$this->usageTracking->canValidateContact($user)) {
                Log::warning('Contact validation limit reached', [
                    'user_id' => $user->id,
                    'contact_id' => $contact->id,
                ]);
                return false;
            }

            // Call WhatsApp API
            $response = $this->whatsappApi->validatePhoneNumber(
                $user,
                $contact->phone_number
            );

            $isValid = $response['success'] ?? false;
            $jid = $response['data']['jid'] ?? null;

            // Update contact
            $contact->update([
                'is_whatsapp_valid' => $isValid,
                'whatsapp_jid' => $jid,
                'validated_at' => now(),
            ]);

            // Track usage
            $this->usageTracking->trackContactValidation($user);

            return $isValid;

        } catch (\Exception $e) {
            Log::error('WhatsApp validation failed', [
                'user_id' => $user->id,
                'contact_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Bulk validate WhatsApp numbers
     */
    public function bulkValidateWhatsApp(User $user, array $contactIds): array
    {
        $results = [
            'valid' => 0,
            'invalid' => 0,
        ];

        $contacts = Contact::forUser($user)
            ->whereIn('id', $contactIds)
            ->get();

        foreach ($contacts as $contact) {
            if ($this->validateWhatsAppNumber($user, $contact)) {
                $results['valid']++;
            } else {
                $results['invalid']++;
            }
        }

        return $results;
    }

    /**
     * Get all contacts for a user
     */
    public function getAll(User $user)
    {
        return Contact::forUser($user)
            ->with(['tags', 'country'])
            ->latest()
            ->paginate(20);
    }
}
