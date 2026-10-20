<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('payroll_source_claims')]
#[Fillable([
    'meeting_id',
    'payroll_id',
    'payroll_version_id',
    'payroll_line_source_id',
    'beneficiary_employee_id',
])]
class PayrollSourceClaim extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'meeting_id' => 'integer',
            'payroll_id' => 'integer',
            'payroll_version_id' => 'integer',
            'payroll_line_source_id' => 'integer',
            'beneficiary_employee_id' => 'integer',
            'claimed_at' => 'immutable_datetime',
            'claimed_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Meeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id', 'id');
    }

    /** @return BelongsTo<Payroll, $this> */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class, 'payroll_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function beneficiaryEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'beneficiary_employee_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function claimant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by', 'id');
    }

    /** @return BelongsTo<PayrollVersion, $this> */
    public function payrollVersion(): BelongsTo
    {
        return $this->belongsTo(PayrollVersion::class, 'payroll_version_id', 'id');
    }

    /** @return BelongsTo<PayrollLineSource, $this> */
    public function payrollLineSource(): BelongsTo
    {
        return $this->belongsTo(PayrollLineSource::class, 'payroll_line_source_id', 'id');
    }
}
