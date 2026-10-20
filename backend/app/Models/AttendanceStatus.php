<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('attendance_statuses')]
#[Fillable([
    'domain',
    'code',
    'name',
    'is_active',
])]
class AttendanceStatus extends DomainModel
{
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
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<EmployeeAttendance, $this> */
    public function employeeAttendances(): HasMany
    {
        return $this->hasMany(EmployeeAttendance::class, 'attendance_status_id', 'id');
    }

    /** @return HasMany<StudentDailyAttendance, $this> */
    public function studentDailyAttendances(): HasMany
    {
        return $this->hasMany(StudentDailyAttendance::class, 'attendance_status_id', 'id');
    }

    /** @return HasMany<StudentMeetingAttendance, $this> */
    public function studentMeetingAttendances(): HasMany
    {
        return $this->hasMany(StudentMeetingAttendance::class, 'attendance_status_id', 'id');
    }
}
