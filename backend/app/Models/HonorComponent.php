<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('honor_components')]
#[Fillable([
    'code',
    'name',
    'component_type',
    'calculation_method',
    'is_active',
])]
class HonorComponent extends DomainModel
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

    /** @return HasMany<HonorRateScope, $this> */
    public function honorRateScopes(): HasMany
    {
        return $this->hasMany(HonorRateScope::class, 'component_id', 'id');
    }

    /** @return HasMany<EmployeeAdditionalDuty, $this> */
    public function employeeAdditionalDuties(): HasMany
    {
        return $this->hasMany(EmployeeAdditionalDuty::class, 'component_id', 'id');
    }

    /** @return HasMany<HonorDeduction, $this> */
    public function honorDeductions(): HasMany
    {
        return $this->hasMany(HonorDeduction::class, 'component_id', 'id');
    }

    /** @return HasMany<PayrollLine, $this> */
    public function payrollLines(): HasMany
    {
        return $this->hasMany(PayrollLine::class, 'component_id', 'id');
    }
}
