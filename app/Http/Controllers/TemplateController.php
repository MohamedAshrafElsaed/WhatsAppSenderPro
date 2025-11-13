<?php

namespace App\Http\Controllers;

use App\Models\Template;
use App\Services\TemplateService;
use App\Services\UsageTrackingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateService      $templateService,
        private readonly UsageTrackingService $usageTrackingService
    )
    {
    }

    /**
     * Display templates list
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Template::forUser($user)
            ->search($request->input('search'))
            ->byType($request->input('type'));

        // Apply sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'usage') {
            $query->mostUsed();
        } elseif ($sortBy === 'last_used') {
            $query->recentlyUsed();
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $templates = $query->paginate(20)
            ->withQueryString();

        // Get usage stats for templates
        $usageStats = $this->usageTrackingService->getCurrentUsageStats($user);

        return Inertia::render('templates/Index', [
            'templates' => $templates,
            'filters' => [
                'search' => $request->input('search'),
                'type' => $request->input('type'),
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
            'usage' => $usageStats['templates'],
        ]);
    }

    /**
     * Show create form
     */
    public function create(Request $request): Response
    {
        $user = $request->user();

        // Check if user can create more templates
        if (!$this->usageTrackingService->canCreateTemplate($user)) {
            $remaining = $this->usageTrackingService->getRemainingQuota($user, 'message_templates');

            return redirect()->route('dashboard.templates.index')
                ->with('error', trans('templates.errors.limit_reached', [
                    'limit' => $user->currentPackage()?->getLimit('message_templates') ?? 0
                ]));
        }

        $placeholders = $this->templateService->getAvailablePlaceholders();

        // Get usage stats
        $usageStats = $this->usageTrackingService->getCurrentUsageStats($user);

        return Inertia::render('templates/Create', [
            'placeholders' => $placeholders,
            'usage' => $usageStats['templates'],
        ]);
    }

    /**
     * Store new template
     */
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if user can create more templates
        if (!$this->usageTrackingService->canCreateTemplate($user)) {
            return back()->withErrors([
                'error' => trans('templates.errors.limit_reached', [
                    'limit' => $user->currentPackage()?->getLimit('message_templates') ?? 0
                ]),
            ])->withInput();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,text_image,text_video,text_document',
            'content' => 'required|string|max:4096',
            'caption' => 'nullable|string|max:1024',
            'media' => [
                'nullable',
                'file',
                'max:16384',
                Rule::when(
                    $request->input('type') === 'text_image',
                    ['image', 'mimes:jpg,jpeg,png,gif', 'max:5120']
                ),
                Rule::when(
                    $request->input('type') === 'text_video',
                    ['mimes:mp4', 'max:16384']
                ),
                Rule::when(
                    $request->input('type') === 'text_document',
                    ['mimes:pdf,doc,docx,xls,xlsx', 'max:10240']
                ),
            ],
        ]);

        try {
            $template = $this->templateService->createTemplate($user, $validated);

            // Track template creation
            $this->usageTrackingService->trackTemplateCreated($user);

            return redirect()->route('dashboard.templates.index')
                ->with('success', trans('templates.messages.created_successfully'));
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Show template details
     */
    public function show(Template $template): Response
    {
        $this->authorize('view', $template);

        $placeholders = $this->templateService->getAvailablePlaceholders();

        return Inertia::render('templates/Show', [
            'template' => $template,
            'placeholders' => $placeholders,
            'samplePreview' => $template->getSamplePreview(),
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Template $template): Response
    {
        $this->authorize('update', $template);

        $placeholders = $this->templateService->getAvailablePlaceholders();

        return Inertia::render('templates/Edit', [
            'template' => $template,
            'placeholders' => $placeholders,
        ]);
    }

    /**
     * Update template
     */
    public function update(Request $request, Template $template)
    {
        $this->authorize('update', $template);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:text,text_image,text_video,text_document',
            'content' => 'required|string|max:4096',
            'caption' => 'nullable|string|max:1024',
            'media' => [
                'nullable',
                'file',
                'max:16384',
                Rule::when(
                    $request->input('type') === 'text_image',
                    ['image', 'mimes:jpg,jpeg,png,gif', 'max:5120']
                ),
                Rule::when(
                    $request->input('type') === 'text_video',
                    ['mimes:mp4', 'max:16384']
                ),
                Rule::when(
                    $request->input('type') === 'text_document',
                    ['mimes:pdf,doc,docx,xls,xlsx', 'max:10240']
                ),
            ],
        ]);

        try {
            $this->templateService->updateTemplate($template, $validated);

            return redirect()->route('dashboard.templates.index')
                ->with('success', trans('templates.messages.updated_successfully'));
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ])->withInput();
        }
    }

    /**
     * Delete template
     */
    public function destroy(Template $template)
    {
        $this->authorize('delete', $template);

        $user = $template->user;

        try {
            $this->templateService->deleteTemplate($template);

            // Decrement template count when deleted
            $this->usageTrackingService->decrementTemplateCount($user);

            return redirect()->route('dashboard.templates.index')
                ->with('success', trans('templates.messages.deleted_successfully'));
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }
}
