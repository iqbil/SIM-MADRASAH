<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('employee_additional_duties')]
#[Fillable([
    'employee_id',
    'component_id',
    'position_assignment_id',
    'homeroom_assignment_id',
    'description',
    'effective_from',
    'effective_to',
    'appointment_file_id',
])]
class EmployeeAdditionalDuty extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'employee_id' => 'integer',
            'component_id' => 'integer',
            'position_assignment_id' => 'integer',
            'homeroom_assignment_id' => 'integer',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'appointment_file_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<HonorComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(HonorComponent::class, 'component_id', 'id');
    }

    /** @return BelongsTo<EmployeePosition, $this> */
    public function positionAssignment(): BelongsTo
    {
        return $this->belongsTo(EmployeePosition::class, 'position_assignment_id', 'id');
    }

    /** @return BelongsTo<HomeroomAssignment, $this> */
    public function homeroomAssignment(): BelongsTo
    {
        return $this->belongsTo(HomeroomAssignment::class, 'homeroom_assignment_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function appointmentFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'appointment_file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function payrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'additional_duty_id', 'id');
    }
}
