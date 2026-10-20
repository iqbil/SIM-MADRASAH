<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('student_transitions')]
#[Fillable([
    'student_id',
    'from_enrollment_id',
    'to_enrollment_id',
    'from_status_id',
    'to_status_id',
    'type',
    'effective_date',
    'reason',
    'batch_key',
    'operation_key',
])]
class StudentTransition extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'student_id' => 'integer',
            'from_enrollment_id' => 'integer',
            'to_enrollment_id' => 'integer',
            'from_status_id' => 'integer',
            'to_status_id' => 'integer',
            'effective_date' => 'immutable_date:Y-m-d',
            'confirmed_by' => 'integer',
            'confirmed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /** @return BelongsTo<StudentStatus, $this> */
    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(StudentStatus::class, 'from_status_id', 'id');
    }

    /** @return BelongsTo<StudentStatus, $this> */
    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(StudentStatus::class, 'to_status_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by', 'id');
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function fromEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'from_enrollment_id', 'id');
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function toEnrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'to_enrollment_id', 'id');
    }
}
