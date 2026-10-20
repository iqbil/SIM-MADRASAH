<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Table('question_versions')]
#[Fillable([
    'question_id',
    'version_number',
    'question_type_id',
    'topic_id',
    'grade',
    'difficulty',
    'cognitive_level',
    'body',
    'body_format',
    'revision_reason',
])]
#[Hidden(['key'])]
class QuestionVersion extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'question_id' => 'integer',
            'version_number' => 'integer',
            'question_type_id' => 'integer',
            'topic_id' => 'integer',
            'grade' => 'integer',
            'cognitive_level' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id', 'id');
    }

    /** @return BelongsTo<QuestionType, $this> */
    public function questionType(): BelongsTo
    {
        return $this->belongsTo(QuestionType::class, 'question_type_id', 'id');
    }

    /** @return BelongsTo<SubjectTopic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(SubjectTopic::class, 'topic_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<QuestionOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'question_version_id', 'id');
    }

    /** @return HasOne<QuestionKey, $this> */
    public function key(): HasOne
    {
        return $this->hasOne(QuestionKey::class, 'question_version_id', 'id');
    }

    /** @return HasMany<QuestionFile, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(QuestionFile::class, 'question_version_id', 'id');
    }

    /** @return HasMany<ExamPackageItem, $this> */
    public function examPackageItems(): HasMany
    {
        return $this->hasMany(ExamPackageItem::class, 'question_version_id', 'id');
    }
}
