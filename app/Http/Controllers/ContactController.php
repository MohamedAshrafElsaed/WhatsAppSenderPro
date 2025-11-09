<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\Country;
use App\Services\ContactService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contactService
    ) {}

    /**
     * Display contacts list
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = Contact::forUser($user)
            ->with(['tags', 'country'])
            ->search($request->input('search'))
            ->bySource($request->input('source'))
            ->withTag($request->input('tag_id'))
            ->byCountry($request->input('country_id'));

        // Filter by validation status
        $validationStatus = $request->input('validation_status');
        if ($validationStatus === 'valid') {
            $query->validWhatsApp();
        } elseif ($validationStatus === 'invalid') {
            $query->invalidWhatsApp();
        }

        $contacts = $query->latest()
            ->paginate(20)
            ->withQueryString();

        // Get filter options
        $tags = ContactTag::forUser($user)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $countries = Country::active()
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar']);

        return Inertia::render('contacts/Index', [
            'contacts' => $contacts,
            'tags' => $tags,
            'countries' => $countries,
            'filters' => [
                'search' => $request->input('search'),
                'source' => $request->input('source'),
                'tag_id' => $request->input('tag_id'),
                'country_id' => $request->input('country_id'),
                'validation_status' => $validationStatus,
            ],
        ]);
    }

    /**
     * Show create form
     */
    public function create(): Response
    {
        $countries = Country::active()
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar', 'phone_code']);

        $tags = ContactTag::forUser(auth()->user())
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('contacts/Create', [
            'countries' => $countries,
            'tags' => $tags,
        ]);
    }

    /**
     * Store new contact
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'required|string',
            'email' => 'nullable|email|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:contact_tags,id',
            'notes' => 'nullable|string',
            'validate_whatsapp' => 'boolean',
        ]);

        $user = $request->user();

        // Check for duplicates
        if ($this->contactService->isDuplicate($user, $validated['phone_number'])) {
            return back()->withErrors([
                'phone_number' => trans('contacts.errors.duplicate_phone'),
            ])->withInput();
        }

        $this->contactService->createContact($user, $validated);

        return redirect()->route('contacts.index')
            ->with('success', trans('contacts.messages.created_successfully'));
    }

    /**
     * Show contact details
     */
    public function show(Contact $contact): Response
    {
        $this->authorize('view', $contact);

        $contact->load(['tags', 'country', 'import']);

        return Inertia::render('contacts/Show', [
            'contact' => $contact,
        ]);
    }

    /**
     * Show edit form
     */
    public function edit(Contact $contact): Response
    {
        $this->authorize('update', $contact);

        $contact->load(['tags', 'country']);

        $countries = Country::active()
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar', 'phone_code']);

        $tags = ContactTag::forUser(auth()->user())
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        return Inertia::render('contacts/Edit', [
            'contact' => $contact,
            'countries' => $countries,
            'tags' => $tags,
        ]);
    }

    /**
     * Update contact
     */
    public function update(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'required|string',
            'email' => 'nullable|email|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:contact_tags,id',
            'notes' => 'nullable|string',
            'validate_whatsapp' => 'boolean',
        ]);

        $user = $request->user();

        // Check for duplicates (excluding current contact)
        if ($this->contactService->isDuplicate(
            $user,
            $validated['phone_number'],
            $contact->id
        )) {
            return back()->withErrors([
                'phone_number' => trans('contacts.errors.duplicate_phone'),
            ])->withInput();
        }

        $this->contactService->updateContact($contact, $validated);

        return redirect()->route('contacts.index')
            ->with('success', trans('contacts.messages.updated_successfully'));
    }

    /**
     * Delete contact
     */
    public function destroy(Contact $contact)
    {
        $this->authorize('delete', $contact);

        $this->contactService->deleteContact($contact);

        return redirect()->route('contacts.index')
            ->with('success', trans('contacts.messages.deleted_successfully'));
    }

    /**
     * Bulk validate WhatsApp numbers
     */
    public function bulkValidate(Request $request)
    {
        $validated = $request->validate([
            'contact_ids' => 'required|array',
            'contact_ids.*' => 'exists:contacts,id',
        ]);

        $user = $request->user();
        $results = $this->contactService->bulkValidateWhatsApp(
            $user,
            $validated['contact_ids']
        );

        return back()->with('success', trans('contacts.messages.validation_completed', [
            'valid' => $results['valid'],
            'invalid' => $results['invalid'],
        ]));
    }
}
