<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('meetings')]
#[Fillable([
    'schedule_entry_id',
    'schedule_entry_version_id',
    'meeting_date',
    'occurrence_number',
    'semester_id',
    'class_period_id',
    'subject_id',
    'scheduled_employee_id',
    'start_time_snapshot',
    'end_time_snapshot',
    'planned_jp_snapshot',
    'planned_minutes_snapshot',
    'calendar_event_id',
    'is_cancelled',
    'cancellation_reason',
    'rescheduled_from_id',
])]
class Meeting extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'is_cancelled' => false,
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'schedule_entry_id' => 'integer',
            'schedule_entry_version_id' => 'integer',
            'meeting_date' => 'immutable_date:Y-m-d',
            'occurrence_number' => 'integer',
            'semester_id' => 'integer',
            'class_period_id' => 'integer',
            'subject_id' => 'integer',
            'scheduled_employee_id' => 'integer',
            'planned_jp_snapshot' => 'decimal:3',
            'planned_minutes_snapshot' => 'integer',
            'calendar_event_id' => 'integer',
            'is_cancelled' => 'boolean',
            'rescheduled_from_id' => 'integer',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<ScheduleEntry, $this> */
    public function scheduleEntry(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntry::class, 'schedule_entry_id', 'id');
    }

    /** @return BelongsTo<Semester, $this> */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'id');
    }

    /** @return BelongsTo<ClassPeriod, $this> */
    public function classPeriod(): BelongsTo
    {
        return $this->belongsTo(ClassPeriod::class, 'class_period_id', 'id');
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function scheduledEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'scheduled_employee_id', 'id');
    }

    /** @return BelongsTo<AcademicCalendarEvent, $this> */
    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendarEvent::class, 'calendar_event_id', 'id');
    }

    /** @return BelongsTo<Meeting, $this> */
    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'rescheduled_from_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<ScheduleEntryVersion, $this> */
    public function scheduleEntryVersion(): BelongsTo
    {
        return $this->belongsTo(ScheduleEntryVersion::class, 'schedule_entry_version_id', 'id');
    }

    /** @return HasMany<SubstituteAssignment, $this> */
    public function substituteAssignments(): HasMany
    {
        return $this->hasMany(SubstituteAssignment::class, 'meeting_id', 'id');
    }

    /** @return HasOne<TeachingRealization, $this> */
    public function realization(): HasOne
    {
        return $this->hasOne(TeachingRealization::class, 'meeting_id', 'id');
    }

    /** @return HasMany<StudentMeetingAttendance, $this> */
    public function studentMeetingAttendances(): HasMany
    {
        return $this->hasMany(StudentMeetingAttendance::class, 'meeting_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function payrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'meeting_id', 'id');
    }

    /** @return HasOne<PayrollSourceClaim, $this> */
    public function payrollSourceClaim(): HasOne
    {
        return $this->hasOne(PayrollSourceClaim::class, 'meeting_id', 'id');
    }
}
