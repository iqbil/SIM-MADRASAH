<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('payrolls')]
#[Fillable([
    'employee_id',
    'honor_period_id',
    'payroll_type',
    'active_version_id',
])]
class Payroll extends DomainModel
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
            'employee_id' => 'integer',
            'honor_period_id' => 'integer',
            'active_version_id' => 'integer',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<HonorPeriod, $this> */
    public function honorPeriod(): BelongsTo
    {
        return $this->belongsTo(HonorPeriod::class, 'honor_period_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<PayrollVersion, $this> */
    public function activeVersion(): BelongsTo
    {
        return $this->belongsTo(PayrollVersion::class, 'active_version_id', 'id');
    }

    /** @return HasMany<PayrollVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(PayrollVersion::class, 'payroll_id', 'id');
    }

    /** @return HasMany<PayrollSourceClaim, $this> */
    public function payrollSourceClaims(): HasMany
    {
        return $this->hasMany(PayrollSourceClaim::class, 'payroll_id', 'id');
    }

    /** @return HasMany<HonorDeduction, $this> */
    public function honorDeductions(): HasMany
    {
        return $this->hasMany(HonorDeduction::class, 'applied_payroll_id', 'id');
    }
}
