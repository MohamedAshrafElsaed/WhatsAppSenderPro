<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Template;
use App\Services\CampaignService;
use App\Services\UsageTrackingService;
use App\Services\WhatsAppApiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService,
        private readonly UsageTrackingService $usageTracking,
        private readonly WhatsAppApiService $whatsappApi
    ) {}

    /**
     * Display campaigns list
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Campaign::forUser($user)
            ->with(['template:id,name', 'recipients'])
            ->withCount(['recipients', 'recipients as sent_count' => function ($q) {
                $q->whereIn('status', ['sent', 'delivered']);
            }, 'recipients as failed_count' => function ($q) {
                $q->where('status', 'failed');
            }])
            ->search($request->input('search'))
            ->byStatus($request->input('status'));

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $campaigns = $query->paginate(20)->withQueryString();

        // Get usage stats
        $usageStats = $this->usageTracking->getCurrentUsageStats($user);

        return Inertia::render('campaigns/Index', [
            'campaigns' => $campaigns,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
            'usage' => $usageStats['messages'],
        ]);
    }

    /**
     * Show create campaign form
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        // Check if user can create campaigns
        $this->authorize('create', Campaign::class);

        // Get available contacts
        $contacts = Contact::forUser($user)
            ->validWhatsApp()
            ->select('id', 'first_name', 'last_name', 'phone_number')
            ->get();

        // Get available templates
        $templates = Template::forUser($user)
            ->select('id', 'name', 'type', 'content', 'caption', 'media_url')
            ->get();

        // Get WhatsApp sessions
        try {
            $sessionsResponse = $this->whatsappApi->getSessions($user);
            $sessions = collect($sessionsResponse['data']['sessions'] ?? [])
                ->filter(fn($session) => $session['status'] === 'connected')
                ->values();
        } catch (\Exception $e) {
            $sessions = collect();
        }

        // Get usage stats
        $usageStats = $this->usageTracking->getCurrentUsageStats($user);

        return Inertia::render('campaigns/Create', [
            'contacts' => $contacts,
            'templates' => $templates,
            'sessions' => $sessions,
            'usage' => $usageStats['messages'],
        ]);
    }

    /**
     * Store new campaign
     * @throws AuthorizationException
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Check authorization
        $this->authorize('create', Campaign::class);

        // Validate request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_id' => 'nullable|exists:templates,id',
            'message_type' => 'required|in:text,image,video,audio,document',
            'message_content' => 'required|string|max:4096',
            'message_caption' => 'nullable|string|max:1024',
            'scheduled_at' => 'nullable|date|after:now',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'exists:contacts,id',
            'session_id' => 'required|string', // WhatsApp session ID
            'media' => [
                'nullable',
                'file',
                'max:16384',
                Rule::when(
                    $request->input('message_type') === 'image',
                    ['image', 'mimes:jpg,jpeg,png,gif', 'max:5120']
                ),
                Rule::when(
                    $request->input('message_type') === 'video',
                    ['mimes:mp4', 'max:16384']
                ),
                Rule::when(
                    $request->input('message_type') === 'audio',
                    ['mimes:mp3,ogg,wav', 'max:16384']
                ),
                Rule::when(
                    $request->input('message_type') === 'document',
                    ['mimes:pdf,doc,docx,xls,xlsx', 'max:10240']
                ),
            ],
        ]);

        // Check quota before creating
        $recipientCount = count($validated['recipient_ids']);
        $remaining = $this->usageTracking->getRemainingQuota($user, 'messages_per_month');

        if ($remaining !== 'unlimited' && $recipientCount > $remaining) {
            return back()->withErrors([
                'quota' => trans('campaigns.errors.quota_exceeded', [
                    'required' => $recipientCount,
                    'remaining' => $remaining,
                ]),
            ])->withInput();
        }

        try {
            $validated['user_id'] = $user->id;
            $validated['status'] = $validated['scheduled_at'] ? 'scheduled' : 'draft';

            $campaign = $this->campaignService->createCampaign($user, $validated);

            return redirect()->route('dashboard.campaigns.show', $campaign)
                ->with('success', trans('campaigns.messages.created_successfully'));

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Display campaign details
     * @throws AuthorizationException
     */
    public function show(Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        $campaign->load([
            'template:id,name',
            'recipients.contact:id,first_name,last_name,phone_number',
        ]);

        return Inertia::render('campaigns/Show', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Show edit campaign form
     * @throws AuthorizationException
     */
    public function edit(Campaign $campaign): Response
    {
        $this->authorize('update', $campaign);

        // Only drafts can be edited
        if ($campaign->status !== 'draft') {
            abort(403, 'Only draft campaigns can be edited');
        }

        $user = $campaign->user;

        // Get available contacts
        $contacts = Contact::forUser($user)
            ->validWhatsApp()
            ->select('id', 'first_name', 'last_name', 'phone_number')
            ->get();

        // Get available templates
        $templates = Template::forUser($user)
            ->select('id', 'name', 'type', 'content', 'caption', 'media_url')
            ->get();

        // Get WhatsApp sessions
        try {
            $sessionsResponse = $this->whatsappApi->getSessions($user);
            $sessions = collect($sessionsResponse['data']['sessions'] ?? [])
                ->filter(fn($session) => $session['status'] === 'connected')
                ->values();
        } catch (\Exception $e) {
            $sessions = collect();
        }

        // Get selected recipient IDs
        $selectedRecipients = $campaign->recipients->pluck('contact_id')->toArray();

        return Inertia::render('campaigns/Edit', [
            'campaign' => $campaign,
            'contacts' => $contacts,
            'templates' => $templates,
            'sessions' => $sessions,
            'selectedRecipients' => $selectedRecipients,
        ]);
    }

    /**
     * Update campaign
     * @throws \Throwable
     */
    public function update(Request $request, Campaign $campaign): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $campaign);

        // Validate request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_id' => 'nullable|exists:templates,id',
            'message_type' => 'required|in:text,image,video,audio,document',
            'message_content' => 'required|string|max:4096',
            'message_caption' => 'nullable|string|max:1024',
            'scheduled_at' => 'nullable|date|after:now',
            'recipient_ids' => 'required|array|min:1',
            'recipient_ids.*' => 'exists:contacts,id',
            'session_id' => 'required|string',
            'media' => [
                'nullable',
                'file',
                'max:16384',
            ],
        ]);

        try {
            $campaign = $this->campaignService->updateCampaign($campaign, $validated);

            return redirect()->route('dashboard.campaigns.show', $campaign)
                ->with('success', trans('campaigns.messages.updated_successfully'));

        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Delete campaign
     * @throws \Throwable
     */
    public function destroy(Campaign $campaign): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $campaign);

        try {
            $this->campaignService->deleteCampaign($campaign);

            return redirect()->route('dashboard.campaigns.index')
                ->with('success', trans('campaigns.messages.deleted_successfully'));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pause a running campaign
     * @throws AuthorizationException
     */
    public function pause(Campaign $campaign): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('pause', $campaign);

        try {
            $this->campaignService->pauseCampaign($campaign);

            return back()->with('success', trans('campaigns.messages.paused_successfully'));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Resume a paused campaign
     * @throws AuthorizationException
     */
    public function resume(Campaign $campaign): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('resume', $campaign);

        try {
            $this->campaignService->resumeCampaign($campaign);

            return back()->with('success', trans('campaigns.messages.resumed_successfully'));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Send campaign immediately
     * @throws AuthorizationException
     */
    public function send(Campaign $campaign): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('send', $campaign);

        try {
            $this->campaignService->sendCampaign($campaign);

            return back()->with('success', trans('campaigns.messages.sending_started'));

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show campaign results/analytics
     * @throws AuthorizationException
     */
    public function results(Campaign $campaign): Response
    {
        $this->authorize('view', $campaign);

        // Get recipient statistics
        $recipientsWithStats = $campaign->recipients()
            ->with('contact:id,first_name,last_name,phone_number')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Calculate statistics
        $stats = [
            'total' => $campaign->total_recipients,
            'sent' => $campaign->messages_sent,
            'delivered' => $campaign->messages_delivered,
            'failed' => $campaign->messages_failed,
            'pending' => $campaign->total_recipients - $campaign->messages_sent,
            'success_rate' => $campaign->success_rate,
            'failure_rate' => $campaign->failure_rate,
            'progress_percentage' => $campaign->progress_percentage,
        ];

        return Inertia::render('campaigns/Results', [
            'campaign' => $campaign,
            'recipients' => $recipientsWithStats,
            'stats' => $stats,
        ]);
    }

    /**
     * Export campaign results to CSV
     * @throws AuthorizationException
     */
    public function export(Campaign $campaign): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $campaign);

        $recipients = $campaign->recipients()
            ->with('contact:id,first_name,last_name,phone_number')
            ->get();

        $csvData = [];
        $csvData[] = [
            'Contact Name',
            'Phone Number',
            'Status',
            'Sent At',
            'Delivered At',
            'Error Message',
            'Message ID',
        ];

        foreach ($recipients as $recipient) {
            $csvData[] = [
                $recipient->contact->full_name ?? '',
                $recipient->contact->phone_number ?? '',
                ucfirst($recipient->status),
                $recipient->sent_at ? $recipient->sent_at->format('Y-m-d H:i:s') : '',
                $recipient->delivered_at ? $recipient->delivered_at->format('Y-m-d H:i:s') : '',
                $recipient->error_message ?? '',
                $recipient->message_id ?? '',
            ];
        }

        $filename = "campaign-{$campaign->name}-results-" . now()->format('Y-m-d') . ".csv";

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
