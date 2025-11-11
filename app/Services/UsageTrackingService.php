<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserUsage;

class UsageTrackingService
{
    /**
     * Track when a user sends a message
     */
    public function trackMessageSent(User $user, int $count = 1): void
    {
        UserUsage::incrementMessagesSent($user, $count);
    }

    /**
     * Track when a user validates contacts
     */
    public function trackContactValidated(User $user, int $count = 1): void
    {
        UserUsage::incrementContactsValidated($user, $count);
    }

    /**
     * Track when a user connects a WhatsApp number
     */
    public function trackConnectedNumber(User $user, int $count = 1): void
    {
        UserUsage::incrementConnectedNumbers($user, $count);
    }

    /**
     * Track when a user creates a template
     */
    public function trackTemplateCreated(User $user, int $count = 1): void
    {
        UserUsage::incrementTemplatesCreated($user, $count);
    }

    /**
     * Check if user can send a message based on their quota
     */
    public function canSendMessage(User $user): bool
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return false;
        }

        // Check if user has reached message limit
        return !$user->hasReachedLimit('messages_per_month');
    }

    /**
     * Check if user can validate contacts based on their quota
     */
    public function canValidateContact(User $user): bool
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return false;
        }

        return !$user->hasReachedLimit('contacts_validation_per_month');
    }

    /**
     * Check if user can connect more WhatsApp numbers
     */
    public function canConnectNumber(User $user): bool
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return false;
        }

        return !$user->hasReachedLimit('connected_numbers');
    }

    /**
     * Check if user can create more templates
     */
    public function canCreateTemplate(User $user): bool
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return false;
        }

        return !$user->hasReachedLimit('message_templates');
    }

    /**
     * Get remaining quota for a specific limit type
     */
    public function getRemainingQuota(User $user, string $type): int|string
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return 0;
        }

        return $user->getRemainingQuota($type);
    }

    /**
     * Get usage statistics for the current period
     */
    public function getCurrentUsageStats(User $user): array
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return [
                'messages' => ['used' => 0, 'limit' => 0, 'remaining' => 0],
                'contacts' => ['used' => 0, 'limit' => 0, 'remaining' => 0],
                'numbers' => ['used' => 0, 'limit' => 0, 'remaining' => 0],
                'templates' => ['used' => 0, 'limit' => 0, 'remaining' => 0],
            ];
        }

        $usage = UserUsage::getCurrentPeriodUsage($user);
        $package = $subscription->package;

        return [
            'messages' => [
                'used' => $usage->messages_sent,
                'limit' => $package->getLimit('messages_per_month'),
                'remaining' => $user->getRemainingQuota('messages_per_month'),
            ],
            'contacts' => [
                'used' => $usage->contacts_validated,
                'limit' => $package->getLimit('contacts_validation_per_month'),
                'remaining' => $user->getRemainingQuota('contacts_validation_per_month'),
            ],
            'numbers' => [
                'used' => $usage->connected_numbers_count,
                'limit' => $package->getLimit('connected_numbers'),
                'remaining' => $user->getRemainingQuota('connected_numbers'),
            ],
            'templates' => [
                'used' => $usage->templates_created,
                'limit' => $package->getLimit('message_templates'),
                'remaining' => $user->getRemainingQuota('message_templates'),
            ],
        ];
    }

    /**
     * Reset usage for a new period (monthly)
     * This should be run as a scheduled job at the start of each month
     */
    public function resetMonthlyUsage(): int
    {
        $resetCount = 0;
        $lastMonth = now()->subMonth();

        $usages = UserUsage::where('period_end', '<', now()->startOfMonth())->get();

        foreach ($usages as $usage) {
            // Create new period usage
            UserUsage::getCurrentPeriodUsage($usage->user);
            $resetCount++;
        }

        return $resetCount;
    }

    /**
     * Decrement connected number count when user disconnects
     */
    public function decrementConnectedNumber(User $user): void
    {
        $usage = UserUsage::getCurrentPeriodUsage($user);

        if ($usage->connected_numbers_count > 0) {
            $usage->decrement('connected_numbers_count');
        }
    }

    /**
     * Decrement template count when user deletes a template
     */
    public function decrementTemplateCount(User $user): void
    {
        $usage = UserUsage::getCurrentPeriodUsage($user);

        if ($usage->templates_created > 0) {
            $usage->decrement('templates_created');
        }
    }
}
