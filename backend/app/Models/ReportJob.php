<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('report_jobs')]
#[Fillable([
    'report_type',
    'output_format',
    'filters',
    'scope_snapshot',
    'status',
    'operation_key',
    'output_file_id',
    'requested_at',
    'started_at',
    'finished_at',
    'expires_at',
    'attempts',
    'error_code',
    'error_message',
    'dataset_cutoff_at',
    'dataset_status',
    'dataset_row_count',
    'dataset_hash',
    'dataset_extracted_at',
])]
#[Hidden(['filters', 'scope_snapshot', 'error_message'])]
class ReportJob extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'requested_by' => 'integer',
            'filters' => 'array',
            'scope_snapshot' => 'array',
            'output_file_id' => 'integer',
            'requested_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'attempts' => 'integer',
            'dataset_cutoff_at' => 'immutable_datetime',
            'dataset_row_count' => 'integer',
            'dataset_extracted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function outputFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'output_file_id', 'id');
    }

    /** @return HasMany<ReportDatasetRow, $this> */
    public function datasetRows(): HasMany
    {
        return $this->hasMany(ReportDatasetRow::class, 'report_job_id', 'id');
    }
}
