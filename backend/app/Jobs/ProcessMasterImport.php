<?php

namespace App\Jobs;

use App\Models\ImportBatche;
use App\Services\MasterImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessMasterImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $backoff = 10;

    public function __construct(public int $batchId, public string $stage, public string $reason = '') {}

    public function handle(MasterImportService $service): void
    {
        if ($this->stage === 'validate') {
            $service->validateBatch($this->batchId);
        } else {
            $service->importBatch($this->batchId, $this->reason);
        }
    }

    public function failed(?Throwable $exception): void
    {
        DB::transaction(function (): void {
            $batch = ImportBatche::query()->whereKey($this->batchId)->whereIn('status', ['uploaded', 'validating', 'importing'])->lockForUpdate()->first();
            if (! $batch) {
                return;
            }
            $batch->rows()->update(['status' => 'failed', 'validation_errors' => json_encode(['batch' => ['Impor dibatalkan karena data, izin, atau layanan berubah. Periksa master dan unggah ulang untuk pratinjau baru.']], JSON_THROW_ON_ERROR)]);
            $batch->update(['status' => 'failed', 'failed_rows' => $batch->total_rows, 'valid_rows' => 0, 'finished_at' => now('UTC')]);
        }, 3);
    }
}
