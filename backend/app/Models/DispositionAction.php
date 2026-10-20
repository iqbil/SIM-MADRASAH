<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('disposition_actions')]
#[Fillable([
    'disposition_id',
    'action',
    'from_status',
    'to_status',
    'note',
    'acted_at',
])]
class DispositionAction extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'disposition_id' => 'integer',
            'actor_user_id' => 'integer',
            'acted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<LetterDisposition, $this> */
    public function disposition(): BelongsTo
    {
        return $this->belongsTo(LetterDisposition::class, 'disposition_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'id');
    }
}
