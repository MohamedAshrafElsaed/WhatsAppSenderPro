<?php

namespace App\Services;

use App\Models\User;

class OnboardingTrackingService
{
    /**
     * Mark a specific onboarding step as completed
     */
    public function markStepCompleted(User $user, string $step): void
    {
        $validSteps = [
            'tour_completed',
            'whatsapp_connected',
            'contacts_imported',
            'template_created',
            'campaign_sent',
        ];

        if (!in_array($step, $validSteps)) {
            return;
        }

        $onboardingData = $user->onboarding_data ?? [];

        // Only update if not already completed
        if (!isset($onboardingData[$step]) || !$onboardingData[$step]) {
            $onboardingData[$step] = true;
            $onboardingData['last_updated'] = now()->toISOString();

            $user->update(['onboarding_data' => $onboardingData]);
        }
    }

    /**
     * Check if a step is completed
     */
    public function isStepCompleted(User $user, string $step): bool
    {
        $onboardingData = $user->onboarding_data ?? [];
        return $onboardingData[$step] ?? false;
    }

    /**
     * Get onboarding progress percentage
     */
    public function getProgress(User $user): int
    {
        $onboardingData = $user->onboarding_data ?? [];
        $steps = [
            'tour_completed',
            'whatsapp_connected',
            'contacts_imported',
            'template_created',
            'campaign_sent',
        ];

        $completedCount = 0;
        foreach ($steps as $step) {
            if ($onboardingData[$step] ?? false) {
                $completedCount++;
            }
        }

        return round(($completedCount / count($steps)) * 100);
    }

    /**
     * Get all onboarding status
     */
    public function getStatus(User $user): array
    {
        $onboardingData = $user->onboarding_data ?? [];

        return [
            'tour_completed' => $onboardingData['tour_completed'] ?? false,
            'whatsapp_connected' => $onboardingData['whatsapp_connected'] ?? false,
            'contacts_imported' => $onboardingData['contacts_imported'] ?? false,
            'template_created' => $onboardingData['template_created'] ?? false,
            'campaign_sent' => $onboardingData['campaign_sent'] ?? false,
        ];
    }

    /**
     * Reset all onboarding progress (admin only)
     */
    public function reset(User $user): void
    {
        $user->update(['onboarding_data' => []]);
    }
}
