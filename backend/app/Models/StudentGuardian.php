<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('student_guardians')]
#[Fillable([
    'student_id',
    'guardian_id',
    'relationship',
    'is_primary',
    'verification_status',
    'verification_evidence_file_id',
    'effective_from',
    'effective_to',
    'reason',
])]
class StudentGuardian extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'is_primary' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'student_id' => 'integer',
            'guardian_id' => 'integer',
            'is_primary' => 'boolean',
            'verified_by' => 'integer',
            'verified_at' => 'immutable_datetime',
            'verification_evidence_file_id' => 'integer',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'revoked_at' => 'immutable_datetime',
            'revoked_by' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /** @return BelongsTo<Guardian, $this> */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function verificationEvidenceFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'verification_evidence_file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<BkSummaryRecipient, $this> */
    public function bkSummaryRecipients(): HasMany
    {
        return $this->hasMany(BkSummaryRecipient::class, 'student_guardian_id', 'id');
    }

    /** @return HasMany<StudentPublicationConsent, $this> */
    public function givenPublicationConsents(): HasMany
    {
        return $this->hasMany(StudentPublicationConsent::class, 'given_by_guardian_id', 'id');
    }
}
