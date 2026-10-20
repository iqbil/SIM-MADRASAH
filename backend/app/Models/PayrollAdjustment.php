<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('payroll_adjustments')]
#[Fillable([
    'batch_id',
    'original_payroll_version_id',
    'original_line_source_id',
    'target_honor_period_id',
    'employee_id',
    'direction',
    'amount',
    'reason',
    'correction_realization_revision_id',
    'status',
    'applied_line_source_id',
    'operation_key',
])]
class PayrollAdjustment extends DomainModel
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
            'batch_id' => 'integer',
            'original_payroll_version_id' => 'integer',
            'original_line_source_id' => 'integer',
            'target_honor_period_id' => 'integer',
            'employee_id' => 'integer',
            'amount' => 'decimal:2',
            'correction_realization_revision_id' => 'integer',
            'requested_by' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'applied_line_source_id' => 'integer',
        ];
    }

    /** @return BelongsTo<PayrollVersion, $this> */
    public function originalPayrollVersion(): BelongsTo
    {
        return $this->belongsTo(PayrollVersion::class, 'original_payroll_version_id', 'id');
    }

    /** @return BelongsTo<HonorPeriod, $this> */
    public function targetHonorPeriod(): BelongsTo
    {
        return $this->belongsTo(HonorPeriod::class, 'target_honor_period_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<RealizationRevision, $this> */
    public function correctionRealizationRevision(): BelongsTo
    {
        return $this->belongsTo(RealizationRevision::class, 'correction_realization_revision_id', 'id');
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

    /** @return BelongsTo<PayrollLineSource, $this> */
    public function originalLineSource(): BelongsTo
    {
        return $this->belongsTo(PayrollLineSource::class, 'original_line_source_id', 'id');
    }

    /** @return BelongsTo<PayrollLineSource, $this> */
    public function appliedLineSource(): BelongsTo
    {
        return $this->belongsTo(PayrollLineSource::class, 'applied_line_source_id', 'id');
    }

    /** @return BelongsTo<PayrollAdjustmentBatche, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayrollAdjustmentBatche::class, 'batch_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function payrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'adjustment_id', 'id');
    }
}
