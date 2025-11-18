<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\WhatsAppApiService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Log;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly WhatsAppApiService  $whatsappApi
    )
    {
    }

    /**
     * Display the dashboard with subscription, usage, and onboarding data
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get subscription summary
        $subscriptionSummary = $this->subscriptionService->getSubscriptionSummary($user);

        // Get onboarding status
        $onboardingData = $user->onboarding_data ?? [];
        $onboardingStatus = [
            'tour_completed' => $onboardingData['tour_completed'] ?? false,
            'whatsapp_connected' => $onboardingData['whatsapp_connected'] ?? false,
            'contacts_imported' => $onboardingData['contacts_imported'] ?? false,
            'template_created' => $onboardingData['template_created'] ?? false,
            'campaign_sent' => $onboardingData['campaign_sent'] ?? false,
        ];

        // Get WhatsApp sessions summary
        $whatsappSummary = ['connected' => 0, 'total' => 0];
        try {
            $sessionsResponse = $this->whatsappApi->getSessions($user);
            if (isset($sessionsResponse['data']['summary'])) {
                $whatsappSummary = [
                    'connected' => $sessionsResponse['data']['summary']['connected'] ?? 0,
                    'total' => $sessionsResponse['data']['summary']['total_sessions'] ?? 0,
                ];
            }

            // Auto-update onboarding status if WhatsApp is connected
            if ($whatsappSummary['connected'] > 0 && !$onboardingStatus['whatsapp_connected']) {
                $onboardingData['whatsapp_connected'] = true;
                $user->update(['onboarding_data' => $onboardingData]);
                $onboardingStatus['whatsapp_connected'] = true;
            }
        } catch (Exception $e) {
            // If WhatsApp API is not available, continue without it
            Log::warning('WhatsApp API not available for dashboard', ['error' => $e->getMessage()]);
        }

        // Calculate onboarding progress
        $completedSteps = array_filter($onboardingStatus);
        $progressPercentage = (count($completedSteps) / count($onboardingStatus)) * 100;

        return Inertia::render('Dashboard', [
            'subscription' => $subscriptionSummary,
            'onboarding' => [
                'status' => $onboardingStatus,
                'progress' => round($progressPercentage),
                'completed_count' => count($completedSteps),
                'total_steps' => count($onboardingStatus),
            ],
            'whatsapp' => $whatsappSummary,
        ]);
    }
}
