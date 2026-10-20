<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Guardian> */
class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'internal_code' => 'W-'.Str::uuid(),
            'is_active' => true,
        ];
    }
}
