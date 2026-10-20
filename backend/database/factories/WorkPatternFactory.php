<?php

namespace Database\Factories;

use App\Models\WorkPattern;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<WorkPattern> */
class WorkPatternFactory extends Factory
{
    protected $model = WorkPattern::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => 'TEST-'.Str::upper(Str::random(16)),
            'name' => 'Pola kerja fixture',
            'is_active' => true,
        ];
    }
}
