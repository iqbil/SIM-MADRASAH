<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('academic_contexts')]
#[Fillable([
    'singleton_key',
    'active_academic_year_id',
    'active_semester_id',
])]
class AcademicContext extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'singleton_key' => 'integer',
            'active_academic_year_id' => 'integer',
            'active_semester_id' => 'integer',
            'changed_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function activeAcademicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'active_academic_year_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by', 'id');
    }

    /** @return BelongsTo<Semester, $this> */
    public function activeSemester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'active_semester_id', 'id');
    }
}
