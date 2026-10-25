<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('content_student_links')]
#[Fillable([
    'content_revision_id',
    'student_id',
    'link_type',
    'consent_id',
    'checked_at',
])]
class ContentStudentLink extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'content_revision_id' => 'integer',
            'student_id' => 'integer',
            'consent_id' => 'integer',
            'checked_at' => 'immutable_datetime',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<ContentRevision, $this> */
    public function contentRevision(): BelongsTo
    {
        return $this->belongsTo(ContentRevision::class, 'content_revision_id', 'id');
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    /** @return BelongsTo<StudentPublicationConsent, $this> */
    public function consent(): BelongsTo
    {
        return $this->belongsTo(StudentPublicationConsent::class, 'consent_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
