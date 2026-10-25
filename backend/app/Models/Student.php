<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('students')]
#[Fillable([
    'person_id',
    'internal_code',
    'nis',
    'nisn',
    'admitted_on',
    'status_id',
    'archived_at',
])]
class Student extends DomainModel
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'person_id' => 'integer',
            'admitted_on' => 'immutable_date:Y-m-d',
            'status_id' => 'integer',
            'archived_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }

    /** @return BelongsTo<StudentStatus, $this> */
    public function status(): BelongsTo
    {
        return $this->belongsTo(StudentStatus::class, 'status_id', 'id');
    }

    /** @return HasMany<StudentGuardian, $this> */
    public function guardianRelationships(): HasMany
    {
        return $this->hasMany(StudentGuardian::class, 'student_id', 'id');
    }

    /** @return HasMany<StudentEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id', 'id');
    }

    /** @return HasMany<StudentTransition, $this> */
    public function studentTransitions(): HasMany
    {
        return $this->hasMany(StudentTransition::class, 'student_id', 'id');
    }

    /** @return HasMany<StudentDailyAttendance, $this> */
    public function studentDailyAttendances(): HasMany
    {
        return $this->hasMany(StudentDailyAttendance::class, 'student_id', 'id');
    }

    /** @return HasMany<StudentMeetingAttendance, $this> */
    public function studentMeetingAttendances(): HasMany
    {
        return $this->hasMany(StudentMeetingAttendance::class, 'student_id', 'id');
    }

    /** @return HasMany<BkCase, $this> */
    public function bkCases(): HasMany
    {
        return $this->hasMany(BkCase::class, 'student_id', 'id');
    }

    /** @return HasMany<PortalAnnouncementTarget, $this> */
    public function portalAnnouncementTargets(): HasMany
    {
        return $this->hasMany(PortalAnnouncementTarget::class, 'student_id', 'id');
    }

    /** @return HasMany<SourceRecord, $this> */
    public function sourceRecords(): HasMany
    {
        return $this->hasMany(SourceRecord::class, 'student_id', 'id');
    }

    /** @return HasMany<StudentPublicationConsent, $this> */
    public function publicationConsents(): HasMany
    {
        return $this->hasMany(StudentPublicationConsent::class, 'student_id', 'id');
    }

    /** @return HasMany<ContentStudentLink, $this> */
    public function contentStudentLinks(): HasMany
    {
        return $this->hasMany(ContentStudentLink::class, 'student_id', 'id');
    }
}
