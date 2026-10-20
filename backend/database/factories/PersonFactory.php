<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Person> */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'sex' => fake()->randomElement(['L', 'P']),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'is_active' => true,
        ];
    }
}
