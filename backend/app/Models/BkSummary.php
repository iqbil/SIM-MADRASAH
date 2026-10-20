<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('bk_summaries')]
#[Fillable([
    'case_id',
    'summary_text',
    'summary_date',
    'status',
    'previous_summary_id',
])]
class BkSummary extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'case_id' => 'integer',
            'summary_date' => 'immutable_date:Y-m-d',
            'previous_summary_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<BkCase, $this> */
    public function bkCase(): BelongsTo
    {
        return $this->belongsTo(BkCase::class, 'case_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<BkSummary, $this> */
    public function previousSummary(): BelongsTo
    {
        return $this->belongsTo(BkSummary::class, 'previous_summary_id', 'id');
    }

    /** @return HasMany<BkSummaryRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(BkSummaryRecipient::class, 'summary_id', 'id');
    }

    /** @return HasMany<BkFile, $this> */
    public function bkFiles(): HasMany
    {
        return $this->hasMany(BkFile::class, 'summary_id', 'id');
    }
}
