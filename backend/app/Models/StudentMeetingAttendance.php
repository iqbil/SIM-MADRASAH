<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('student_meeting_attendances')]
#[Fillable([
    'student_id',
    'meeting_id',
    'enrollment_id',
    'attendance_status_id',
    'policy_id',
    'notes',
])]
class StudentMeetingAttendance extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'student_id' => 'integer',
            'meeting_id' => 'integer',
            'enrollment_id' => 'integer',
            'attendance_status_id' => 'integer',
            'policy_id' => 'integer',
            'recorded_by' => 'integer',
            'recorded_at' => 'immutable_datetime',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /** @return BelongsTo<Meeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id', 'id');
    }

    /** @return BelongsTo<AttendanceStatus, $this> */
    public function attendanceStatus(): BelongsTo
    {
        return $this->belongsTo(AttendanceStatus::class, 'attendance_status_id', 'id');
    }

    /** @return BelongsTo<AttendancePolicy, $this> */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(AttendancePolicy::class, 'policy_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'id');
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'enrollment_id', 'id');
    }

    /** @return HasMany<AttendanceCorrection, $this> */
    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'student_meeting_attendance_id', 'id');
    }
}
