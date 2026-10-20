<?php

namespace App\Console\Commands;

use App\Models\ImportBatche;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PurgeMasterImportPayloads extends Command
{
    protected $signature = 'master:purge-import-payloads';

    protected $description = 'Hapus berkas dan payload impor master setelah retensi 30 hari, pertahankan ringkasan batch dan pemetaan sumber.';

    public function handle(): int
    {
        $count = 0;
        ImportBatche::query()->where('module', 'like', 'master.%')->whereHas('sourceFile', fn (Builder $query): Builder => $query->where('expires_at', '<=', now('UTC'))->whereNull('deleted_at'))
            ->chunkById(50, function (Collection $batches) use (&$count): void {
                foreach ($batches as $batch) {
                    DB::transaction(function () use ($batch, &$count): void {
                        $locked = ImportBatche::query()->lockForUpdate()->findOrFail($batch->id);
                        $file = $locked->sourceFile()->lockForUpdate()->firstOrFail();
                        if ($file->deleted_at || $file->expires_at->isFuture()) {
                            return;
                        }
                        if (! Storage::disk($file->storage_disk)->delete($file->storage_path)) {
                            $this->error('Berkas batch '.$locked->id.' belum dapat dihapus; ulangi perintah.');

                            return;
                        }
                        $locked->rows()->update(['normalized_values' => null, 'validation_errors' => null, 'duplicate_candidates' => null, 'external_id' => null]);
                        if (in_array($locked->status, ['uploaded', 'validating', 'preview_ready', 'importing'], true)) {
                            $locked->update(['status' => 'cancelled', 'finished_at' => now('UTC')]);
                        }
                        $file->update(['deleted_at' => now('UTC')]);
                        $count++;
                    }, 3);
                }
            });
        $this->info($count.' payload impor kedaluwarsa dibersihkan.');

        return self::SUCCESS;
    }
}
