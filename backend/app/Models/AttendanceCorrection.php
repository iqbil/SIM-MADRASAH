<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('attendance_corrections')]
#[Fillable([
    'employee_attendance_id',
    'student_daily_attendance_id',
    'student_meeting_attendance_id',
    'before_values',
    'after_values',
    'reason',
    'requested_at',
    'status',
    'rejection_reason',
    'expected_lock_version',
    'operation_key',
])]
class AttendanceCorrection extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'employee_attendance_id' => 'integer',
            'student_daily_attendance_id' => 'integer',
            'student_meeting_attendance_id' => 'integer',
            'before_values' => 'array',
            'after_values' => 'array',
            'requested_by' => 'integer',
            'requested_at' => 'immutable_datetime',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'applied_at' => 'immutable_datetime',
            'expected_lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<EmployeeAttendance, $this> */
    public function employeeAttendance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id', 'id');
    }

    /** @return BelongsTo<StudentDailyAttendance, $this> */
    public function studentDailyAttendance(): BelongsTo
    {
        return $this->belongsTo(StudentDailyAttendance::class, 'student_daily_attendance_id', 'id');
    }

    /** @return BelongsTo<StudentMeetingAttendance, $this> */
    public function studentMeetingAttendance(): BelongsTo
    {
        return $this->belongsTo(StudentMeetingAttendance::class, 'student_meeting_attendance_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}
