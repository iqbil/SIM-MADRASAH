<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'employee_number' => 'EMP-'.Str::uuid(),
            'joined_on' => '2026-07-01',
            'is_active' => true,
        ];
    }
}
