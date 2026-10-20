<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('question_options')]
#[Fillable([
    'question_version_id',
    'option_key',
    'position',
    'label',
    'body',
    'body_format',
])]
class QuestionOption extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'question_version_id' => 'integer',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<QuestionVersion, $this> */
    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class, 'question_version_id', 'id');
    }

    /** @return HasMany<ExamPackageOption, $this> */
    public function examPackageOptions(): HasMany
    {
        return $this->hasMany(ExamPackageOption::class, 'source_option_id', 'id');
    }

    /** @return HasMany<QuestionFile, $this> */
    public function questionFiles(): HasMany
    {
        return $this->hasMany(QuestionFile::class, 'option_id', 'id');
    }
}
