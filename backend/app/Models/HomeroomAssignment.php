<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('homeroom_assignments')]
#[Fillable([
    'employee_id',
    'class_period_id',
    'semester_id',
    'effective_from',
    'effective_to',
    'appointment_file_id',
])]
class HomeroomAssignment extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'employee_id' => 'integer',
            'class_period_id' => 'integer',
            'semester_id' => 'integer',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'appointment_file_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<ClassPeriod, $this> */
    public function classPeriod(): BelongsTo
    {
        return $this->belongsTo(ClassPeriod::class, 'class_period_id', 'id');
    }

    /** @return BelongsTo<Semester, $this> */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function appointmentFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'appointment_file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<EmployeeAdditionalDuty, $this> */
    public function employeeAdditionalDuties(): HasMany
    {
        return $this->hasMany(EmployeeAdditionalDuty::class, 'homeroom_assignment_id', 'id');
    }

    /** @return HasMany<BkSummaryRecipient, $this> */
    public function bkSummaryRecipients(): HasMany
    {
        return $this->hasMany(BkSummaryRecipient::class, 'homeroom_assignment_id', 'id');
    }
}
