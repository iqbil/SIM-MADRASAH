<?php

namespace App\Models;

use Database\Factories\EmployeeWorkDayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('employee_work_days')]
#[Fillable([
    'employee_id',
    'work_assignment_id',
    'work_pattern_version_id',
    'policy_id',
    'work_date',
    'shift_number',
    'scheduled_start_at',
    'scheduled_end_at',
    'tolerance_minutes_snapshot',
    'calendar_event_id',
    'is_work_required',
])]
class EmployeeWorkDay extends DomainModel
{
    /** @use HasFactory<EmployeeWorkDayFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'is_work_required' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'employee_id' => 'integer',
            'work_assignment_id' => 'integer',
            'work_pattern_version_id' => 'integer',
            'policy_id' => 'integer',
            'work_date' => 'immutable_date:Y-m-d',
            'shift_number' => 'integer',
            'scheduled_start_at' => 'immutable_datetime',
            'scheduled_end_at' => 'immutable_datetime',
            'tolerance_minutes_snapshot' => 'integer',
            'calendar_event_id' => 'integer',
            'is_work_required' => 'boolean',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<WorkPatternVersion, $this> */
    public function workPatternVersion(): BelongsTo
    {
        return $this->belongsTo(WorkPatternVersion::class, 'work_pattern_version_id', 'id');
    }

    /** @return BelongsTo<EmployeeAttendancePolicy, $this> */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendancePolicy::class, 'policy_id', 'id');
    }

    /** @return BelongsTo<AcademicCalendarEvent, $this> */
    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendarEvent::class, 'calendar_event_id', 'id');
    }

    /** @return BelongsTo<EmployeeWorkAssignment, $this> */
    public function workAssignment(): BelongsTo
    {
        return $this->belongsTo(EmployeeWorkAssignment::class, 'work_assignment_id', 'id');
    }

    /** @return HasOne<EmployeeAttendance, $this> */
    public function attendance(): HasOne
    {
        return $this->hasOne(EmployeeAttendance::class, 'work_day_id', 'id');
    }
}
