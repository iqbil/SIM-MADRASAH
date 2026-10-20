<?php

namespace Database\Factories;

use App\Models\BusinessDecision;
use App\Models\User;
use App\Models\WorkPattern;
use App\Models\WorkPatternVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkPatternVersion> */
class WorkPatternVersionFactory extends Factory
{
    protected $model = WorkPatternVersion::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'work_pattern_id' => WorkPattern::factory(),
            'version_number' => 1,
            'day_of_week' => 2,
            'start_time' => '07:00:00',
            'end_time' => '14:00:00',
            'crosses_midnight' => false,
            'late_tolerance_minutes' => 10,
            'effective_from' => '2026-10-20',
            'decision_id' => BusinessDecision::factory(),
            'created_by' => User::factory(),
        ];
    }
}
