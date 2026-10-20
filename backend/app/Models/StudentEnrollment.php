<?php

namespace App\Models;

use Database\Factories\StudentEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('student_enrollments')]
#[Fillable([
    'student_id',
    'class_period_id',
    'effective_from',
    'effective_to',
    'entry_reason',
    'exit_reason',
])]
class StudentEnrollment extends DomainModel
{
    /** @use HasFactory<StudentEnrollmentFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'student_id' => 'integer',
            'class_period_id' => 'integer',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /** @return BelongsTo<ClassPeriod, $this> */
    public function classPeriod(): BelongsTo
    {
        return $this->belongsTo(ClassPeriod::class, 'class_period_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<StudentDailyAttendance, $this> */
    public function studentDailyAttendances(): HasMany
    {
        return $this->hasMany(StudentDailyAttendance::class, 'enrollment_id', 'id');
    }

    /** @return HasMany<StudentMeetingAttendance, $this> */
    public function studentMeetingAttendances(): HasMany
    {
        return $this->hasMany(StudentMeetingAttendance::class, 'enrollment_id', 'id');
    }

    /** @return HasMany<StudentTransition, $this> */
    public function fromEnrollmentStudentTransitions(): HasMany
    {
        return $this->hasMany(StudentTransition::class, 'from_enrollment_id', 'id');
    }

    /** @return HasMany<StudentTransition, $this> */
    public function toEnrollmentStudentTransitions(): HasMany
    {
        return $this->hasMany(StudentTransition::class, 'to_enrollment_id', 'id');
    }
}
