<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('payroll_line_sources')]
#[Fillable([
    'payroll_line_id',
    'payroll_version_id',
    'source_key',
    'meeting_id',
    'realization_revision_id',
    'verification_id',
    'additional_duty_id',
    'deduction_id',
    'adjustment_id',
    'rate_id',
    'policy_version_id',
    'source_date',
    'scheduled_employee_id',
    'actual_employee_id',
    'beneficiary_employee_id',
    'category_assignment_id',
    'employee_category_id',
    'quantity_snapshot',
    'rate_snapshot',
    'amount_snapshot',
    'source_snapshot',
])]
#[Hidden(['source_snapshot'])]
class PayrollLineSource extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'payroll_line_id' => 'integer',
            'payroll_version_id' => 'integer',
            'meeting_id' => 'integer',
            'realization_revision_id' => 'integer',
            'verification_id' => 'integer',
            'additional_duty_id' => 'integer',
            'deduction_id' => 'integer',
            'adjustment_id' => 'integer',
            'rate_id' => 'integer',
            'policy_version_id' => 'integer',
            'source_date' => 'immutable_date:Y-m-d',
            'scheduled_employee_id' => 'integer',
            'actual_employee_id' => 'integer',
            'beneficiary_employee_id' => 'integer',
            'category_assignment_id' => 'integer',
            'employee_category_id' => 'integer',
            'quantity_snapshot' => 'decimal:3',
            'rate_snapshot' => 'decimal:2',
            'amount_snapshot' => 'decimal:2',
            'source_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<PayrollVersion, $this> */
    public function payrollVersion(): BelongsTo
    {
        return $this->belongsTo(PayrollVersion::class, 'payroll_version_id', 'id');
    }

    /** @return BelongsTo<Meeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id', 'id');
    }

    /** @return BelongsTo<RealizationRevision, $this> */
    public function realizationRevision(): BelongsTo
    {
        return $this->belongsTo(RealizationRevision::class, 'realization_revision_id', 'id');
    }

    /** @return BelongsTo<RealizationVerification, $this> */
    public function verification(): BelongsTo
    {
        return $this->belongsTo(RealizationVerification::class, 'verification_id', 'id');
    }

    /** @return BelongsTo<EmployeeAdditionalDuty, $this> */
    public function additionalDuty(): BelongsTo
    {
        return $this->belongsTo(EmployeeAdditionalDuty::class, 'additional_duty_id', 'id');
    }

    /** @return BelongsTo<HonorDeduction, $this> */
    public function deduction(): BelongsTo
    {
        return $this->belongsTo(HonorDeduction::class, 'deduction_id', 'id');
    }

    /** @return BelongsTo<PayrollAdjustment, $this> */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(PayrollAdjustment::class, 'adjustment_id', 'id');
    }

    /** @return BelongsTo<HonorRate, $this> */
    public function rate(): BelongsTo
    {
        return $this->belongsTo(HonorRate::class, 'rate_id', 'id');
    }

    /** @return BelongsTo<HonorPolicyVersion, $this> */
    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(HonorPolicyVersion::class, 'policy_version_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function scheduledEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'scheduled_employee_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function actualEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actual_employee_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function beneficiaryEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'beneficiary_employee_id', 'id');
    }

    /** @return BelongsTo<EmployeeCategory, $this> */
    public function employeeCategory(): BelongsTo
    {
        return $this->belongsTo(EmployeeCategory::class, 'employee_category_id', 'id');
    }

    /** @return BelongsTo<PayrollLine, $this> */
    public function payrollLine(): BelongsTo
    {
        return $this->belongsTo(PayrollLine::class, 'payroll_line_id', 'id');
    }

    /** @return BelongsTo<EmployeeCategoryAssignment, $this> */
    public function categoryAssignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeCategoryAssignment::class, 'category_assignment_id', 'id');
    }

    /** @return HasMany<PayrollAdjustmentBatche, $this> */
    public function payrollAdjustmentBatches(): HasMany
    {
        return $this->hasMany(PayrollAdjustmentBatche::class, 'original_line_source_id', 'id');
    }

    /** @return HasOne<PayrollSourceClaim, $this> */
    public function payrollSourceClaim(): HasOne
    {
        return $this->hasOne(PayrollSourceClaim::class, 'payroll_line_source_id', 'id');
    }

    /** @return HasMany<PayrollAdjustment, $this> */
    public function originalLineSourcePayrollAdjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class, 'original_line_source_id', 'id');
    }

    /** @return HasOne<PayrollAdjustment, $this> */
    public function appliedLineSourcePayrollAdjustment(): HasOne
    {
        return $this->hasOne(PayrollAdjustment::class, 'applied_line_source_id', 'id');
    }
}
