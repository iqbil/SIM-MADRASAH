<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('question_keys')]
#[Fillable([
    'question_version_id',
    'correct_option_ids',
    'answer_text',
    'explanation',
    'scoring_rubric',
])]
#[Hidden(['correct_option_ids', 'answer_text', 'explanation', 'scoring_rubric'])]
class QuestionKey extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'question_version_id' => 'integer',
            'correct_option_ids' => 'array',
            'scoring_rubric' => 'array',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<QuestionVersion, $this> */
    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class, 'question_version_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
