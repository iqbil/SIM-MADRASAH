<?php

namespace App\Models;

use Database\Factories\SchoolClassFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('classes')]
#[Fillable([
    'code',
    'name',
    'grade',
    'is_active',
])]
class SchoolClass extends DomainModel
{
    /** @use HasFactory<SchoolClassFactory> */
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
            'grade' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<ClassPeriod, $this> */
    public function periods(): HasMany
    {
        return $this->hasMany(ClassPeriod::class, 'class_id', 'id');
    }

    /** @return HasMany<SourceRecord, $this> */
    public function sourceRecords(): HasMany
    {
        return $this->hasMany(SourceRecord::class, 'class_id', 'id');
    }
}
