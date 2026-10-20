<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ClassPeriod;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClassPeriod> */
class ClassPeriodFactory extends Factory
{
    protected $model = ClassPeriod::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'class_id' => SchoolClass::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'display_name' => fn (array $attributes): string => SchoolClass::query()->findOrFail($attributes['class_id'])->name,
            'grade_snapshot' => fn (array $attributes): int => SchoolClass::query()->findOrFail($attributes['class_id'])->grade,
            'status' => 'open',
        ];
    }
}
