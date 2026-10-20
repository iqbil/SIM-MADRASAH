<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('letter_number_sequences')]
#[Fillable([
    'scope_key',
    'scope_type',
    'year',
    'prefix',
    'format_template',
    'next_value',
    'decision_id',
])]
class LetterNumberSequence extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'year' => 'integer',
            'next_value' => 'integer',
            'decision_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<BusinessDecision, $this> */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(BusinessDecision::class, 'decision_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<Letter, $this> */
    public function letters(): HasMany
    {
        return $this->hasMany(Letter::class, 'number_sequence_id', 'id');
    }

    /** @return HasMany<LetterAgenda, $this> */
    public function letterAgendas(): HasMany
    {
        return $this->hasMany(LetterAgenda::class, 'agenda_sequence_id', 'id');
    }
}
