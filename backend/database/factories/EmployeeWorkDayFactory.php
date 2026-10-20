<?php

namespace Database\Factories;

use App\Models\EmployeeAttendancePolicy;
use App\Models\EmployeeWorkAssignment;
use App\Models\EmployeeWorkDay;
use App\Models\WorkPatternVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeWorkDay> */
class EmployeeWorkDayFactory extends Factory
{
    protected $model = EmployeeWorkDay::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'work_assignment_id' => EmployeeWorkAssignment::factory(),
            'employee_id' => fn (array $attributes): int => EmployeeWorkAssignment::query()->findOrFail($attributes['work_assignment_id'])->employee_id,
            'work_pattern_version_id' => function (array $attributes): int {
                $assignment = EmployeeWorkAssignment::query()->findOrFail($attributes['work_assignment_id']);

                return WorkPatternVersion::query()->where('work_pattern_id', $assignment->work_pattern_id)->where('version_number', 1)->where('day_of_week', 2)->value('id')
                    ?? WorkPatternVersion::factory()->create(['work_pattern_id' => $assignment->work_pattern_id])->id;
            },
            'policy_id' => EmployeeAttendancePolicy::factory(),
            'work_date' => '2026-10-20',
            'shift_number' => 1,
            'scheduled_start_at' => '2026-10-20 00:00:00',
            'scheduled_end_at' => '2026-10-20 07:00:00',
            'tolerance_minutes_snapshot' => 10,
            'is_work_required' => true,
        ];
    }
}
