<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('data_subject_requests')]
#[Fillable([
    'request_number',
    'subject_person_id',
    'on_behalf_of_guardian_id',
    'request_type',
    'detail',
    'identity_verified',
    'verification_evidence_file_id',
    'status',
    'due_at',
    'handled_by',
    'handled_at',
    'result_summary',
    'rejection_reason',
])]
class DataSubjectRequest extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'identity_verified' => false,
        'status' => 'received',
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'subject_person_id' => 'integer',
            'on_behalf_of_guardian_id' => 'integer',
            'identity_verified' => 'boolean',
            'verification_evidence_file_id' => 'integer',
            'due_at' => 'immutable_datetime',
            'handled_by' => 'integer',
            'handled_at' => 'immutable_datetime',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function subjectPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'subject_person_id', 'id');
    }

    /** @return BelongsTo<StudentGuardian, $this> */
    public function onBehalfOfGuardian(): BelongsTo
    {
        return $this->belongsTo(StudentGuardian::class, 'on_behalf_of_guardian_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function verificationEvidenceFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'verification_evidence_file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
