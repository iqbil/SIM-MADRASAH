<?php

namespace Database\Factories;

use App\Models\AuthRefreshToken;
use App\Models\AuthSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AuthRefreshToken> */
class AuthRefreshTokenFactory extends Factory
{
    protected $model = AuthRefreshToken::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'auth_session_id' => AuthSession::factory(), 'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now('UTC')->addHours(2),
        ];
    }
}
