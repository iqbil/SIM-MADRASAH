<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('positions')]
#[Fillable([
    'code',
    'name',
    'description',
    'is_active',
])]
class Position extends DomainModel
{
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

    /** @return HasMany<EmployeePosition, $this> */
    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeePosition::class, 'position_id', 'id');
    }
}
