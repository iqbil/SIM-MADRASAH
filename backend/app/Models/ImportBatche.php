<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('import_batches')]
#[Fillable([
    'module',
    'source_system',
    'source_file_id',
    'file_hash',
    'template_version',
    'status',
    'operation_key',
    'total_rows',
    'valid_rows',
    'failed_rows',
    'inserted_rows',
    'updated_rows',
    'atomicity_mode',
    'started_at',
    'finished_at',
    'error_report_file_id',
])]
class ImportBatche extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'requested_by' => 'integer',
            'source_file_id' => 'integer',
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'failed_rows' => 'integer',
            'inserted_rows' => 'integer',
            'updated_rows' => 'integer',
            'confirmed_by' => 'integer',
            'confirmed_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'error_report_file_id' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function sourceFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'source_file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function errorReportFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'error_report_file_id', 'id');
    }

    /** @return HasMany<SourceRecord, $this> */
    public function sourceRecords(): HasMany
    {
        return $this->hasMany(SourceRecord::class, 'last_batch_id', 'id');
    }

    /** @return HasMany<ImportRow, $this> */
    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class, 'import_batch_id', 'id');
    }
}
