<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    /**
     * Mark onboarding step as completed
     */
    public function completeStep(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'step' => 'required|string|in:tour_completed,whatsapp_connected,contacts_imported,template_created,campaign_sent',
        ]);

        $user = auth()->user();

        $onboardingData = $user->onboarding_data ?? [];
        $onboardingData[$validated['step']] = true;
        $onboardingData['completed_at'] = now()->toISOString();

        $user->update(['onboarding_data' => $onboardingData]);

        return response()->json([
            'success' => true,
            'message' => __('onboarding.step_completed'),
        ]);
    }

    /**
     * Get onboarding status
     */
    public function status(): JsonResponse
    {
        $user = auth()->user();
        $onboardingData = $user->onboarding_data ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'tour_completed' => $onboardingData['tour_completed'] ?? false,
                'whatsapp_connected' => $onboardingData['whatsapp_connected'] ?? false,
                'contacts_imported' => $onboardingData['contacts_imported'] ?? false,
                'template_created' => $onboardingData['template_created'] ?? false,
                'campaign_sent' => $onboardingData['campaign_sent'] ?? false,
            ],
        ]);
    }
}
