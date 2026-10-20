<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcademicYear> */
class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $year = fake()->unique()->numberBetween(2020, 2200);

        return [
            'code' => $year.'/'.($year + 1),
            'name' => $year.'/'.($year + 1),
            'start_date' => $year.'-07-01',
            'end_date' => ($year + 1).'-07-01',
            'status' => 'open',
            'created_by' => User::factory(),
        ];
    }
}
