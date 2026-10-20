<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('letter_dispositions')]
#[Fillable([
    'letter_id',
    'parent_disposition_id',
    'assigned_to_user_id',
    'instruction',
    'due_date',
    'status',
    'completed_at',
    'result',
])]
class LetterDisposition extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'letter_id' => 'integer',
            'parent_disposition_id' => 'integer',
            'assigned_to_user_id' => 'integer',
            'due_date' => 'immutable_date:Y-m-d',
            'assigned_by' => 'integer',
            'assigned_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Letter, $this> */
    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class, 'letter_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id');
    }

    /** @return BelongsTo<LetterDisposition, $this> */
    public function parentDisposition(): BelongsTo
    {
        return $this->belongsTo(LetterDisposition::class, 'parent_disposition_id', 'id');
    }

    /** @return HasMany<DispositionAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(DispositionAction::class, 'disposition_id', 'id');
    }

    /** @return HasMany<LetterDisposition, $this> */
    public function letterDispositions(): HasMany
    {
        return $this->hasMany(LetterDisposition::class, 'parent_disposition_id', 'id');
    }
}
