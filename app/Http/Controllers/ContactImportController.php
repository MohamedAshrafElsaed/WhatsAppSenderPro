<?php

namespace App\Http\Controllers;

use App\Models\ContactImport;
use App\Models\ContactTag;
use App\Models\Country;
use App\Services\ContactImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ContactImportController extends Controller
{
    public function __construct(
        private readonly ContactImportService $importService
    )
    {
    }

    /**
     * Display import history
     */
    public function index(): Response
    {
        $user = auth()->user();

        $imports = ContactImport::forUser($user)
            ->orderBy('id', 'desc')  // Changed from created_at to id
            ->paginate(10);

        $tags = ContactTag::forUser($user)
            ->orderBy('name')
            ->get(['id', 'name', 'color']);

        $countries = Country::active()
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar', 'phone_code', 'iso_code']);

        return Inertia::render('contacts/imports/Index', [
            'imports' => $imports,
            'tags' => $tags,
            'countries' => $countries,
        ]);
    }

    /**
     * Upload and parse import file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $user = $request->user();

        try {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('imports', $filename, 'local');

            // Parse file to get preview
            $fileType = $file->getClientOriginalExtension();
            $preview = $this->importService->parseFile($path, $fileType);

            // Create import record
            $import = ContactImport::create([
                'user_id' => $user->id,
                'filename' => $filename,
                'file_path' => $path,
                'file_type' => $fileType,
                'total_rows' => $preview['total_rows'],
                'status' => 'pending',
            ]);

            // Share the preview data using Inertia's share method
            return redirect()->route('dashboard.contacts.imports.index')->with([
                'import_preview' => array_merge($preview, ['import_id' => $import->id]),
            ]);

        } catch (\Exception $e) {
            return back()->withErrors([
                'file' => trans('imports.errors.parse_failed') . ': ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Process import with column mapping
     */
    public function process(Request $request, ContactImport $import)
    {
        $this->authorize('update', $import);

        $validated = $request->validate([
            'column_mapping' => 'required|array',
            'column_mapping.first_name' => 'required',
            'column_mapping.phone_number' => 'required',
            'validate_whatsapp' => 'boolean',
            'tag_id' => 'nullable|exists:contact_tags,id',
            'default_country_id' => 'nullable|exists:countries,id',
        ]);

        $user = $request->user();

        try {
            $results = $this->importService->processImport(
                $user,
                $import,
                $validated['column_mapping'],
                $validated['validate_whatsapp'] ?? false,
                $validated['tag_id'] ?? null,
                $validated['default_country_id'] ?? null
            );

            // Redirect with import summary data
            return redirect()->route('dashboard.contacts.imports.index')->with([
                'import_summary' => [
                    'import_id' => $import->id,
                    'total' => $results['total'],
                    'valid' => $results['valid'],
                    'invalid' => $results['invalid'],
                    'duplicates' => $results['duplicates'],
                    'phone_normalized' => $results['phone_normalized'],
                    'errors' => $results['errors'],
                ],
            ]);

        } catch (\Exception $e) {
            return back()->withErrors([
                'processing' => trans('imports.errors.processing_failed') . ': ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete import
     */
    public function destroy(ContactImport $import)
    {
        $this->authorize('delete', $import);

        // Delete associated file
        if (Storage::exists($import->file_path)) {
            Storage::delete($import->file_path);
        }

        $import->delete();

        return back()->with('success', trans('imports.messages.deleted_successfully'));
    }

    /**
     * Download CSV template
     */
    public function downloadTemplate()
    {
        $csv = $this->importService->generateCsvTemplate();

        return response($csv, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="contacts_template.csv"');
    }

    /**
     * Get import progress (for polling)
     */
    public function progress(Request $request, ContactImport $import)
    {
        $this->authorize('view', $import);

        $progressService = app(\App\Services\ImportProgressService::class);
        $progress = $progressService->getProgress($import->id);

        return response()->json($progress ?? [
            'total' => 0,
            'processed' => 0,
            'valid' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'current_row' => 0,
            'status' => 'unknown',
        ]);
    }
}
