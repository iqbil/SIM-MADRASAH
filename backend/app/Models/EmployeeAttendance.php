<?php

namespace App\Models;

use Database\Factories\EmployeeAttendanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('employee_attendances')]
#[Fillable([
    'work_day_id',
    'attendance_status_id',
    'check_in_at',
    'check_out_at',
    'late_minutes',
    'entry_mode',
    'notes',
    'evidence_file_id',
])]
class EmployeeAttendance extends DomainModel
{
    /** @use HasFactory<EmployeeAttendanceFactory> */
    use HasFactory;

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
            'work_day_id' => 'integer',
            'attendance_status_id' => 'integer',
            'check_in_at' => 'immutable_datetime',
            'check_out_at' => 'immutable_datetime',
            'late_minutes' => 'integer',
            'evidence_file_id' => 'integer',
            'recorded_by' => 'integer',
            'recorded_at' => 'immutable_datetime',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<EmployeeWorkDay, $this> */
    public function workDay(): BelongsTo
    {
        return $this->belongsTo(EmployeeWorkDay::class, 'work_day_id', 'id');
    }

    /** @return BelongsTo<AttendanceStatus, $this> */
    public function attendanceStatus(): BelongsTo
    {
        return $this->belongsTo(AttendanceStatus::class, 'attendance_status_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function evidenceFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'evidence_file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'id');
    }

    /** @return HasMany<AttendanceCorrection, $this> */
    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class, 'employee_attendance_id', 'id');
    }

    /** @return HasMany<EmployeeAttendanceCapture, $this> */
    public function captures(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceCapture::class, 'employee_attendance_id', 'id');
    }
}
