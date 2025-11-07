<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;
use App\Models\UserUsage;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class SubscriptionService
{
    /**
     * Create a trial subscription for a new user
     * @throws Throwable
     */
    public function createTrialSubscription(User $user, ?Package $package = null): UserSubscription
    {
        if (!$package) {
            $package = Package::where('slug', 'basic')->first();

            if (!$package) {
                throw new Exception('Default package not found');
            }
        }

        return DB::transaction(function () use ($user, $package) {
            $trialEndsAt = now()->addDays(7);

            $subscription = UserSubscription::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'status' => 'trial',
                'trial_ends_at' => $trialEndsAt,
                'starts_at' => now(),
                'ends_at' => $trialEndsAt,
                'auto_renew' => true,
            ]);

            // Initialize usage tracking
            UserUsage::getCurrentPeriodUsage($user);

            return $subscription;
        });
    }

    /**
     * Upgrade a user's subscription to a new package
     * @throws Throwable
     */
    public function upgradeSubscription(User $user, Package $package, Transaction $transaction): UserSubscription
    {
        return DB::transaction(function () use ($user, $package, $transaction) {
            // Cancel current subscription
            $currentSubscription = $user->activeSubscription();
            if ($currentSubscription) {
                $currentSubscription->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);
            }

            // Create new active subscription
            $startsAt = now();
            $endsAt = $startsAt->copy()->addMonth();

            return UserSubscription::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'status' => 'active',
                'trial_ends_at' => null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'auto_renew' => true,
            ]);
        });
    }

    /**
     * Cancel a subscription
     */
    public function cancelSubscription(UserSubscription $subscription): bool
    {
        return $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);
    }

    /**
     * Check and update expired subscriptions
     * This should be run as a scheduled job daily
     */
    public function checkExpiredSubscriptions(): array
    {
        $expiredCount = 0;
        $renewedCount = 0;

        $subscriptions = UserSubscription::whereIn('status', ['active', 'trial'])
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            if ($subscription->auto_renew && $subscription->status === 'active') {
                // Attempt auto-renewal (in real app, process payment here)
                try {
                    $this->renewSubscription($subscription);
                    $renewedCount++;
                } catch (Exception $e) {
                    // Payment failed, mark as expired
                    $subscription->update(['status' => 'expired']);
                    $expiredCount++;
                }
            } else {
                // Mark as expired
                $subscription->update(['status' => 'expired']);
                $expiredCount++;
            }
        }

        return [
            'expired' => $expiredCount,
            'renewed' => $renewedCount,
        ];
    }

    /**
     * Renew a subscription (for auto-renewal)
     * @throws Exception
     * @throws Throwable
     */
    public function renewSubscription(UserSubscription $subscription): UserSubscription
    {
        if (!$subscription->auto_renew) {
            throw new Exception('Subscription is not set to auto-renew');
        }

        return DB::transaction(function () use ($subscription) {
            $newEndsAt = $subscription->ends_at->copy()->addMonth();

            $subscription->update([
                'status' => 'active',
                'ends_at' => $newEndsAt,
            ]);

            return $subscription;
        });
    }

    /**
     * Convert trial to paid subscription
     * @throws Exception|Throwable
     */
    public function convertTrialToActive(UserSubscription $subscription, Transaction $transaction): UserSubscription
    {
        if ($subscription->status !== 'trial') {
            throw new Exception('Subscription is not a trial');
        }

        return DB::transaction(function () use ($subscription, $transaction) {
            $startsAt = now();
            $endsAt = $startsAt->copy()->addMonth();

            $subscription->update([
                'status' => 'active',
                'trial_ends_at' => null,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'auto_renew' => true,
            ]);

            return $subscription;
        });
    }

    /**
     * Get subscription summary for a user
     */
    public function getSubscriptionSummary(User $user): array
    {
        $subscription = $user->activeSubscription();

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'status' => 'none',
            ];
        }

        $package = $subscription->package;
        $usage = UserUsage::getCurrentPeriodUsage($user);

        return [
            'has_subscription' => true,
            'status' => $subscription->status,
            'package' => $package->name,
            'days_remaining' => $subscription->daysRemaining(),
            'ends_at' => $subscription->ends_at->toISOString(), // Convert to ISO string
            'is_trial' => $subscription->isTrial(),
            'usage' => [
                'messages_sent' => $usage->messages_sent,
                'messages_limit' => $package->getLimit('messages_per_month'),
                'contacts_validated' => $usage->contacts_validated,
                'contacts_limit' => $package->getLimit('contacts_validation_per_month'),
            ],
        ];
    }
}
