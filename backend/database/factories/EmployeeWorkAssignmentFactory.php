<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeWorkAssignment;
use App\Models\User;
use App\Models\WorkPattern;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeWorkAssignment> */
class EmployeeWorkAssignmentFactory extends Factory
{
    protected $model = EmployeeWorkAssignment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'work_pattern_id' => WorkPattern::factory(),
            'effective_from' => '2026-10-20',
            'created_by' => User::factory(),
        ];
    }
}
