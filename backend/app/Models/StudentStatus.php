<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('student_statuses')]
#[Fillable([
    'code',
    'name',
    'is_terminal',
    'is_active',
])]
class StudentStatus extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'is_terminal' => false,
        'is_active' => 1,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'is_terminal' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<Student, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'status_id', 'id');
    }

    /** @return HasMany<StudentTransition, $this> */
    public function fromStatusStudentTransitions(): HasMany
    {
        return $this->hasMany(StudentTransition::class, 'from_status_id', 'id');
    }

    /** @return HasMany<StudentTransition, $this> */
    public function toStatusStudentTransitions(): HasMany
    {
        return $this->hasMany(StudentTransition::class, 'to_status_id', 'id');
    }
}
