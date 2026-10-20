<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('audit_logs')]
#[Fillable([
    'actor_label_snapshot',
    'event',
    'object_type',
    'object_id',
    'occurred_at',
    'reason',
    'before_values',
    'after_values',
    'request_id',
    'ip_address',
    'user_agent',
    'classification',
])]
#[Hidden(['before_values', 'after_values', 'ip_address', 'user_agent'])]
class AuditLog extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'actor_user_id' => 'integer',
            'occurred_at' => 'immutable_datetime',
            'before_values' => 'array',
            'after_values' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'id');
    }
}
