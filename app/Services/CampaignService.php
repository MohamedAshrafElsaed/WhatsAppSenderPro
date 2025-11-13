<?php

namespace App\Services;

use App\Jobs\SendCampaignMessageJob;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

readonly class CampaignService
{
    public function __construct(
        private UsageTrackingService $usageTracking
    )
    {
    }

    /**
     * Create a new campaign
     * @throws \Throwable
     */
    public function createCampaign(User $user, array $data): Campaign
    {
        DB::beginTransaction();

        try {
            // Handle media upload if present
            if (isset($data['media']) && $data['media'] instanceof UploadedFile) {
                $mediaData = $this->handleMediaUpload($data['media'], $data['message_type']);
                $data['media_path'] = $mediaData['path'];
                $data['media_url'] = $mediaData['url'];
                unset($data['media']);
            }

            // Create campaign
            $campaign = $user->campaigns()->create($data);

            // Add recipients
            if (isset($data['recipient_ids']) && is_array($data['recipient_ids'])) {
                $this->addRecipientsToCampaign($campaign, $data['recipient_ids']);
            }

            DB::commit();

            Log::info("Campaign created", [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'recipients' => $campaign->total_recipients,
            ]);

            return $campaign->fresh();

        } catch (Exception $e) {
            DB::rollBack();

            // Clean up uploaded media if exists
            if (isset($data['media_path'])) {
                Storage::disk('public')->delete($data['media_path']);
            }

            throw $e;
        }
    }

    /**
     * Handle media file upload
     * @throws Exception
     */
    private function handleMediaUpload(UploadedFile $file, string $messageType): array
    {
        // Validate file based on message type
        $validationRules = $this->getMediaValidationRules($messageType);

        $maxSize = $validationRules['max_size'];
        $allowedMimes = $validationRules['mimes'];

        // Check file size (in KB)
        if ($file->getSize() > $maxSize * 1024) {
            throw new Exception("File size exceeds maximum allowed ({$maxSize}KB)");
        }

        // Check mime type
        if (!in_array($file->getClientOriginalExtension(), $allowedMimes)) {
            throw new Exception("Invalid file type for {$messageType}");
        }

        // Store file
        $path = $file->store('campaigns', 'public');
        $url = Storage::url($path);

        return [
            'path' => $path,
            'url' => $url,
        ];
    }

    /**
     * Get validation rules for media based on message type
     */
    private function getMediaValidationRules(string $messageType): array
    {
        return match ($messageType) {
            'image' => [
                'max_size' => 5120, // 5MB
                'mimes' => ['jpg', 'jpeg', 'png', 'gif'],
            ],
            'video' => [
                'max_size' => 16384, // 16MB
                'mimes' => ['mp4'],
            ],
            'audio' => [
                'max_size' => 16384, // 16MB
                'mimes' => ['mp3', 'ogg', 'wav'],
            ],
            'document' => [
                'max_size' => 10240, // 10MB
                'mimes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx'],
            ],
            default => [
                'max_size' => 0,
                'mimes' => [],
            ],
        };
    }

    /**
     * Add recipients to campaign
     */
    private function addRecipientsToCampaign(Campaign $campaign, array $contactIds): void
    {
        $recipients = [];
        foreach ($contactIds as $contactId) {
            $recipients[] = [
                'campaign_id' => $campaign->id,
                'contact_id' => $contactId,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        CampaignRecipient::insert($recipients);

        $campaign->update(['total_recipients' => count($recipients)]);
    }

    /**
     * Update a campaign
     * @throws Exception
     * @throws \Throwable
     */
    public function updateCampaign(Campaign $campaign, array $data): Campaign
    {
        // Only drafts can be edited
        if ($campaign->status !== 'draft') {
            throw new Exception('Only draft campaigns can be edited');
        }

        DB::beginTransaction();

        try {
            // Handle new media upload if present
            if (isset($data['media']) && $data['media'] instanceof UploadedFile) {
                // Delete old media
                if ($campaign->media_path) {
                    Storage::disk('public')->delete($campaign->media_path);
                }

                $mediaData = $this->handleMediaUpload($data['media'], $data['message_type']);
                $data['media_path'] = $mediaData['path'];
                $data['media_url'] = $mediaData['url'];
                unset($data['media']);
            }

            // If message type changed to text, remove media
            if (isset($data['message_type']) && $data['message_type'] === 'text' && $campaign->media_path) {
                Storage::disk('public')->delete($campaign->media_path);
                $data['media_path'] = null;
                $data['media_url'] = null;
            }

            // Update campaign
            $campaign->update($data);

            // Update recipients if provided
            if (isset($data['recipient_ids']) && is_array($data['recipient_ids'])) {
                $campaign->recipients()->delete();
                $this->addRecipientsToCampaign($campaign, $data['recipient_ids']);
            }

            DB::commit();

            return $campaign->fresh();

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Start sending campaign
     * @throws Exception
     */
    public function sendCampaign(Campaign $campaign): void
    {
        // Check if user can send messages
        if (!$this->usageTracking->canSendMessage($campaign->user)) {
            throw new Exception('Message quota exceeded for your subscription plan');
        }

        // Check remaining quota
        $remaining = $this->usageTracking->getRemainingQuota($campaign->user, 'messages_per_month');

        if ($remaining !== 'unlimited' && $remaining < $campaign->total_recipients) {
            throw new Exception("This campaign requires {$campaign->total_recipients} messages but you only have {$remaining} remaining.");
        }

        // Mark campaign as running
        $campaign->markAsStarted();

        // Get queue priority based on user's package
        $queuePriority = $this->getQueuePriority($campaign->user);

        // Dispatch jobs for each recipient
        $pendingRecipients = $campaign->recipients()->pending()->get();

        foreach ($pendingRecipients as $recipient) {
            SendCampaignMessageJob::dispatch($campaign, $recipient)
                ->onQueue($queuePriority);
        }

        Log::info("Campaign sending started", [
            'campaign_id' => $campaign->id,
            'recipients' => $pendingRecipients->count(),
            'queue' => $queuePriority,
        ]);
    }

    /**
     * Get queue priority based on user's subscription package
     */
    private function getQueuePriority(User $user): string
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return 'lowest'; // Free tier
        }

        $packageSlug = $subscription->package->slug ?? 'basic';

        return match ($packageSlug) {
            'golden' => 'highest',  // Golden: Highest priority, 0 delay
            'pro' => 'high',        // Pro: High priority
            'basic' => 'low',       // Basic: Low priority
            default => 'lowest',    // Free: Lowest priority
        };
    }

    /**
     * Pause a running campaign
     * @throws Exception
     */
    public function pauseCampaign(Campaign $campaign): void
    {
        if ($campaign->status !== 'running') {
            throw new Exception('Only running campaigns can be paused');
        }

        $campaign->pause();

        Log::info("Campaign paused", ['campaign_id' => $campaign->id]);
    }

    /**
     * Resume a paused campaign
     * @throws Exception
     */
    public function resumeCampaign(Campaign $campaign): void
    {
        if ($campaign->status !== 'paused') {
            throw new Exception('Only paused campaigns can be resumed');
        }

        $campaign->resume();

        // Re-queue pending messages
        $queuePriority = $this->getQueuePriority($campaign->user);
        $pendingRecipients = $campaign->recipients()->pending()->get();

        foreach ($pendingRecipients as $recipient) {
            SendCampaignMessageJob::dispatch($campaign, $recipient)
                ->onQueue($queuePriority);
        }

        Log::info("Campaign resumed", [
            'campaign_id' => $campaign->id,
            'pending_count' => $pendingRecipients->count(),
        ]);
    }

    /**
     * Delete a campaign
     * @throws Exception
     * @throws \Throwable
     */
    public function deleteCampaign(Campaign $campaign): void
    {
        DB::beginTransaction();

        try {
            // Delete media if exists
            if ($campaign->media_path) {
                Storage::disk('public')->delete($campaign->media_path);
            }

            // Delete recipients
            $campaign->recipients()->delete();

            // Delete campaign
            $campaign->delete();

            DB::commit();

            Log::info("Campaign deleted", ['campaign_id' => $campaign->id]);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
