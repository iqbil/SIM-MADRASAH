<?php

namespace App\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('employees')]
#[Fillable([
    'person_id',
    'employee_number',
    'nip',
    'nuptk',
    'joined_on',
    'left_on',
    'is_active',
])]
class Employee extends DomainModel
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

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
            'person_id' => 'integer',
            'joined_on' => 'immutable_date:Y-m-d',
            'left_on' => 'immutable_date:Y-m-d',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }

    /** @return HasMany<AuthorizationMandate, $this> */
    public function authorizationMandates(): HasMany
    {
        return $this->hasMany(AuthorizationMandate::class, 'employee_id', 'id');
    }

    /** @return HasMany<EmployeeCategoryAssignment, $this> */
    public function categoryAssignments(): HasMany
    {
        return $this->hasMany(EmployeeCategoryAssignment::class, 'employee_id', 'id');
    }

    /** @return HasMany<EmployeePosition, $this> */
    public function positionAssignments(): HasMany
    {
        return $this->hasMany(EmployeePosition::class, 'employee_id', 'id');
    }

    /** @return HasMany<HomeroomAssignment, $this> */
    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'employee_id', 'id');
    }

    /** @return HasMany<TeachingAssignment, $this> */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'employee_id', 'id');
    }

    /** @return HasMany<Meeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'scheduled_employee_id', 'id');
    }

    /** @return HasMany<SubstituteAssignment, $this> */
    public function substituteAssignments(): HasMany
    {
        return $this->hasMany(SubstituteAssignment::class, 'substitute_employee_id', 'id');
    }

    /** @return HasMany<RealizationRevision, $this> */
    public function realizationRevisions(): HasMany
    {
        return $this->hasMany(RealizationRevision::class, 'actual_employee_id', 'id');
    }

    /** @return HasMany<EmployeeWorkAssignment, $this> */
    public function workAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkAssignment::class, 'employee_id', 'id');
    }

    /** @return HasMany<EmployeeWorkDay, $this> */
    public function workDays(): HasMany
    {
        return $this->hasMany(EmployeeWorkDay::class, 'employee_id', 'id');
    }

    /** @return HasMany<HonorRateScope, $this> */
    public function honorRateScopes(): HasMany
    {
        return $this->hasMany(HonorRateScope::class, 'employee_id', 'id');
    }

    /** @return HasMany<EmployeeAdditionalDuty, $this> */
    public function additionalDuties(): HasMany
    {
        return $this->hasMany(EmployeeAdditionalDuty::class, 'employee_id', 'id');
    }

    /** @return HasMany<HonorDeduction, $this> */
    public function honorDeductions(): HasMany
    {
        return $this->hasMany(HonorDeduction::class, 'employee_id', 'id');
    }

    /** @return HasMany<Payroll, $this> */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'employee_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function scheduledEmployeePayrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'scheduled_employee_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function actualEmployeePayrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'actual_employee_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function beneficiaryEmployeePayrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'beneficiary_employee_id', 'id');
    }

    /** @return HasMany<PayrollSourceClaim, $this> */
    public function payrollSourceClaims(): HasMany
    {
        return $this->hasMany(PayrollSourceClaim::class, 'beneficiary_employee_id', 'id');
    }

    /** @return HasMany<PayrollAdjustmentBatche, $this> */
    public function fromEmployeePayrollAdjustmentBatches(): HasMany
    {
        return $this->hasMany(PayrollAdjustmentBatche::class, 'from_employee_id', 'id');
    }

    /** @return HasMany<PayrollAdjustmentBatche, $this> */
    public function toEmployeePayrollAdjustmentBatches(): HasMany
    {
        return $this->hasMany(PayrollAdjustmentBatche::class, 'to_employee_id', 'id');
    }

    /** @return HasMany<PayrollAdjustment, $this> */
    public function payrollAdjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class, 'employee_id', 'id');
    }

    /** @return HasMany<QuestionCollaborator, $this> */
    public function questionCollaborators(): HasMany
    {
        return $this->hasMany(QuestionCollaborator::class, 'employee_id', 'id');
    }

    /** @return HasMany<Question, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'owner_employee_id', 'id');
    }

    /** @return HasMany<ExamPackage, $this> */
    public function examPackages(): HasMany
    {
        return $this->hasMany(ExamPackage::class, 'owner_employee_id', 'id');
    }

    /** @return HasMany<BkCase, $this> */
    public function bkCases(): HasMany
    {
        return $this->hasMany(BkCase::class, 'assigned_employee_id', 'id');
    }

    /** @return HasMany<BkFollowup, $this> */
    public function bkFollowups(): HasMany
    {
        return $this->hasMany(BkFollowup::class, 'assigned_employee_id', 'id');
    }

    /** @return HasMany<PublicStaffProfile, $this> */
    public function publicStaffProfiles(): HasMany
    {
        return $this->hasMany(PublicStaffProfile::class, 'employee_id', 'id');
    }

    /** @return HasMany<SourceRecord, $this> */
    public function sourceRecords(): HasMany
    {
        return $this->hasMany(SourceRecord::class, 'employee_id', 'id');
    }
}
