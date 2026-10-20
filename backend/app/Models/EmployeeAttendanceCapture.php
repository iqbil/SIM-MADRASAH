<?php

namespace App\Models;

use Database\Factories\EmployeeAttendanceCaptureFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('employee_attendance_captures')]
#[Fillable([
    'employee_attendance_id',
    'event_type',
    'photo_file_id',
    'latitude',
    'longitude',
    'accuracy_meters',
    'photo_taken_at',
    'location_captured_at',
    'original_size_bytes',
    'photo_width',
    'photo_height',
    'compression_quality',
    'policy_snapshot',
    'operation_key',
])]
#[Hidden(['latitude', 'longitude', 'accuracy_meters', 'policy_snapshot', 'photoFile'])]
class EmployeeAttendanceCapture extends DomainModel
{
    /** @use HasFactory<EmployeeAttendanceCaptureFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'employee_attendance_id' => 'integer',
            'photo_file_id' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy_meters' => 'decimal:2',
            'photo_taken_at' => 'immutable_datetime',
            'location_captured_at' => 'immutable_datetime',
            'recorded_at' => 'immutable_datetime',
            'original_size_bytes' => 'integer',
            'photo_width' => 'integer',
            'photo_height' => 'integer',
            'compression_quality' => 'integer',
            'policy_snapshot' => 'array',
            'recorded_by' => 'integer',
        ];
    }

    /** @return BelongsTo<EmployeeAttendance, $this> */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendance::class, 'employee_attendance_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function photoFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'photo_file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'id');
    }
}
