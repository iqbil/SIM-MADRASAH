<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'name' => fn (array $attributes): string => Person::query()->findOrFail($attributes['person_id'])->full_name,
            'username' => 'user_'.Str::lower(Str::random(20)),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now('UTC'),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => ['email_verified_at' => null]);
    }

    public function withoutEmail(): static
    {
        return $this->state(fn (array $attributes): array => ['email' => null, 'email_verified_at' => null]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'disabled', 'disabled_at' => now('UTC')]);
    }
}
