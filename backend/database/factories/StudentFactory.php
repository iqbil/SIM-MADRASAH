<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Student;
use App\Models\StudentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Student> */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'internal_code' => 'S-'.Str::uuid(),
            'admitted_on' => '2026-07-01',
            'status_id' => fn (): int => StudentStatus::query()->firstOrCreate(['code' => 'active'], ['name' => 'Aktif', 'is_terminal' => false, 'is_active' => true])->id,
        ];
    }
}
