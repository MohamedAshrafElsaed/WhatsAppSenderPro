<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;
use App\Services\UsageTrackingService;

readonly class CampaignPolicy
{
    public function __construct(
        private UsageTrackingService $usageTracking
    ) {}

    /**
     * Determine whether the user can view any campaigns.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the campaign.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $campaign->user_id === $user->id;
    }

    /**
     * Determine whether the user can create campaigns.
     */
    public function create(User $user): bool
    {
        // Check if user has active subscription
        if (!$user->activeSubscription()) {
            return false;
        }

        // Check if user can send messages (quota not exceeded)
        return $this->usageTracking->canSendMessage($user);
    }

    /**
     * Determine whether the user can update the campaign.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        // Can only update own campaigns
        if ($campaign->user_id !== $user->id) {
            return false;
        }

        // Can only update draft campaigns
        return $campaign->status === 'draft';
    }

    /**
     * Determine whether the user can delete the campaign.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $campaign->user_id === $user->id;
    }

    /**
     * Determine whether the user can pause the campaign.
     */
    public function pause(User $user, Campaign $campaign): bool
    {
        if ($campaign->user_id !== $user->id) {
            return false;
        }

        return $campaign->status === 'running';
    }

    /**
     * Determine whether the user can resume the campaign.
     */
    public function resume(User $user, Campaign $campaign): bool
    {
        if ($campaign->user_id !== $user->id) {
            return false;
        }

        // Check quota before resuming
        if (!$this->usageTracking->canSendMessage($user)) {
            return false;
        }

        return $campaign->status === 'paused';
    }

    /**
     * Determine whether the user can send the campaign.
     */
    public function send(User $user, Campaign $campaign): bool
    {
        if ($campaign->user_id !== $user->id) {
            return false;
        }

        // Check quota before sending
        if (!$this->usageTracking->canSendMessage($user)) {
            return false;
        }

        // Can only send campaigns that are draft, scheduled, or paused
        return in_array($campaign->status, ['draft', 'scheduled', 'paused']);
    }
}
