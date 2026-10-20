<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('teaching_realizations')]
#[Fillable([
    'meeting_id',
    'current_revision_id',
])]
class TeachingRealization extends DomainModel
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
            'meeting_id' => 'integer',
            'current_revision_id' => 'integer',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Meeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<RealizationRevision, $this> */
    public function currentRevision(): BelongsTo
    {
        return $this->belongsTo(RealizationRevision::class, 'current_revision_id', 'id');
    }

    /** @return HasMany<RealizationRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(RealizationRevision::class, 'realization_id', 'id');
    }
}
