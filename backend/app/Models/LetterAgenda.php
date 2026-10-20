<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('letter_agendas')]
#[Fillable([
    'letter_id',
    'agenda_sequence_id',
    'agenda_number',
    'agenda_date',
    'note',
])]
class LetterAgenda extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'letter_id' => 'integer',
            'agenda_sequence_id' => 'integer',
            'agenda_number' => 'integer',
            'agenda_date' => 'immutable_date:Y-m-d',
            'recorded_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Letter, $this> */
    public function letter(): BelongsTo
    {
        return $this->belongsTo(Letter::class, 'letter_id', 'id');
    }

    /** @return BelongsTo<LetterNumberSequence, $this> */
    public function agendaSequence(): BelongsTo
    {
        return $this->belongsTo(LetterNumberSequence::class, 'agenda_sequence_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by', 'id');
    }
}
