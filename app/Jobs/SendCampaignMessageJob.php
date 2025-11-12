<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Services\BulkMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [30, 60, 120]; // Exponential backoff

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign,
        public CampaignRecipient $recipient
    ) {}

    /**
     * Execute the job.
     * @throws \Exception
     */
    public function handle(BulkMessageService $messageService): void
    {
        // Check if campaign is still running
        if (!in_array($this->campaign->status, ['running', 'scheduled'])) {
            Log::info("Campaign {$this->campaign->id} is not running, skipping message");
            return;
        }

        // Rate limiting: Max 30 messages per minute per user
        $rateLimitKey = "campaign_rate_limit:user:{$this->campaign->user_id}";
        $messagesSentInMinute = Cache::get($rateLimitKey, 0);

        if ($messagesSentInMinute >= 30) {
            // Too many messages, re-queue with delay
            $this->release(2); // Release back to queue after 2 seconds
            return;
        }

        try {
            // Send the message
            $success = $messageService->sendMessage(
                $this->campaign,
                $this->recipient
            );

            if ($success) {
                // Increment campaign counters
                $this->campaign->incrementSent();

                // Increment rate limit counter (expires after 60 seconds)
                Cache::put($rateLimitKey, $messagesSentInMinute + 1, 60);

                Log::info("Message sent successfully", [
                    'campaign_id' => $this->campaign->id,
                    'recipient_id' => $this->recipient->id,
                    'contact_id' => $this->recipient->contact_id,
                ]);
            } else {
                // Mark as failed
                $this->campaign->incrementFailed();

                Log::error("Failed to send message", [
                    'campaign_id' => $this->campaign->id,
                    'recipient_id' => $this->recipient->id,
                ]);
            }

            // Check if campaign is complete
            $this->checkCampaignCompletion();

        } catch (\Exception $e) {
            Log::error("Exception sending campaign message", [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'error' => $e->getMessage(),
            ]);

            // Mark recipient as failed
            $this->recipient->markAsFailed($e->getMessage());
            $this->campaign->incrementFailed();

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Check if all messages have been sent
     */
    private function checkCampaignCompletion(): void
    {
        $this->campaign->refresh();

        $totalProcessed = $this->campaign->messages_sent + $this->campaign->messages_failed;

        if ($totalProcessed >= $this->campaign->total_recipients) {
            $this->campaign->markAsCompleted();

            Log::info("Campaign completed", [
                'campaign_id' => $this->campaign->id,
                'total' => $this->campaign->total_recipients,
                'sent' => $this->campaign->messages_sent,
                'failed' => $this->campaign->messages_failed,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Campaign message job failed permanently", [
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'error' => $exception->getMessage(),
        ]);

        // Mark recipient as failed
        $this->recipient->markAsFailed($exception->getMessage());
        $this->campaign->incrementFailed();

        // Check if campaign should be marked as failed or completed
        $this->checkCampaignCompletion();
    }
}
