<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('payroll_adjustment_batches')]
#[Fillable([
    'batch_key',
    'batch_type',
    'original_line_source_id',
    'from_employee_id',
    'to_employee_id',
    'target_honor_period_id',
    'transfer_amount',
    'expected_origin_fingerprint',
    'reason',
    'status',
    'operation_key',
])]
class PayrollAdjustmentBatche extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'original_line_source_id' => 'integer',
            'from_employee_id' => 'integer',
            'to_employee_id' => 'integer',
            'target_honor_period_id' => 'integer',
            'transfer_amount' => 'decimal:2',
            'requested_by' => 'integer',
            'reviewed_by' => 'integer',
            'reviewed_at' => 'immutable_datetime',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'applied_by' => 'integer',
            'applied_at' => 'immutable_datetime',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<PayrollLineSource, $this> */
    public function originalLineSource(): BelongsTo
    {
        return $this->belongsTo(PayrollLineSource::class, 'original_line_source_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function fromEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'from_employee_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function toEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'to_employee_id', 'id');
    }

    /** @return BelongsTo<HonorPeriod, $this> */
    public function targetHonorPeriod(): BelongsTo
    {
        return $this->belongsTo(HonorPeriod::class, 'target_honor_period_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function applier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by', 'id');
    }

    /** @return HasMany<PayrollAdjustment, $this> */
    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class, 'batch_id', 'id');
    }
}
