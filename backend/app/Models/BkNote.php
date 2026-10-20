<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('bk_notes')]
#[Fillable([
    'case_id',
    'note_type',
    'occurred_at',
    'internal_content',
    'violation_code',
    'violation_points',
    'action_taken',
    'previous_note_id',
])]
#[Hidden(['internal_content', 'violation_code', 'violation_points', 'action_taken'])]
class BkNote extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'case_id' => 'integer',
            'occurred_at' => 'immutable_datetime',
            'violation_points' => 'decimal:2',
            'previous_note_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<BkCase, $this> */
    public function bkCase(): BelongsTo
    {
        return $this->belongsTo(BkCase::class, 'case_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<BkNote, $this> */
    public function previousNote(): BelongsTo
    {
        return $this->belongsTo(BkNote::class, 'previous_note_id', 'id');
    }

    /** @return HasMany<BkFollowup, $this> */
    public function bkFollowups(): HasMany
    {
        return $this->hasMany(BkFollowup::class, 'note_id', 'id');
    }

    /** @return HasMany<BkFile, $this> */
    public function bkFiles(): HasMany
    {
        return $this->hasMany(BkFile::class, 'note_id', 'id');
    }
}
