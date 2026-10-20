<?php

namespace Database\Factories;

use App\Models\AuthSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuthSession> */
class AuthSessionFactory extends Factory
{
    protected $model = AuthSession::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(), 'user_id' => User::factory(),
            'access_jti_hash' => hash('sha256', (string) Str::uuid()), 'login_method' => 'password',
            'expires_at' => now('UTC')->addHours(2), 'last_used_at' => now('UTC'),
        ];
    }
}
