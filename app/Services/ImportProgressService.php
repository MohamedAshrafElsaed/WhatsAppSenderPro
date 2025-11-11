<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ImportProgressService
{
    /**
     * Initialize import progress
     */
    public function initProgress(int $importId, int $totalRows): void
    {
        Cache::put("import_progress_{$importId}", [
            'total' => $totalRows,
            'processed' => 0,
            'valid' => 0,
            'invalid' => 0,
            'duplicates' => 0,
            'current_row' => 0,
            'status' => 'processing',
        ], now()->addHours(2));
    }

    /**
     * Update import progress
     */
    public function updateProgress(
        int $importId,
        int $processed,
        int $valid,
        int $invalid,
        int $duplicates,
        int $currentRow
    ): void {
        $progress = Cache::get("import_progress_{$importId}");
        if ($progress) {
            $progress['processed'] = $processed;
            $progress['valid'] = $valid;
            $progress['invalid'] = $invalid;
            $progress['duplicates'] = $duplicates;
            $progress['current_row'] = $currentRow;
            Cache::put("import_progress_{$importId}", $progress, now()->addHours(2));
        }
    }

    /**
     * Get import progress
     */
    public function getProgress(int $importId): ?array
    {
        return Cache::get("import_progress_{$importId}");
    }

    /**
     * Complete import progress
     */
    public function completeProgress(int $importId): void
    {
        $progress = Cache::get("import_progress_{$importId}");
        if ($progress) {
            $progress['status'] = 'completed';
            Cache::put("import_progress_{$importId}", $progress, now()->addMinutes(5));
        }
    }

    /**
     * Clear import progress
     */
    public function clearProgress(int $importId): void
    {
        Cache::forget("import_progress_{$importId}");
    }
}
