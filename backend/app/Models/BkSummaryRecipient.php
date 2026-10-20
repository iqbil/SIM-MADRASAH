<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('bk_summary_recipients')]
#[Fillable([
    'summary_id',
    'recipient_user_id',
    'student_guardian_id',
    'homeroom_assignment_id',
    'expires_at',
    'revoke_reason',
])]
class BkSummaryRecipient extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'summary_id' => 'integer',
            'recipient_user_id' => 'integer',
            'student_guardian_id' => 'integer',
            'homeroom_assignment_id' => 'integer',
            'shared_by' => 'integer',
            'shared_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<BkSummary, $this> */
    public function summary(): BelongsTo
    {
        return $this->belongsTo(BkSummary::class, 'summary_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id', 'id');
    }

    /** @return BelongsTo<StudentGuardian, $this> */
    public function studentGuardian(): BelongsTo
    {
        return $this->belongsTo(StudentGuardian::class, 'student_guardian_id', 'id');
    }

    /** @return BelongsTo<HomeroomAssignment, $this> */
    public function homeroomAssignment(): BelongsTo
    {
        return $this->belongsTo(HomeroomAssignment::class, 'homeroom_assignment_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function sharer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by', 'id');
    }
}
