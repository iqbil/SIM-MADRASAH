<?php

namespace Database\Factories;

use App\Models\BusinessDecision;
use App\Models\EmployeeAttendancePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EmployeeAttendancePolicy> */
class EmployeeAttendancePolicyFactory extends Factory
{
    protected $model = EmployeeAttendancePolicy::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'TEST-'.Str::upper(Str::random(16)),
            'version_number' => 2,
            'status' => 'baseline',
            'correction_deadline_days' => 7,
            'requires_correction_approval' => true,
            'allow_late_correction_with_mandate' => true,
            'requires_photo' => true,
            'requires_gps' => true,
            'photo_max_size_bytes' => 307200,
            'photo_max_dimension' => 1280,
            'photo_quality' => 80,
            'gps_max_accuracy_meters' => '100.00',
            'gps_max_age_seconds' => 60,
            'effective_from' => '2026-10-20',
            'decision_id' => BusinessDecision::factory(),
        ];
    }
}
