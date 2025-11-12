<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

readonly class BulkMessageService
{
    public function __construct(
        private WhatsAppApiService $whatsappApi
    ) {}

    /**
     * Send a message to a campaign recipient
     */
    public function sendMessage(Campaign $campaign, CampaignRecipient $recipient): bool
    {
        try {
            $contact = $recipient->contact;

            if (!$contact) {
                throw new Exception('Contact not found');
            }

            // Get WhatsApp session ID
            $sessionId = $campaign->session_id;
            if (!$sessionId) {
                throw new Exception('No WhatsApp session configured for this campaign');
            }

            // Personalize message with contact data
            $personalizedMessage = $this->personalizeMessage(
                $campaign->message_content,
                $contact
            );

            // Store personalized message
            $recipient->update(['personalized_message' => $personalizedMessage]);

            // Send based on message type
            if ($campaign->message_type === 'text') {
                return $this->sendTextMessage($campaign, $recipient, $contact, $personalizedMessage, $sessionId);
            } else {
                return $this->sendMediaMessage($campaign, $recipient, $contact, $personalizedMessage, $sessionId);
            }

        } catch (Exception $e) {
            Log::error("Failed to send campaign message", [
                'campaign_id' => $campaign->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);

            $recipient->markAsFailed($e->getMessage());
            return false;
        }
    }

    /**
     * Send text message via Go API
     * @throws Exception
     */
    private function sendTextMessage(
        Campaign $campaign,
        CampaignRecipient $recipient,
        Contact $contact,
        string $message,
        string $sessionId
    ): bool {
        try {
            // Use simple send endpoint for text messages
            $response = $this->whatsappApi->sendMessage(
                $campaign->user,
                $sessionId,
                $contact->phone_number,
                $message
            );

            if (isset($response['success']) && $response['success']) {
                // Extract message ID if available
                $messageId = $response['data']['message_id'] ?? uniqid('msg_');

                $recipient->markAsSent($messageId);

                // Mark as delivered immediately for now
                // In production, you'd listen to webhooks for delivery status
                $recipient->markAsDelivered();
                $campaign->incrementDelivered();

                return true;
            }

            throw new Exception('Failed to send message: ' . ($response['error'] ?? 'Unknown error'));

        } catch (Exception $e) {
            Log::error("WhatsApp API error", [
                'error' => $e->getMessage(),
                'campaign_id' => $campaign->id,
                'contact' => $contact->phone_number,
            ]);
            throw $e;
        }
    }

    /**
     * Send media message via Go API (image/video/audio/document)
     * @throws Exception
     */
    private function sendMediaMessage(
        Campaign $campaign,
        CampaignRecipient $recipient,
        Contact $contact,
        string $caption,
        string $sessionId
    ): bool {
        try {
            // Get media file
            if (!$campaign->media_path) {
                throw new Exception('No media file found');
            }

            // Read media file as base64
            $mediaContent = Storage::disk('public')->get($campaign->media_path);
            $mediaBase64 = base64_encode($mediaContent);

            // Determine media type
            $messageType = $campaign->message_type; // image, video, audio, document

            // Prepare request payload
            $payload = [
                'to' => $contact->phone_number,
                'message_type' => $messageType,
                'content' => [
                    'text' => $caption, // Caption for media
                    'media_base64' => $mediaBase64,
                ]
            ];

            // Add filename for documents
            if ($messageType === 'document') {
                $payload['content']['filename'] = basename($campaign->media_path);
                $payload['content']['mimetype'] = Storage::disk('public')->mimeType($campaign->media_path);
            }

            // Send via advanced endpoint
            $response = $this->whatsappApi->makeRequest(
                'post',
                "/api/v1/sessions/{$sessionId}/send-advanced",
                $campaign->user,
                $payload
            );

            if (isset($response['success']) && $response['success']) {
                $messageId = $response['data']['message_id'] ?? uniqid('msg_');

                $recipient->markAsSent($messageId);
                $recipient->markAsDelivered();
                $campaign->incrementDelivered();

                return true;
            }

            throw new Exception('Failed to send media message: ' . ($response['error'] ?? 'Unknown error'));

        } catch (Exception $e) {
            Log::error("WhatsApp media message error", [
                'error' => $e->getMessage(),
                'campaign_id' => $campaign->id,
                'contact' => $contact->phone_number,
                'media_type' => $campaign->message_type,
            ]);
            throw $e;
        }
    }

    /**
     * Personalize message with contact placeholders
     */
    private function personalizeMessage(string $message, Contact $contact): string
    {
        $placeholders = [
            '{{first_name}}' => $contact->first_name,
            '{{last_name}}' => $contact->last_name ?? '',
            '{{full_name}}' => $contact->full_name,
            '{{phone}}' => $contact->phone_number,
            '{{email}}' => $contact->email ?? '',
        ];

        // Add custom fields if available
        if ($contact->custom_fields && is_array($contact->custom_fields)) {
            foreach ($contact->custom_fields as $key => $value) {
                $placeholders["{{$key}}"] = $value;
            }
        }

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $message
        );
    }
}
