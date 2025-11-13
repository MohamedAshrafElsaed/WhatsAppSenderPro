<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Template;
use App\Services\CampaignService;
use App\Services\UsageTrackingService;
use App\Services\WhatsAppApiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            ->with(['template:id,name'])
            ->select([
                'campaigns.*',
                \DB::raw('(SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = campaigns.id) as total_recipients'),
                \DB::raw('(SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = campaigns.id AND status IN ("sent", "delivered")) as messages_sent'),
                \DB::raw('(SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = campaigns.id AND status = "delivered") as messages_delivered'),
                \DB::raw('(SELECT COUNT(*) FROM campaign_recipients WHERE campaign_id = campaigns.id AND status = "failed") as messages_failed'),
            ])
            ->search($request->input('search'))
            ->byStatus($request->input('status'));

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $campaigns = $query->paginate(20)->withQueryString();

        // Add computed fields to each campaign
        $campaigns->through(function ($campaign) {
            $campaign->progress_percentage = $campaign->total_recipients > 0
                ? round(($campaign->messages_sent / $campaign->total_recipients) * 100)
                : 0;

            $campaign->success_rate = $campaign->messages_sent > 0
                ? round(($campaign->messages_delivered / $campaign->messages_sent) * 100)
                : 0;

            return $campaign;
        });

        // Get usage stats
        $usageStats = $this->usageTracking->getCurrentUsageStats($user);

        return Inertia::render('campaigns/Index', [
            'campaigns' => [
                'data' => $campaigns->items(),
                'links' => $campaigns->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $campaigns->currentPage(),
                    'from' => $campaigns->firstItem(),
                    'last_page' => $campaigns->lastPage(),
                    'per_page' => $campaigns->perPage(),
                    'to' => $campaigns->lastItem(),
                    'total' => $campaigns->total(),
                ],
            ],
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
            'usage' => $usageStats['messages'] ?? [
                    'used' => 0,
                    'limit' => 'unlimited',
                    'remaining' => 'unlimited',
                ],
        ]);
    }


    /**
     * NEW: Search contacts endpoint for lazy loading
     */
    public function searchContacts(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
            'selected_ids' => 'nullable|array',
            'selected_ids.*' => 'integer',
        ]);

        $perPage = $validated['per_page'] ?? 50;
        $search = $validated['search'] ?? '';

        $query = Contact::forUser($user)
            ->select('id', 'first_name', 'last_name', 'phone_number');

        // Apply search if provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get paginated results
        $contacts = $query->paginate($perPage);

        // If there are selected IDs not in current page, fetch them separately
        $selectedNotInPage = [];
        if (!empty($validated['selected_ids'])) {
            $currentPageIds = collect($contacts->items())->pluck('id')->toArray();
            $missingIds = array_diff($validated['selected_ids'], $currentPageIds);

            if (!empty($missingIds)) {
                $selectedNotInPage = Contact::forUser($user)
                    ->whereIn('id', $missingIds)
                    ->select('id', 'first_name', 'last_name', 'phone_number')
                    ->get();
            }
        }

        return response()->json([
            'contacts' => $contacts->items(),
            'selected_not_in_page' => $selectedNotInPage,
            'pagination' => [
                'current_page' => $contacts->currentPage(),
                'last_page' => $contacts->lastPage(),
                'per_page' => $contacts->perPage(),
                'total' => $contacts->total(),
                'from' => $contacts->firstItem(),
                'to' => $contacts->lastItem(),
            ],
        ]);
    }

    /**
     * NEW: Select all contacts endpoint
     */
    public function selectAllContacts(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $query = Contact::forUser($user);

        // Apply search filter if provided
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Get only IDs for performance
        $contactIds = $query->pluck('id')->toArray();

        return response()->json([
            'contact_ids' => $contactIds,
            'total' => count($contactIds),
        ]);
    }


    /**
     * Show create campaign form - OPTIMIZED VERSION
     * No longer loads all contacts upfront
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        // Check if user can create campaigns
        $this->authorize('create', Campaign::class);

        // Don't load contacts here - they'll be loaded via AJAX
        // Just get the total count for display
        $totalContactsCount = Contact::forUser($user)->count();

        // Get available templates (these are usually fewer)
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
            'totalContactsCount' => $totalContactsCount,
            'templates' => $templates,
            'sessions' => $sessions,
            'usage' => $usageStats['messages'] ?? [
                    'used' => 0,
                    'limit' => 'unlimited',
                    'remaining' => 'unlimited',
                ],
        ]);
    }

    /**
     * Store new campaign with comprehensive validation and error handling
     *
     * @throws AuthorizationException
     */
    public function store(StoreCampaignRequest $request): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();

        // Check authorization
        $this->authorize('create', Campaign::class);

        // Get validated data from form request (all validation already done)
        $validated = $request->validated();

        // Use database transaction for atomic operations
        DB::beginTransaction();

        try {
            // Prepare campaign data
            $validated['user_id'] = $user->id;
            $validated['status'] = $validated['scheduled_at'] ?? null ? 'scheduled' : 'draft';

            // Log campaign creation attempt
            Log::info('Creating campaign', [
                'user_id' => $user->id,
                'campaign_name' => $validated['name'],
                'recipient_count' => count($validated['recipient_ids']),
                'message_type' => $validated['message_type'],
                'has_template' => isset($validated['template_id']),
                'is_scheduled' => isset($validated['scheduled_at']),
            ]);

            // Validate WhatsApp session exists and is connected
            try {
                $sessionsResponse = $this->whatsappApi->getSessions($user);
                $sessionExists = collect($sessionsResponse['data']['sessions'] ?? [])
                    ->firstWhere('id', $validated['session_id']);

                if (!$sessionExists || $sessionExists['status'] !== 'connected') {
                    throw ValidationException::withMessages([
                        'session_id' => 'The selected WhatsApp session is not connected or does not exist.',
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to validate WhatsApp session', [
                    'user_id' => $user->id,
                    'session_id' => $validated['session_id'],
                    'error' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'session_id' => 'Unable to verify WhatsApp session connectivity. Please try again.',
                ]);
            }

            // Create campaign through service (handles media upload, recipients, etc.)
            $campaign = $this->campaignService->createCampaign($user, $validated);

            // Commit transaction
            DB::commit();

            // Log successful creation
            Log::info('Campaign created successfully', [
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'recipient_count' => count($validated['recipient_ids']),
            ]);

            // Activity log (if you have activity logging system)
            activity()
                ->causedBy($user)
                ->performedOn($campaign)
                ->withProperties([
                    'campaign_name' => $campaign->name,
                    'recipient_count' => count($validated['recipient_ids']),
                    'message_type' => $campaign->message_type,
                    'is_scheduled' => !empty($campaign->scheduled_at),
                ])
                ->log('created_campaign');

            return redirect()->route('dashboard.campaigns.show', $campaign)
                ->with('success', trans('campaigns.messages.created_successfully'));

        } catch (ValidationException $e) {
            // Rollback transaction
            DB::rollBack();

            // Re-throw validation exceptions to show to user
            throw $e;

        } catch (\Illuminate\Database\QueryException $e) {
            // Rollback transaction
            DB::rollBack();

            // Log database errors
            Log::error('Database error creating campaign', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['error' => 'A database error occurred. Please try again or contact support.'])
                ->withInput();

        } catch (\Exception $e) {
            // Rollback transaction
            DB::rollBack();

            // Log general errors
            Log::error('Error creating campaign', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['error' => 'An unexpected error occurred: ' . $e->getMessage()])
                ->withInput();
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

        // Add computed properties
        $campaign->total_recipients = $campaign->recipients->count();
        $campaign->messages_sent = $campaign->recipients->whereIn('status', ['sent', 'delivered'])->count();
        $campaign->messages_delivered = $campaign->recipients->where('status', 'delivered')->count();
        $campaign->messages_failed = $campaign->recipients->where('status', 'failed')->count();
        $campaign->progress_percentage = $campaign->total_recipients > 0
            ? round(($campaign->messages_sent / $campaign->total_recipients) * 100)
            : 0;
        $campaign->success_rate = $campaign->messages_sent > 0
            ? round(($campaign->messages_delivered / $campaign->messages_sent) * 100)
            : 0;

        // Add permission flags
        $campaign->can_be_paused = $campaign->status === 'running';
        $campaign->can_be_resumed = $campaign->status === 'paused';
        $campaign->can_be_edited = $campaign->status === 'draft';
        $campaign->can_be_sent = in_array($campaign->status, ['draft', 'scheduled', 'paused']);

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
        $recipientsQuery = $campaign->recipients()
            ->with('contact:id,first_name,last_name,phone_number')
            ->orderBy('created_at', 'desc');

        $recipientsPaginated = $recipientsQuery->paginate(50);

        // Calculate statistics
        $stats = [
            'total' => $campaign->recipients()->count(),
            'sent' => $campaign->recipients()->whereIn('status', ['sent', 'delivered'])->count(),
            'delivered' => $campaign->recipients()->where('status', 'delivered')->count(),
            'failed' => $campaign->recipients()->where('status', 'failed')->count(),
            'pending' => $campaign->recipients()->where('status', 'pending')->count(),
            'success_rate' => 0,
            'failure_rate' => 0,
            'progress_percentage' => 0,
        ];

        // Calculate rates
        if ($stats['sent'] > 0) {
            $stats['success_rate'] = round(($stats['delivered'] / $stats['sent']) * 100);
            $stats['failure_rate'] = round(($stats['failed'] / $stats['sent']) * 100);
        }

        if ($stats['total'] > 0) {
            $stats['progress_percentage'] = round(($stats['sent'] / $stats['total']) * 100);
        }

        return Inertia::render('campaigns/Results', [
            'campaign' => $campaign->only(['id', 'name', 'status', 'message_type', 'created_at']),
            'recipients' => [
                'data' => $recipientsPaginated->items(),
                'links' => $recipientsPaginated->linkCollection()->toArray(),
                'meta' => [
                    'current_page' => $recipientsPaginated->currentPage(),
                    'from' => $recipientsPaginated->firstItem(),
                    'last_page' => $recipientsPaginated->lastPage(),
                    'per_page' => $recipientsPaginated->perPage(),
                    'to' => $recipientsPaginated->lastItem(),
                    'total' => $recipientsPaginated->total(),
                ],
            ],
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
