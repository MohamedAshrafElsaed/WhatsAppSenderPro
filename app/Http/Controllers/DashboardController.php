<?php

namespace App\Http\Controllers;

use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    /**
     * Display the dashboard with subscription and usage data
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get subscription summary
        $subscriptionSummary = $this->subscriptionService->getSubscriptionSummary($user);

        return Inertia::render('Dashboard', [
            'subscription' => [],
        ]);
    }
}
