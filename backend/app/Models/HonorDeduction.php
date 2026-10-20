<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('honor_deductions')]
#[Fillable([
    'employee_id',
    'honor_period_id',
    'component_id',
    'source_date',
    'amount',
    'reason',
    'status',
    'rejection_reason',
    'applied_payroll_id',
    'operation_key',
])]
class HonorDeduction extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'employee_id' => 'integer',
            'honor_period_id' => 'integer',
            'component_id' => 'integer',
            'source_date' => 'immutable_date:Y-m-d',
            'amount' => 'decimal:2',
            'requested_by' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'applied_payroll_id' => 'integer',
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

    /** @return BelongsTo<HonorComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(HonorComponent::class, 'component_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /** @return BelongsTo<Payroll, $this> */
    public function appliedPayroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'applied_payroll_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function payrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'deduction_id', 'id');
    }
}
