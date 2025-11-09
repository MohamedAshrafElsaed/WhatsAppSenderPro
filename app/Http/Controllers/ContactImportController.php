<?php

namespace App\Http\Controllers;

use App\Models\ContactImport;
use App\Models\ContactTag;
use App\Services\ContactImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ContactImportController extends Controller
{
    public function __construct(
        private readonly ContactImportService $importService
    ) {}

    /**
     * Display import history
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $imports = ContactImport::forUser($user)
            ->latest()
            ->paginate(20);

        return Inertia::render('contacts/Import', [
            'imports' => $imports,
            'tags' => ContactTag::forUser($user)->orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    /**
     * Handle file upload and parsing
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        $user = $request->user();
        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        // Store file
        $path = $file->store('imports', 'local');

        try {
            // Parse file
            $fileType = in_array($extension, ['xlsx', 'xls']) ? 'excel' : 'csv';
            $parseResult = $this->importService->parseFile($path, $fileType);

            // Create import record
            $import = ContactImport::create([
                'user_id' => $user->id,
                'filename' => $filename,
                'file_path' => $path,
                'status' => 'pending',
                'total_rows' => $parseResult['total_rows'],
            ]);

            return back()->with([
                'import_preview' => [
                    'id' => $import->id,
                    'filename' => $filename,
                    'headers' => $parseResult['headers'],
                    'preview' => $parseResult['preview'],
                    'total_rows' => $parseResult['total_rows'],
                ],
            ]);

        } catch (\Exception $e) {
            // Clean up file
            Storage::delete($path);

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
        ]);

        $user = $request->user();

        try {
            $results = $this->importService->processImport(
                $user,
                $import,
                $validated['column_mapping'],
                $validated['validate_whatsapp'] ?? false,
                $validated['tag_id'] ?? null
            );

            return back()->with([
                'import_summary' => [
                    'import_id' => $import->id,
                    'total' => $results['total'],
                    'valid' => $results['valid'],
                    'invalid' => $results['invalid'],
                    'duplicates' => $results['duplicates'],
                    'errors' => $results['errors'],
                ],
                'success' => trans('imports.messages.completed_successfully'),
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
}
