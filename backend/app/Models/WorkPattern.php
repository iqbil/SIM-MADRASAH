<?php

namespace App\Models;

use Database\Factories\WorkPatternFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('work_patterns')]
#[Fillable([
    'code',
    'name',
    'is_active',
])]
class WorkPattern extends DomainModel
{
    /** @use HasFactory<WorkPatternFactory> */
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
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<WorkPatternVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(WorkPatternVersion::class, 'work_pattern_id', 'id');
    }

    /** @return HasMany<EmployeeWorkAssignment, $this> */
    public function employeeWorkAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkAssignment::class, 'work_pattern_id', 'id');
    }
}
