<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('report_dataset_rows')]
#[Fillable([
    'report_job_id',
    'row_number',
    'payload',
    'row_hash',
])]
#[Hidden(['payload'])]
class ReportDatasetRow extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'report_job_id' => 'integer',
            'row_number' => 'integer',
            'payload' => 'array',
        ];
    }

    /** @return BelongsTo<ReportJob, $this> */
    public function reportJob(): BelongsTo
    {
        return $this->belongsTo(ReportJob::class, 'report_job_id', 'id');
    }
}
