<?php

namespace Database\Factories;

use App\Models\ClassPeriod;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StudentEnrollment> */
class StudentEnrollmentFactory extends Factory
{
    protected $model = StudentEnrollment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'class_period_id' => ClassPeriod::factory(),
            'effective_from' => fn (array $attributes): string => ClassPeriod::query()->findOrFail($attributes['class_period_id'])->academicYear->start_date->toDateString(),
            'entry_reason' => 'admission',
            'created_by' => User::factory(),
        ];
    }
}
