<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('letters')]
#[Fillable([
    'direction',
    'status',
    'category_id',
    'number_sequence_id',
    'sequence_number',
    'formatted_number',
    'external_number',
    'letter_date',
    'received_on',
    'sender',
    'recipient',
    'subject',
    'body',
    'cancel_reason',
])]
class Letter extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'category_id' => 'integer',
            'number_sequence_id' => 'integer',
            'sequence_number' => 'integer',
            'letter_date' => 'immutable_date:Y-m-d',
            'received_on' => 'immutable_date:Y-m-d',
            'issued_by' => 'integer',
            'issued_at' => 'immutable_datetime',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<DocumentCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id', 'id');
    }

    /** @return BelongsTo<LetterNumberSequence, $this> */
    public function numberSequence(): BelongsTo
    {
        return $this->belongsTo(LetterNumberSequence::class, 'number_sequence_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasOne<LetterAgenda, $this> */
    public function agenda(): HasOne
    {
        return $this->hasOne(LetterAgenda::class, 'letter_id', 'id');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'letter_id', 'id');
    }

    /** @return HasMany<LetterDisposition, $this> */
    public function letterDispositions(): HasMany
    {
        return $this->hasMany(LetterDisposition::class, 'letter_id', 'id');
    }
}
