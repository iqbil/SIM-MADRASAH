<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Semester> */
class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'number' => 1,
            'name' => 'Ganjil',
            'start_date' => fn (array $attributes): string => AcademicYear::query()->findOrFail($attributes['academic_year_id'])->start_date->toDateString(),
            'end_date' => fn (array $attributes): string => AcademicYear::query()->findOrFail($attributes['academic_year_id'])->start_date->addMonths(6)->toDateString(),
            'status' => 'open',
            'created_by' => User::factory(),
        ];
    }
}
