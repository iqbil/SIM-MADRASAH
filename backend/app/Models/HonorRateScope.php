<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('honor_rate_scopes')]
#[Fillable([
    'component_id',
    'employee_id',
    'employee_category_id',
    'scope_key',
])]
class HonorRateScope extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'component_id' => 'integer',
            'employee_id' => 'integer',
            'employee_category_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<HonorComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(HonorComponent::class, 'component_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<EmployeeCategory, $this> */
    public function employeeCategory(): BelongsTo
    {
        return $this->belongsTo(EmployeeCategory::class, 'employee_category_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<HonorRate, $this> */
    public function honorRates(): HasMany
    {
        return $this->hasMany(HonorRate::class, 'rate_scope_id', 'id');
    }
}
