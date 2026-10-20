<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('bk_files')]
#[Fillable([
    'case_id',
    'note_id',
    'followup_id',
    'summary_id',
    'file_id',
    'visibility',
])]
class BkFile extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'case_id' => 'integer',
            'note_id' => 'integer',
            'followup_id' => 'integer',
            'summary_id' => 'integer',
            'file_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<BkCase, $this> */
    public function bkCase(): BelongsTo
    {
        return $this->belongsTo(BkCase::class, 'case_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<BkNote, $this> */
    public function note(): BelongsTo
    {
        return $this->belongsTo(BkNote::class, 'note_id', 'id');
    }

    /** @return BelongsTo<BkFollowup, $this> */
    public function followup(): BelongsTo
    {
        return $this->belongsTo(BkFollowup::class, 'followup_id', 'id');
    }

    /** @return BelongsTo<BkSummary, $this> */
    public function summary(): BelongsTo
    {
        return $this->belongsTo(BkSummary::class, 'summary_id', 'id');
    }
}
