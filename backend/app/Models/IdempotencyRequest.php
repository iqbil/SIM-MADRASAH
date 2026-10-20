<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('idempotency_requests')]
#[Fillable([
    'operation',
    'request_key',
    'request_hash',
    'status',
    'response_reference',
    'expires_at',
])]
#[Hidden(['request_hash', 'response_reference'])]
class IdempotencyRequest extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'actor_user_id' => 'integer',
            'response_reference' => 'array',
            'expires_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'id');
    }
}
