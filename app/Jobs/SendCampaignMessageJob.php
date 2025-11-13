<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Notifications\CampaignCompletedNotification;
use App\Services\BulkMessageService;
use App\Services\UsageTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     * Exponential backoff: 30s, 60s, 120s
     */
    public array $backoff = [30, 60, 120];

    /**
     * The maximum number of seconds the job should run.
     */
    public int $timeout = 120;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Campaign $campaign,
        public CampaignRecipient $recipient,
        public int $attempt = 1
    ) {
        // Set queue priority based on user's subscription tier
        $this->onQueue($this->getQueuePriority());
    }

    /**
     * Execute the job with comprehensive error handling and rate limiting
     */
    public function handle(BulkMessageService $messageService, UsageTrackingService $usageTracking): void
    {
        // Check if campaign is still active
        if (!in_array($this->campaign->status, ['running', 'scheduled'])) {
            Log::info('Campaign not active, skipping message', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'status' => $this->campaign->status,
            ]);
            return;
        }

        // Check if recipient already processed
        if (in_array($this->recipient->status, ['sent', 'delivered', 'read'])) {
            Log::info('Recipient already processed, skipping', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'status' => $this->recipient->status,
            ]);
            return;
        }

        // Rate limiting: 30 messages per minute per user
        $rateLimitKey = "campaign_rate_limit:user_{$this->campaign->user_id}";
        $messagesInLastMinute = Cache::get($rateLimitKey, 0);

        if ($messagesInLastMinute >= 30) {
            Log::warning('Rate limit exceeded, releasing job back to queue', [
                'user_id' => $this->campaign->user_id,
                'campaign_id' => $this->campaign->id,
                'messages_in_last_minute' => $messagesInLastMinute,
            ]);

            // Release the job back to the queue with a delay
            $this->release(60); // Wait 1 minute before retry
            return;
        }

        try {
            // Load contact with fresh data
            $contact = Contact::find($this->recipient->contact_id);

            if (!$contact) {
                $this->handleFailure('Contact not found', 'permanent');
                return;
            }

            // Validate contact has valid WhatsApp number
            if (!$contact->is_whatsapp_valid) {
                $this->handleFailure('Contact does not have a valid WhatsApp number', 'permanent');
                return;
            }

            // Log sending attempt
            Log::info('Sending campaign message', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'contact_id' => $contact->id,
                'phone_number' => $contact->phone_number,
                'attempt' => $this->attempt,
            ]);

            // Send message via BulkMessageService
            $success = $messageService->sendMessage(
                $this->campaign,
                $this->recipient
            );

            if ($success) {
                // Update rate limit counter
                Cache::put($rateLimitKey, $messagesInLastMinute + 1, now()->addMinutes(1));

                // Handle successful send
                $this->handleSuccess();

                // Track usage
                $usageTracking->trackUsage(
                    $this->campaign->user,
                    'messages_per_month',
                    1
                );

                // Check if campaign is complete
                $this->checkCampaignCompletion();
            } else {
                // Handle failure
                $this->handleFailure('Message send returned false', 'unknown');
            }

        } catch (\GuzzleHttp\Exception\TooManyRequestsException $e) {
            // WhatsApp API rate limit hit
            Log::warning('WhatsApp API rate limit hit', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'error' => $e->getMessage(),
            ]);

            // Exponential backoff with jitter
            $delay = min(300, $this->backoff[$this->attempts - 1] ?? 60) + rand(10, 30);
            $this->release($delay);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // 4xx errors (client errors) - usually permanent failures
            Log::error('Client error sending message', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);

            $this->handleFailure($e->getMessage(), 'permanent');

        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // 5xx errors (server errors) - retry
            Log::error('Server error sending message', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'status_code' => $e->getResponse()->getStatusCode(),
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts < $this->tries) {
                $delay = $this->backoff[$this->attempts - 1] ?? 60;
                $this->release($delay);
            } else {
                $this->handleFailure($e->getMessage(), 'temporary');
            }

        } catch (\Exception $e) {
            // General exception
            Log::error('Error sending campaign message', [
                'campaign_id' => $this->campaign->id,
                'recipient_id' => $this->recipient->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->attempts < $this->tries) {
                $delay = $this->backoff[$this->attempts - 1] ?? 60;
                $this->release($delay);
            } else {
                $this->handleFailure($e->getMessage(), 'unknown');
            }
        }
    }

    /**
     * Handle successful message send
     */
    protected function handleSuccess(): void
    {
        DB::transaction(function () {
            $this->recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
                'retry_count' => $this->attempt,
                'error_message' => null,
            ]);

            // Update campaign sent count
            $this->campaign->increment('messages_sent');
        });

        Log::info('Message sent successfully', [
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
        ]);
    }

    /**
     * Handle failed message send
     */
    protected function handleFailure(string $error, string $errorType): void
    {
        DB::transaction(function () use ($error, $errorType) {
            $this->recipient->update([
                'status' => 'failed',
                'error_message' => substr($error, 0, 500), // Limit error message length
                'error_type' => $errorType, // 'permanent', 'temporary', or 'unknown'
                'retry_count' => $this->attempt,
            ]);

            // Update campaign failed count
            $this->campaign->increment('messages_failed');
        });

        Log::error('Message send failed', [
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'error' => $error,
            'error_type' => $errorType,
            'attempt' => $this->attempt,
        ]);

        // Check if campaign is complete after failure
        $this->checkCampaignCompletion();
    }

    /**
     * Check if campaign is complete and notify user
     */
    protected function checkCampaignCompletion(): void
    {
        // Use cache lock to prevent multiple checks
        $lockKey = "campaign_completion_check:{$this->campaign->id}";

        Cache::lock($lockKey, 10)->block(5, function () {
            // Refresh campaign to get latest data
            $this->campaign->refresh();

            // Count pending recipients
            $pendingCount = CampaignRecipient::where('campaign_id', $this->campaign->id)
                ->where('status', 'pending')
                ->count();

            // If no pending recipients, mark campaign as completed
            if ($pendingCount === 0 && $this->campaign->status === 'running') {
                DB::transaction(function () {
                    $this->campaign->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);

                    // Calculate final statistics
                    $stats = CampaignRecipient::where('campaign_id', $this->campaign->id)
                        ->selectRaw('
                            COUNT(*) as total,
                            SUM(CASE WHEN status IN ("sent", "delivered", "read") THEN 1 ELSE 0 END) as sent,
                            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed
                        ')
                        ->first();

                    // Log completion
                    Log::info('Campaign completed', [
                        'campaign_id' => $this->campaign->id,
                        'campaign_name' => $this->campaign->name,
                        'total_recipients' => $stats->total,
                        'sent' => $stats->sent,
                        'delivered' => $stats->delivered,
                        'failed' => $stats->failed,
                        'success_rate' => $stats->sent > 0 ? round(($stats->delivered / $stats->sent) * 100, 2) : 0,
                    ]);

                    // Notify user via notification (if notification class exists)
                    try {
                        if (class_exists(CampaignCompletedNotification::class)) {
                            $this->campaign->user->notify(
                                new CampaignCompletedNotification($this->campaign, [
                                    'total' => $stats->total,
                                    'sent' => $stats->sent,
                                    'delivered' => $stats->delivered,
                                    'failed' => $stats->failed,
                                ])
                            );
                        }
                    } catch (\Exception $e) {
                        Log::error('Failed to send campaign completion notification', [
                            'campaign_id' => $this->campaign->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    // Activity log (if activity helper exists)
                    if (function_exists('activity')) {
                        activity()
                            ->causedBy($this->campaign->user)
                            ->performedOn($this->campaign)
                            ->withProperties([
                                'campaign_name' => $this->campaign->name,
                                'stats' => $stats,
                            ])
                            ->log('campaign_completed');
                    }
                });
            }
        });
    }

    /**
     * Get queue priority based on user's subscription tier
     */
    protected function getQueuePriority(): string
    {
        $user = $this->campaign->user;
        $subscription = $user->activeSubscription ?? null;

        if (!$subscription) {
            return 'campaigns-low'; // Free tier
        }

        return match ($subscription->package->slug ?? 'basic') {
            'golden' => 'campaigns-highest',
            'pro' => 'campaigns-high',
            'basic' => 'campaigns-medium',
            default => 'campaigns-low',
        };
    }

    /**
     * Handle a job failure after all retries exhausted
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendCampaignMessageJob failed permanently', [
            'campaign_id' => $this->campaign->id,
            'recipient_id' => $this->recipient->id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Mark as failed in database
        $this->handleFailure($exception->getMessage(), 'job_failed');
    }
}
