<?php

namespace Database\Factories;

use App\Models\EmployeeAttendance;
use App\Models\EmployeeAttendanceCapture;
use App\Models\File;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EmployeeAttendanceCapture> */
class EmployeeAttendanceCaptureFactory extends Factory
{
    protected $model = EmployeeAttendanceCapture::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'employee_attendance_id' => EmployeeAttendance::factory(),
            'event_type' => 'check_in',
            'photo_file_id' => fn (array $attributes): int => File::factory()->create(['uploaded_by' => EmployeeAttendance::query()->findOrFail($attributes['employee_attendance_id'])->recorded_by])->id,
            'latitude' => '-6.2000000',
            'longitude' => '106.8166667',
            'accuracy_meters' => '15.00',
            'photo_taken_at' => '2026-10-20 00:00:50',
            'location_captured_at' => '2026-10-20 00:00:45',
            'recorded_at' => '2026-10-20 00:01:00',
            'original_size_bytes' => 2097152,
            'photo_width' => 960,
            'photo_height' => 1280,
            'compression_quality' => 80,
            'policy_snapshot' => function (array $attributes): array {
                $policy = EmployeeAttendance::query()->findOrFail($attributes['employee_attendance_id'])->workDay->policy;

                return ['policy_id' => $policy->id, 'version_number' => $policy->version_number, 'requires_photo' => $policy->requires_photo, 'requires_gps' => $policy->requires_gps, 'photo_max_size_bytes' => $policy->photo_max_size_bytes, 'photo_max_dimension' => $policy->photo_max_dimension, 'photo_quality' => $policy->photo_quality, 'gps_max_accuracy_meters' => $policy->gps_max_accuracy_meters, 'gps_max_age_seconds' => $policy->gps_max_age_seconds];
            },
            'recorded_by' => fn (array $attributes): int => EmployeeAttendance::query()->findOrFail($attributes['employee_attendance_id'])->recorded_by,
            'operation_key' => (string) Str::uuid(),
        ];
    }
}
