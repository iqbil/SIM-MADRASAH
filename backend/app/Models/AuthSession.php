<?php

namespace App\Models;

use Database\Factories\AuthSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('auth_sessions')]
#[Hidden(['public_id', 'access_jti_hash'])]
class AuthSession extends DomainModel
{
    /** @use HasFactory<AuthSessionFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'user_id' => 'integer',
            'expires_at' => 'immutable_datetime',
            'last_used_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** @return HasMany<AuthRefreshToken, $this> */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(AuthRefreshToken::class, 'auth_session_id', 'id');
    }
}
