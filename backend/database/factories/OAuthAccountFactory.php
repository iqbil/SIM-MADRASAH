<?php

namespace Database\Factories;

use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<OAuthAccount> */
class OAuthAccountFactory extends Factory
{
    protected $model = OAuthAccount::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), 'provider' => 'google', 'provider_user_id' => (string) Str::uuid(),
        ];
    }
}
