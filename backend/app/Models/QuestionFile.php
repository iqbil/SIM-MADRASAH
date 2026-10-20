<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('question_files')]
#[Fillable([
    'question_version_id',
    'option_id',
    'file_id',
    'purpose',
    'position',
])]
class QuestionFile extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'question_version_id' => 'integer',
            'option_id' => 'integer',
            'file_id' => 'integer',
            'position' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<QuestionVersion, $this> */
    public function questionVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class, 'question_version_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<QuestionOption, $this> */
    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class, 'option_id', 'id');
    }
}
