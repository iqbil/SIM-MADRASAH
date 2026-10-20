<?php

namespace App\Models;

use Database\Factories\ClassPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('class_periods')]
#[Fillable([
    'class_id',
    'academic_year_id',
    'display_name',
    'grade_snapshot',
    'capacity',
    'status',
])]
class ClassPeriod extends DomainModel
{
    /** @use HasFactory<ClassPeriodFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'class_id' => 'integer',
            'academic_year_id' => 'integer',
            'grade_snapshot' => 'integer',
            'capacity' => 'integer',
        ];
    }

    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id', 'id');
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'id');
    }

    /** @return HasMany<AuthorizationMandate, $this> */
    public function authorizationMandates(): HasMany
    {
        return $this->hasMany(AuthorizationMandate::class, 'class_period_id', 'id');
    }

    /** @return HasMany<HomeroomAssignment, $this> */
    public function homeroomAssignments(): HasMany
    {
        return $this->hasMany(HomeroomAssignment::class, 'class_period_id', 'id');
    }

    /** @return HasMany<TeachingAssignment, $this> */
    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'class_period_id', 'id');
    }

    /** @return HasMany<StudentEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'class_period_id', 'id');
    }

    /** @return HasMany<AcademicCalendarEvent, $this> */
    public function academicCalendarEvents(): HasMany
    {
        return $this->hasMany(AcademicCalendarEvent::class, 'class_period_id', 'id');
    }

    /** @return HasMany<Meeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class, 'class_period_id', 'id');
    }

    /** @return HasMany<StudentDailyAttendance, $this> */
    public function studentDailyAttendances(): HasMany
    {
        return $this->hasMany(StudentDailyAttendance::class, 'class_period_id', 'id');
    }

    /** @return HasMany<PortalAnnouncementTarget, $this> */
    public function portalAnnouncementTargets(): HasMany
    {
        return $this->hasMany(PortalAnnouncementTarget::class, 'class_period_id', 'id');
    }
}
