<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('import_rows')]
#[Fillable([
    'import_batch_id',
    'row_number',
    'source_record_id',
    'external_id',
    'normalized_values',
    'validation_errors',
    'duplicate_candidates',
    'status',
    'result_action',
])]
#[Hidden(['normalized_values', 'validation_errors', 'duplicate_candidates'])]
class ImportRow extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'import_batch_id' => 'integer',
            'row_number' => 'integer',
            'source_record_id' => 'integer',
            'normalized_values' => 'array',
            'validation_errors' => 'array',
            'duplicate_candidates' => 'array',
        ];
    }

    /** @return BelongsTo<ImportBatche, $this> */
    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatche::class, 'import_batch_id', 'id');
    }

    /** @return BelongsTo<SourceRecord, $this> */
    public function sourceRecord(): BelongsTo
    {
        return $this->belongsTo(SourceRecord::class, 'source_record_id', 'id');
    }
}
