<?php

namespace App\Models;

use Database\Factories\EmployeeAttendancePolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('employee_attendance_policies')]
#[Fillable([
    'code',
    'version_number',
    'status',
    'correction_deadline_days',
    'requires_correction_approval',
    'allow_late_correction_with_mandate',
    'effective_from',
    'effective_to',
    'decision_id',
    'requires_photo',
    'requires_gps',
    'photo_max_size_bytes',
    'photo_max_dimension',
    'photo_quality',
    'gps_max_accuracy_meters',
    'gps_max_age_seconds',
    'geofence_enabled',
    'geofence_name',
    'geofence_latitude',
    'geofence_longitude',
    'geofence_radius_meters',
])]
class EmployeeAttendancePolicy extends DomainModel
{
    public const GEOFENCE_FIELDS = ['geofence_enabled', 'geofence_name', 'geofence_latitude', 'geofence_longitude', 'geofence_radius_meters'];

    public const CAPTURE_FIELDS = ['id', 'requires_photo', 'requires_gps', 'photo_max_dimension', 'photo_quality', 'photo_max_size_bytes', 'gps_max_accuracy_meters', 'gps_max_age_seconds', ...self::GEOFENCE_FIELDS];

    public const PUBLIC_FIELDS = [...self::CAPTURE_FIELDS, 'code', 'version_number', 'status', 'correction_deadline_days', 'effective_from', 'effective_to', 'updated_at'];

    /** @use HasFactory<EmployeeAttendancePolicyFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'requires_correction_approval' => false,
        'allow_late_correction_with_mandate' => false,
        'requires_photo' => false,
        'requires_gps' => false,
        'photo_max_size_bytes' => 307200,
        'photo_max_dimension' => 1280,
        'photo_quality' => 80,
        'gps_max_accuracy_meters' => 100,
        'gps_max_age_seconds' => 60,
        'geofence_enabled' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'version_number' => 'integer',
            'correction_deadline_days' => 'integer',
            'requires_correction_approval' => 'boolean',
            'allow_late_correction_with_mandate' => 'boolean',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'decision_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'requires_photo' => 'boolean',
            'requires_gps' => 'boolean',
            'photo_max_size_bytes' => 'integer',
            'photo_max_dimension' => 'integer',
            'photo_quality' => 'integer',
            'gps_max_accuracy_meters' => 'decimal:2',
            'gps_max_age_seconds' => 'integer',
            'geofence_enabled' => 'boolean',
            'geofence_latitude' => 'decimal:7',
            'geofence_longitude' => 'decimal:7',
            'geofence_radius_meters' => 'integer',
        ];
    }

    /** @return BelongsTo<BusinessDecision, $this> */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(BusinessDecision::class, 'decision_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /** @return HasMany<EmployeeWorkDay, $this> */
    public function employeeWorkDays(): HasMany
    {
        return $this->hasMany(EmployeeWorkDay::class, 'policy_id', 'id');
    }
}
