<?php

namespace App\Services;

use App\Models\ContactImport;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use libphonenumber\PhoneNumberUtil;
use PhpOffice\PhpSpreadsheet\IOFactory;

readonly class ContactImportService
{
    public function __construct(
        private ContactService        $contactService,
        private ImportProgressService $progressService
    )
    {
    }

    /**
     * Parse uploaded file and return preview data
     */
    public function parseFile(string $filePath, string $fileType): array
    {
        try {
            if ($fileType === 'csv') {
                return $this->parseCsvFile($filePath);
            } else {
                return $this->parseExcelFile($filePath);
            }
        } catch (\Exception $e) {
            Log::error('File parsing failed', [
                'file_path' => $filePath,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to parse file: ' . $e->getMessage());
        }
    }

    /**
     * Parse CSV file
     * @throws UnavailableStream
     * @throws Exception
     */
    private function parseCsvFile(string $filePath): array
    {
        $csv = Reader::createFromPath(Storage::path($filePath), 'r');

        // Auto-detect delimiter (comma, semicolon, tab)
        $csv->setDelimiter(',');
        $csv->setHeaderOffset(0);

        $headers = $csv->getHeader();

        // If only one header, try other delimiters
        if (count($headers) === 1) {
            // Try semicolon
            $csv->setDelimiter(';');
            $csv->setHeaderOffset(0);
            $headers = $csv->getHeader();

            // If still only one header, try tab
            if (count($headers) === 1) {
                $csv->setDelimiter("\t");
                $csv->setHeaderOffset(0);
                $headers = $csv->getHeader();
            }
        }

        $records = iterator_to_array($csv->getRecords());

        // Get preview (first 10 rows)
        $preview = array_slice($records, 0, 10);

        // Log for debugging
        Log::info('CSV Parse Result', [
            'headers' => $headers,
            'header_count' => count($headers),
            'first_row' => $preview[0] ?? null,
            'total_rows' => count($records),
        ]);

        return [
            'headers' => $headers,
            'preview' => array_values($preview),
            'total_rows' => count($records),
        ];
    }

    /**
     * Parse Excel file
     */
    private function parseExcelFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load(Storage::path($filePath));
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        if (empty($data)) {
            throw new \Exception('Excel file is empty');
        }

        $headers = array_shift($data);

        // Remove empty rows
        $data = array_filter($data, function ($row) {
            return !empty(array_filter($row));
        });

        // Get preview (first 10 rows)
        $preview = array_slice($data, 0, 10);

        return [
            'headers' => $headers,
            'preview' => $preview,
            'total_rows' => count($data),
        ];
    }

    /**
     * Process import with column mapping
     * @throws \Exception|\Throwable
     */
    public function processImport(
        User          $user,
        ContactImport $import,
        array         $columnMapping,
        bool          $validateWhatsApp = false,
        ?int          $tagId = null,
        ?int          $defaultCountryId = null
    ): array
    {
        try {
            $import->markAsProcessing();

            $fileType = pathinfo($import->filename, PATHINFO_EXTENSION);
            $fileType = in_array($fileType, ['xlsx', 'xls']) ? 'excel' : 'csv';

            // Read ALL data from file
            $data = $this->readAllData($import->file_path, $fileType);

            $results = [
                'total' => count($data['data']),
                'valid' => 0,
                'invalid' => 0,
                'duplicates' => 0,
                'phone_normalized' => 0,
                'errors' => [],
            ];

            // Initialize progress tracking
            $this->progressService->initProgress($import->id, $results['total']);

            $source = $fileType === 'csv' ? 'csv_import' : 'excel_import';

            // Process ALL rows
            foreach ($data['data'] as $index => $row) {
                try {
                    $contactData = $this->mapRowToContact(
                        $row,
                        $columnMapping,
                        $data['headers'],
                        $defaultCountryId
                    );

                    // Skip if missing required fields
                    if (empty($contactData['first_name']) || empty($contactData['phone_number'])) {
                        $results['invalid']++;
                        $results['errors'][] = [
                            'row' => $index + 2,
                            'error' => 'Missing required fields (first_name or phone_number)',
                            'data' => $contactData,
                        ];

                        // Update progress
                        $this->progressService->updateProgress(
                            $import->id,
                            $index + 1,
                            $results['valid'],
                            $results['invalid'],
                            $results['duplicates'],
                            $index + 2
                        );
                        continue;
                    }

                    // Detect country from phone number if not provided
                    if (empty($contactData['country_id']) && !empty($contactData['phone_number'])) {
                        $detectedCountryId = $this->detectCountryFromPhone($contactData['phone_number']);
                        if ($detectedCountryId) {
                            $contactData['country_id'] = $detectedCountryId;
                        }
                    }

                    // Track if phone was normalized
                    $originalPhone = $contactData['phone_number'];
                    $normalized = $this->contactService->normalizePhoneNumber(
                        $contactData['phone_number'],
                        $contactData['country_id'] ?? $defaultCountryId
                    );

                    if (!$normalized) {
                        $results['invalid']++;
                        $results['errors'][] = [
                            'row' => $index + 2,
                            'error' => 'Invalid phone number format: ' . $originalPhone,
                            'data' => $contactData,
                        ];

                        // Update progress
                        $this->progressService->updateProgress(
                            $import->id,
                            $index + 1,
                            $results['valid'],
                            $results['invalid'],
                            $results['duplicates'],
                            $index + 2
                        );
                        continue;
                    }

                    // Track normalization
                    if ($normalized !== $originalPhone) {
                        $results['phone_normalized']++;
                    }

                    $contactData['phone_number'] = $normalized;

                    // Check for duplicates
                    if ($this->contactService->isDuplicate($user, $contactData['phone_number'])) {
                        $results['duplicates']++;

                        // Update progress
                        $this->progressService->updateProgress(
                            $import->id,
                            $index + 1,
                            $results['valid'],
                            $results['invalid'],
                            $results['duplicates'],
                            $index + 2
                        );
                        continue;
                    }

                    // Create contact
                    $contactData['user_id'] = $user->id;
                    $contactData['source'] = $source;
                    $contactData['import_id'] = $import->id;
                    $contactData['validate_whatsapp'] = $validateWhatsApp;

                    if ($tagId) {
                        $contactData['tags'] = [$tagId];
                    }

                    $this->contactService->createContact($user, $contactData);
                    $results['valid']++;

                    // Update progress
                    $this->progressService->updateProgress(
                        $import->id,
                        $index + 1,
                        $results['valid'],
                        $results['invalid'],
                        $results['duplicates'],
                        $index + 2
                    );

                } catch (\Exception $e) {
                    $results['invalid']++;
                    $results['errors'][] = [
                        'row' => $index + 2,
                        'error' => $e->getMessage(),
                        'data' => $contactData ?? [],
                    ];

                    // Update progress
                    $this->progressService->updateProgress(
                        $import->id,
                        $index + 1,
                        $results['valid'],
                        $results['invalid'],
                        $results['duplicates'],
                        $index + 2
                    );
                }
            }

            // Mark progress as completed
            $this->progressService->completeProgress($import->id);

            // Update import record
            $import->update([
                'total_rows' => $results['total'],
                'valid_contacts' => $results['valid'],
                'invalid_contacts' => $results['invalid'],
                'duplicate_contacts' => $results['duplicates'],
                'validation_errors' => $results['errors'],
                'column_mapping' => $columnMapping,
            ]);

            $import->markAsCompleted();

            return $results;

        } catch (\Exception $e) {
            $import->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Read all data from file (not just preview)
     */
    private function readAllData(string $filePath, string $fileType): array
    {
        if ($fileType === 'csv') {
            $csv = Reader::createFromPath(Storage::path($filePath), 'r');
            $csv->setHeaderOffset(0);
            $headers = $csv->getHeader();
            $records = iterator_to_array($csv->getRecords());
            return [
                'headers' => $headers,
                'data' => array_values($records),
            ];
        } else {
            $spreadsheet = IOFactory::load(Storage::path($filePath));
            $worksheet = $spreadsheet->getActiveSheet();
            $allData = $worksheet->toArray();

            if (empty($allData)) {
                throw new \Exception('Excel file is empty');
            }

            $headers = array_shift($allData);

            // Remove empty rows
            $allData = array_filter($allData, function ($row) {
                return !empty(array_filter($row));
            });

            return [
                'headers' => $headers,
                'data' => array_values($allData),
            ];
        }
    }

    /**
     * Map row data to contact fields with phone normalization
     */
    private function mapRowToContact(
        array $row,
        array $columnMapping,
        array $headers,
        ?int  $defaultCountryId = null
    ): array
    {
        $contactData = [];

        foreach ($columnMapping as $field => $columnIndex) {
            if ($columnIndex === null || $columnIndex === '') {
                continue;
            }

            // Get value from row
            $value = null;

            if (is_numeric($columnIndex)) {
                // Numeric index (for Excel files with numeric arrays)
                $value = $row[$columnIndex] ?? null;
            } else {
                // ✅ FIX: For CSV files, rows are associative arrays
                // Access directly by column name instead of converting to index
                $value = $row[$columnIndex] ?? null;
            }

            if ($value !== null && $value !== '') {
                $contactData[$field] = trim($value);
            }
        }

        // Add default country if provided and not in data
        if ($defaultCountryId && empty($contactData['country_id'])) {
            $contactData['country_id'] = $defaultCountryId;
        }

        return $contactData;
    }

    /**
     * Detect country from phone number
     */
    private function detectCountryFromPhone(string $phoneNumber): ?int
    {
        try {
            $phoneUtil = PhoneNumberUtil::getInstance();

            // Try to parse without country code first
            foreach (['EG', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM', 'JO', 'LB', 'IQ', 'YE', 'SY', 'PS'] as $countryCode) {
                try {
                    $numberProto = $phoneUtil->parse($phoneNumber, $countryCode);
                    if ($phoneUtil->isValidNumber($numberProto)) {
                        $detectedCountryCode = $phoneUtil->getRegionCodeForNumber($numberProto);

                        // Find country in database by ISO code
                        $country = \App\Models\Country::where('iso_code', $detectedCountryCode)->first();
                        return $country?->id;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Country detection failed', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate CSV template
     */
    public function generateCsvTemplate(): string
    {
        $headers = [
            'first_name',
            'last_name',
            'phone_number',
            'email',
        ];

        $sampleData = [
            ['محمد', 'أحمد', '+201012345678', 'mohamed@example.com'],
            ['أحمد', 'علي', '+201123456789', 'ahmed@example.com'],
            ['سارة', 'محمود', '+201234567890', 'sara@example.com'],
            ['John', 'Doe', '+201345678901', 'john@example.com'],
            ['Jane', 'Smith', '+201456789012', 'jane@example.com'],
        ];

        $csv = Writer::createFromString();
        $csv->setDelimiter(','); // Explicitly set comma delimiter
        $csv->insertOne($headers);
        $csv->insertAll($sampleData);

        return $csv->toString();
    }
}
