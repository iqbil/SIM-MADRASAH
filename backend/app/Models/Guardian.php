<?php

namespace App\Models;

use Database\Factories\GuardianFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('guardians')]
#[Fillable([
    'person_id',
    'internal_code',
    'occupation',
    'is_active',
])]
class Guardian extends DomainModel
{
    /** @use HasFactory<GuardianFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => 1,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'person_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }

    /** @return HasMany<StudentGuardian, $this> */
    public function studentRelationships(): HasMany
    {
        return $this->hasMany(StudentGuardian::class, 'guardian_id', 'id');
    }

    /** @return HasMany<SourceRecord, $this> */
    public function sourceRecords(): HasMany
    {
        return $this->hasMany(SourceRecord::class, 'guardian_id', 'id');
    }
}
