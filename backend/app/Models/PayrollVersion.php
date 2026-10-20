<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('payroll_versions')]
#[Fillable([
    'payroll_id',
    'version_number',
    'previous_version_id',
    'status',
    'policy_snapshot',
    'employee_snapshot',
    'source_fingerprint',
    'teaching_total',
    'allowance_total',
    'deduction_total',
    'adjustment_total',
    'net_total',
    'revision_reason',
])]
#[Hidden(['policy_snapshot', 'employee_snapshot', 'source_fingerprint'])]
class PayrollVersion extends DomainModel
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
            'payroll_id' => 'integer',
            'version_number' => 'integer',
            'previous_version_id' => 'integer',
            'policy_snapshot' => 'array',
            'employee_snapshot' => 'array',
            'teaching_total' => 'decimal:2',
            'allowance_total' => 'decimal:2',
            'deduction_total' => 'decimal:2',
            'adjustment_total' => 'decimal:2',
            'net_total' => 'decimal:2',
            'calculated_by' => 'integer',
            'calculated_at' => 'immutable_datetime',
            'reviewed_by' => 'integer',
            'reviewed_at' => 'immutable_datetime',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'locked_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Payroll, $this> */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by', 'id');
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

    /** @return BelongsTo<PayrollVersion, $this> */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(PayrollVersion::class, 'previous_version_id', 'id');
    }

    /** @return HasMany<PayrollLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(PayrollLine::class, 'payroll_version_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'payroll_version_id', 'id');
    }

    /** @return HasMany<PayrollAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(PayrollAction::class, 'payroll_version_id', 'id');
    }

    /** @return HasMany<PayrollAdjustment, $this> */
    public function payrollAdjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class, 'original_payroll_version_id', 'id');
    }

    /** @return HasMany<PayrollSourceClaim, $this> */
    public function payrollSourceClaims(): HasMany
    {
        return $this->hasMany(PayrollSourceClaim::class, 'payroll_version_id', 'id');
    }
}
