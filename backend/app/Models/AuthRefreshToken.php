<?php

namespace App\Models;

use Database\Factories\AuthRefreshTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('auth_refresh_tokens')]
#[Hidden(['token_hash'])]
class AuthRefreshToken extends DomainModel
{
    /** @use HasFactory<AuthRefreshTokenFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'auth_session_id' => 'integer',
            'expires_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<AuthSession, $this> */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AuthSession::class, 'auth_session_id', 'id');
    }
}
