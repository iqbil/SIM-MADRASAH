<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('academic_calendar_events')]
#[Fillable([
    'academic_year_id',
    'semester_id',
    'class_period_id',
    'title',
    'event_type',
    'start_date',
    'end_date',
    'blocks_teaching',
    'blocks_employee_work',
    'description',
])]
class AcademicCalendarEvent extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'blocks_teaching' => false,
        'blocks_employee_work' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'academic_year_id' => 'integer',
            'semester_id' => 'integer',
            'class_period_id' => 'integer',
            'start_date' => 'immutable_date:Y-m-d',
            'end_date' => 'immutable_date:Y-m-d',
            'blocks_teaching' => 'boolean',
            'blocks_employee_work' => 'boolean',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'id');
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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<Meeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'calendar_event_id', 'id');
    }

    /** @return HasMany<EmployeeWorkDay, $this> */
    public function employeeWorkDays(): HasMany
    {
        return $this->hasMany(EmployeeWorkDay::class, 'calendar_event_id', 'id');
    }
}
