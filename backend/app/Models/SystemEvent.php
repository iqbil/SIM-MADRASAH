<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('system_events')]
#[Fillable([
    'event_type',
    'severity',
    'status',
    'occurred_at',
    'correlation_id',
    'safe_message',
    'metadata',
    'resolved_at',
])]
class SystemEvent extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'occurred_at' => 'immutable_datetime',
            'metadata' => 'array',
            'acknowledged_by' => 'integer',
            'acknowledged_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function acknowledger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by', 'id');
    }
}
