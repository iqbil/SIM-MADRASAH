<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('questions')]
#[Fillable([
    'subject_id',
    'code',
    'current_version_id',
    'status',
])]
class Question extends DomainModel
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
            'owner_employee_id' => 'integer',
            'subject_id' => 'integer',
            'current_version_id' => 'integer',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'owner_employee_id', 'id');
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<QuestionVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(QuestionVersion::class, 'current_version_id', 'id');
    }

    /** @return HasMany<QuestionVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(QuestionVersion::class, 'question_id', 'id');
    }

    /** @return HasMany<QuestionCollaborator, $this> */
    public function collaborators(): HasMany
    {
        return $this->hasMany(QuestionCollaborator::class, 'question_id', 'id');
    }
}
