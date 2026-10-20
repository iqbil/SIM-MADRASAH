<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('question_types')]
#[Fillable([
    'code',
    'name',
    'has_options',
    'is_active',
])]
class QuestionType extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'has_options' => false,
        'is_active' => 1,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'has_options' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<QuestionVersion, $this> */
    public function questionVersions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class, 'question_type_id', 'id');
    }
}
