<?php

namespace App\Models;

use Database\Factories\SemesterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('semesters')]
#[Fillable([
    'academic_year_id',
    'number',
    'name',
    'start_date',
    'end_date',
    'status',
])]
class Semester extends DomainModel
{
    /** @use HasFactory<SemesterFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'academic_year_id' => 'integer',
            'number' => 'integer',
            'start_date' => 'immutable_date:Y-m-d',
            'end_date' => 'immutable_date:Y-m-d',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<AuthorizationMandate, $this> */
    public function authorizationMandates(): HasMany
    {
        return $this->hasMany(AuthorizationMandate::class, 'semester_id', 'id');
    }

    /** @return HasMany<HomeroomAssignment, $this> */
    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'semester_id', 'id');
    }

    /** @return HasMany<TeachingAssignment, $this> */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'semester_id', 'id');
    }

    /** @return HasMany<AcademicCalendarEvent, $this> */
    public function academicCalendarEvents(): HasMany
    {
        return $this->hasMany(AcademicCalendarEvent::class, 'semester_id', 'id');
    }

    /** @return HasMany<LessonSlot, $this> */
    public function lessonSlots(): HasMany
    {
        return $this->hasMany(LessonSlot::class, 'semester_id', 'id');
    }

    /** @return HasMany<Timetable, $this> */
    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class, 'semester_id', 'id');
    }

    /** @return HasMany<Meeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'semester_id', 'id');
    }

    /** @return HasMany<AttendancePolicy, $this> */
    public function attendancePolicies(): HasMany
    {
        return $this->hasMany(AttendancePolicy::class, 'semester_id', 'id');
    }

    /** @return HasMany<ExamPackage, $this> */
    public function examPackages(): HasMany
    {
        return $this->hasMany(ExamPackage::class, 'semester_id', 'id');
    }
}
