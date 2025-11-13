<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    )
    {
    }

    /**
     * Display reports dashboard
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get date range from request or default to last 30 days
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        // Get overview statistics
        $overview = $this->reportService->getOverviewStats($user, $startDate, $endDate);

        // Get campaign performance
        $campaignPerformance = $this->reportService->getCampaignPerformance($user, $startDate, $endDate);

        // Get daily message trends
        $messageTrends = $this->reportService->getMessageTrends($user, $startDate, $endDate);

        // Get contact growth
        $contactGrowth = $this->reportService->getContactGrowth($user, $startDate, $endDate);

        return Inertia::render('reports/Index', [
            'overview' => $overview,
            'campaignPerformance' => $campaignPerformance,
            'messageTrends' => $messageTrends,
            'contactGrowth' => $contactGrowth,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    /**
     * Get contact reports
     */
    public function contacts(Request $request): Response
    {
        $user = $request->user();

        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $contactStats = $this->reportService->getContactStats($user, $startDate, $endDate);
        $contactSources = $this->reportService->getContactSources($user);
        $topTags = $this->reportService->getTopContactTags($user);

        return Inertia::render('reports/Contacts', [
            'stats' => $contactStats,
            'sources' => $contactSources,
            'topTags' => $topTags,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    /**
     * Get campaign reports
     */
    public function campaigns(Request $request): Response
    {
        $user = $request->user();

        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $campaignStats = $this->reportService->getCampaignStats($user, $startDate, $endDate);
        $topCampaigns = $this->reportService->getTopPerformingCampaigns($user, $startDate, $endDate);
        $campaignsByStatus = $this->reportService->getCampaignsByStatus($user, $startDate, $endDate);

        return Inertia::render('reports/Campaigns', [
            'stats' => $campaignStats,
            'topCampaigns' => $topCampaigns,
            'campaignsByStatus' => $campaignsByStatus,
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ]);
    }

    /**
     * Get usage reports
     */
    public function usage(Request $request): Response
    {
        $user = $request->user();

        $currentUsage = $this->reportService->getCurrentUsage($user);
        $usageHistory = $this->reportService->getUsageHistory($user, 6); // Last 6 months

        return Inertia::render('reports/Usage', [
            'currentUsage' => $currentUsage,
            'usageHistory' => $usageHistory,
        ]);
    }

    /**
     * Export reports
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $user = $request->user();
        $reportType = $request->input('type', 'overview');
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        return $this->reportService->exportReport($user, $reportType, $startDate, $endDate);
    }
}
