<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('subject_topics')]
#[Fillable([
    'subject_id',
    'grade',
    'code',
    'name',
    'is_active',
])]
class SubjectTopic extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => 1,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'subject_id' => 'integer',
            'grade' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    /** @return HasMany<QuestionVersion, $this> */
    public function questionVersions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class, 'topic_id', 'id');
    }
}
