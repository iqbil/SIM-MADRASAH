<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('employee_categories')]
#[Fillable([
    'code',
    'name',
    'is_active',
])]
class EmployeeCategory extends DomainModel
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

    /** @return HasMany<EmployeeCategoryAssignment, $this> */
    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeCategoryAssignment::class, 'category_id', 'id');
    }

    /** @return HasMany<HonorRateScope, $this> */
    public function honorRateScopes(): HasMany
    {
        return $this->hasMany(HonorRateScope::class, 'employee_category_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function payrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'employee_category_id', 'id');
    }
}
