<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\User;
use App\Models\UserUsage;
use Illuminate\Support\Facades\DB;

readonly class ReportService
{
    /**
     * Get detailed contact statistics
     */
    public function getContactStats(User $user, string $startDate, string $endDate): array
    {
        $totalContacts = Contact::forUser($user)->count();

        $newContacts = Contact::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $validatedContacts = Contact::forUser($user)
            ->where('is_whatsapp_valid', true)
            ->count();

        $invalidContacts = Contact::forUser($user)
            ->where('is_whatsapp_valid', false)
            ->count();

        return [
            'total' => $totalContacts,
            'new' => $newContacts,
            'validated' => $validatedContacts,
            'invalid' => $invalidContacts,
            'validation_rate' => $totalContacts > 0 ? round(($validatedContacts / $totalContacts) * 100, 2) : 0,
        ];
    }

    /**
     * Get contact sources distribution
     */
    public function getContactSources(User $user): array
    {
        return Contact::forUser($user)
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'source' => $item->source,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get top contact tags
     */
    public function getTopContactTags(User $user): array
    {
        return DB::table('contact_tags')
            ->where('user_id', $user->id)
            ->select('name', 'contacts_count')
            ->orderBy('contacts_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'count' => $item->contacts_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get campaign statistics
     */
    public function getCampaignStats(User $user, string $startDate, string $endDate): array
    {
        $totalCampaigns = Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $completedCampaigns = Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        $runningCampaigns = Campaign::forUser($user)
            ->where('status', 'running')
            ->count();

        $totalRecipients = Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_recipients');

        $totalDelivered = Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('messages_delivered');

        return [
            'total' => $totalCampaigns,
            'completed' => $completedCampaigns,
            'running' => $runningCampaigns,
            'total_recipients' => $totalRecipients,
            'total_delivered' => $totalDelivered,
            'completion_rate' => $totalCampaigns > 0 ? round(($completedCampaigns / $totalCampaigns) * 100, 2) : 0,
        ];
    }

    /**
     * Get top performing campaigns
     */
    public function getTopPerformingCampaigns(User $user, string $startDate, string $endDate): array
    {
        return Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('messages_sent', '>', 0)
            ->select([
                'id',
                'name',
                'total_recipients',
                'messages_delivered',
                'messages_sent',
            ])
            ->orderByRaw('(messages_delivered / messages_sent) DESC')
            ->limit(10)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'total_recipients' => $campaign->total_recipients,
                    'messages_delivered' => $campaign->messages_delivered,
                    'success_rate' => $campaign->success_rate,
                ];
            })
            ->toArray();
    }

    /**
     * Get campaigns grouped by status
     */
    public function getCampaignsByStatus(User $user, string $startDate, string $endDate): array
    {
        return Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    /**
     * Get current usage statistics
     */
    public function getCurrentUsage(User $user): array
    {
        $currentPeriod = UserUsage::where('user_id', $user->id)
            ->where('period_start', '<=', now())
            ->where('period_end', '>=', now())
            ->first();

        if (!$currentPeriod) {
            return [
                'messages_sent' => 0,
                'contacts_validated' => 0,
                'templates_created' => 0,
                'connected_numbers' => 0,
            ];
        }

        return [
            'messages_sent' => $currentPeriod->messages_sent,
            'contacts_validated' => $currentPeriod->contacts_validated,
            'templates_created' => $currentPeriod->templates_created,
            'connected_numbers' => $currentPeriod->connected_numbers_count,
        ];
    }

    /**
     * Get usage history for last N months
     */
    public function getUsageHistory(User $user, int $months = 6): array
    {
        return UserUsage::where('user_id', $user->id)
            ->where('period_start', '>=', now()->subMonths($months))
            ->orderBy('period_start')
            ->get()
            ->map(function ($usage) {
                return [
                    'period' => $usage->period_start->format('M Y'),
                    'messages_sent' => $usage->messages_sent,
                    'contacts_validated' => $usage->contacts_validated,
                    'templates_created' => $usage->templates_created,
                ];
            })
            ->toArray();
    }

    /**
     * Export report to CSV
     */
    public function exportReport(User $user, string $reportType, string $startDate, string $endDate): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = match ($reportType) {
            'campaigns' => $this->getCampaignPerformance($user, $startDate, $endDate),
            'contacts' => $this->getContactGrowth($user, $startDate, $endDate),
            'messages' => $this->getMessageTrends($user, $startDate, $endDate),
            default => $this->getOverviewStats($user, $startDate, $endDate),
        };

        $filename = "whatsapp-report-{$reportType}-" . now()->format('Y-m-d') . ".csv";

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Write headers
            if (!empty($data)) {
                if (isset($data[0])) {
                    fputcsv($file, array_keys($data[0]));
                    foreach ($data as $row) {
                        fputcsv($file, array_values($row));
                    }
                } else {
                    fputcsv($file, array_keys($data));
                    fputcsv($file, array_values($data));
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Get campaign performance data
     */
    public function getCampaignPerformance(User $user, string $startDate, string $endDate): array
    {
        return Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select([
                'id',
                'name',
                'total_recipients',
                'messages_sent',
                'messages_delivered',
                'messages_failed',
                'status',
                'created_at',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'total_recipients' => $campaign->total_recipients,
                    'messages_sent' => $campaign->messages_sent,
                    'messages_delivered' => $campaign->messages_delivered,
                    'messages_failed' => $campaign->messages_failed,
                    'success_rate' => $campaign->success_rate,
                    'created_at' => $campaign->created_at->format('Y-m-d'),
                ];
            })
            ->toArray();
    }

    /**
     * Get contact growth over time
     */
    public function getContactGrowth(User $user, string $startDate, string $endDate): array
    {
        $contacts = Contact::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $contacts->map(function ($item) {
            return [
                'date' => $item->date,
                'count' => $item->count,
            ];
        })->toArray();
    }

    /**
     * Get daily message trends
     */
    public function getMessageTrends(User $user, string $startDate, string $endDate): array
    {
        $messages = DB::table('campaign_recipients')
            ->join('campaigns', 'campaign_recipients.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.user_id', $user->id)
            ->whereBetween('campaign_recipients.sent_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(campaign_recipients.sent_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN campaign_recipients.status = "delivered" THEN 1 ELSE 0 END) as delivered'),
                DB::raw('SUM(CASE WHEN campaign_recipients.status = "failed" THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $messages->map(function ($item) {
            return [
                'date' => $item->date,
                'total' => $item->total,
                'delivered' => $item->delivered,
                'failed' => $item->failed,
                'success_rate' => $item->total > 0 ? round(($item->delivered / $item->total) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Get overview statistics
     */
    public function getOverviewStats(User $user, string $startDate, string $endDate): array
    {
        // Total campaigns in date range
        $totalCampaigns = Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Total messages sent
        $totalMessages = Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('messages_sent');

        // Total contacts
        $totalContacts = Contact::forUser($user)->count();

        // Average success rate
        $avgSuccessRate = Campaign::forUser($user)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('messages_sent', '>', 0)
            ->avg(DB::raw('(messages_delivered / messages_sent) * 100'));

        // Active campaigns
        $activeCampaigns = Campaign::forUser($user)
            ->where('status', 'running')
            ->count();

        // Completed campaigns
        $completedCampaigns = Campaign::forUser($user)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->count();

        return [
            'total_campaigns' => $totalCampaigns,
            'total_messages' => $totalMessages,
            'total_contacts' => $totalContacts,
            'average_success_rate' => round($avgSuccessRate ?? 0, 2),
            'active_campaigns' => $activeCampaigns,
            'completed_campaigns' => $completedCampaigns,
        ];
    }
}
