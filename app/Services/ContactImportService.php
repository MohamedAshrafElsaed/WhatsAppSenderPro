<?php

namespace App\Services;

use App\Models\ContactImport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;

readonly class ContactImportService
{
    public function __construct(
        private ContactService $contactService
    ) {}

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
        $csv->setHeaderOffset(0);

        $headers = $csv->getHeader();
        $records = iterator_to_array($csv->getRecords());

        // Get preview (first 10 rows)
        $preview = array_slice($records, 0, 10);

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
     */
    public function processImport(
        User $user,
        ContactImport $import,
        array $columnMapping,
        bool $validateWhatsApp = false,
        ?int $tagId = null
    ): array {
        try {
            $import->markAsProcessing();

            $fileType = pathinfo($import->filename, PATHINFO_EXTENSION);
            $data = $this->parseFile($import->file_path, $fileType === 'csv' ? 'csv' : 'excel');

            $results = [
                'total' => $data['total_rows'],
                'valid' => 0,
                'invalid' => 0,
                'duplicates' => 0,
                'errors' => [],
            ];

            $source = $fileType === 'csv' ? 'csv_import' : 'excel_import';

            foreach ($data['preview'] as $index => $row) {
                try {
                    $contactData = $this->mapRowToContact($row, $columnMapping, $data['headers']);

                    // Skip if missing required fields
                    if (empty($contactData['first_name']) || empty($contactData['phone_number'])) {
                        $results['invalid']++;
                        $results['errors'][] = [
                            'row' => $index + 2,
                            'error' => 'Missing required fields',
                            'data' => $contactData,
                        ];
                        continue;
                    }

                    // Check for duplicates
                    if ($this->contactService->isDuplicate($user, $contactData['phone_number'])) {
                        $results['duplicates']++;
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

                } catch (\Exception $e) {
                    $results['invalid']++;
                    $results['errors'][] = [
                        'row' => $index + 2,
                        'error' => $e->getMessage(),
                        'data' => $contactData ?? [],
                    ];
                }
            }

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
     * Map row data to contact fields
     */
    private function mapRowToContact(array $row, array $columnMapping, array $headers): array
    {
        $contactData = [];

        foreach ($columnMapping as $field => $columnIndex) {
            if ($columnIndex === null) {
                continue;
            }

            // Get value from row using header or index
            $value = null;
            if (is_numeric($columnIndex)) {
                $value = $row[$columnIndex] ?? null;
            } else {
                $headerIndex = array_search($columnIndex, $headers);
                if ($headerIndex !== false) {
                    $value = $row[$headerIndex] ?? null;
                }
            }

            if ($value !== null) {
                $contactData[$field] = trim($value);
            }
        }

        return $contactData;
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
            'country',
        ];

        $sampleData = [
            ['John', 'Doe', '+201234567890', 'john@example.com', 'Egypt'],
            ['Jane', 'Smith', '+201987654321', 'jane@example.com', 'Egypt'],
        ];

        $csv = Writer::createFromString();
        $csv->insertOne($headers);
        $csv->insertAll($sampleData);

        return $csv->toString();
    }
}
