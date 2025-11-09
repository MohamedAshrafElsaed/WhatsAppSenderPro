<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

readonly class ContactService
{
    public function __construct(
        private WhatsAppApiService   $whatsappApi,
        private UsageTrackingService $usageTracking
    ) {}

    /**
     * Create a new contact
     * @throws \Throwable
     */
    public function createContact(User $user, array $data): Contact
    {
        return DB::transaction(function () use ($user, $data) {
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
     * Validate WhatsApp number using Go API
     */
    public function validateWhatsAppNumber(User $user, Contact $contact): bool
    {
        try {
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
            if ($isValid) {
                $this->usageTracking->trackContactValidated($user);
            }

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
        $contacts = Contact::whereIn('id', $contactIds)
            ->forUser($user)
            ->get();

        $results = [
            'total' => $contacts->count(),
            'valid' => 0,
            'invalid' => 0,
            'errors' => [],
        ];

        foreach ($contacts as $contact) {
            try {
                $isValid = $this->validateWhatsAppNumber($user, $contact);

                if ($isValid) {
                    $results['valid']++;
                } else {
                    $results['invalid']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'contact_id' => $contact->id,
                    'name' => $contact->full_name,
                    'error' => $e->getMessage(),
                ];
                $results['invalid']++;
            }
        }

        return $results;
    }

    /**
     * Sync contact tags
     */
    private function syncTags(Contact $contact, array $tagIds): void
    {
        // Get old tags before sync
        $oldTags = $contact->tags()->pluck('contact_tags.id')->toArray();

        // Sync tags
        $contact->tags()->sync($tagIds);

        // Update counts for affected tags
        $affectedTagIds = array_unique(array_merge($oldTags, $tagIds));

        foreach ($affectedTagIds as $tagId) {
            $tag = ContactTag::find($tagId);
            if ($tag) {
                $tag->updateContactsCount();
            }
        }
    }

    /**
     * Check if phone number is duplicate for user
     */
    public function isDuplicate(User $user, string $phoneNumber, ?int $excludeContactId = null): bool
    {
        $query = Contact::forUser($user)
            ->where('phone_number', $phoneNumber);

        if ($excludeContactId) {
            $query->where('id', '!=', $excludeContactId);
        }

        return $query->exists();
    }
}
