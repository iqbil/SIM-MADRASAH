<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('student_publication_consents')]
#[Fillable([
    'student_id',
    'consent_scope',
    'given_by_guardian_id',
    'consent_document_version',
    'given_at',
    'effective_from',
    'effective_to',
    'withdrawn_at',
    'withdrawn_by',
    'withdraw_reason',
])]
class StudentPublicationConsent extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'student_id' => 'integer',
            'given_by_guardian_id' => 'integer',
            'given_at' => 'immutable_datetime',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'withdrawn_at' => 'immutable_datetime',
            'withdrawn_by' => 'integer',
            'created_by' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return $this->withdrawn_at === null;
    }

    /** @param Builder<self> $query */
    public function scopeActive($query): Builder
    {
        return $query->whereNull('withdrawn_at');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /** @return BelongsTo<StudentGuardian, $this> */
    public function givenByGuardian(): BelongsTo
    {
        return $this->belongsTo(StudentGuardian::class, 'given_by_guardian_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function withdrawnBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<ContentStudentLink, $this> */
    public function contentStudentLinks(): HasMany
    {
        return $this->hasMany(ContentStudentLink::class, 'consent_id', 'id');
    }
}
