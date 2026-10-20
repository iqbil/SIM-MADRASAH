<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('subjects')]
#[Fillable([
    'code',
    'name',
    'group_name',
    'is_active',
])]
class Subject extends DomainModel
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
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<TeachingAssignment, $this> */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'subject_id', 'id');
    }

    /** @return HasMany<Meeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'subject_id', 'id');
    }

    /** @return HasMany<SubjectTopic, $this> */
    public function subjectTopics(): HasMany
    {
        return $this->hasMany(SubjectTopic::class, 'subject_id', 'id');
    }

    /** @return HasMany<Question, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'subject_id', 'id');
    }

    /** @return HasMany<ExamPackage, $this> */
    public function examPackages(): HasMany
    {
        return $this->hasMany(ExamPackage::class, 'subject_id', 'id');
    }

    /** @return HasMany<SourceRecord, $this> */
    public function sourceRecords(): HasMany
    {
        return $this->hasMany(SourceRecord::class, 'subject_id', 'id');
    }
}
