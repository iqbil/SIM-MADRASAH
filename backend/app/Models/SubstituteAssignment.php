<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('substitute_assignments')]
#[Fillable([
    'meeting_id',
    'substitute_employee_id',
    'reason',
    'revoke_reason',
])]
class SubstituteAssignment extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'meeting_id' => 'integer',
            'substitute_employee_id' => 'integer',
            'assigned_by' => 'integer',
            'assigned_at' => 'immutable_datetime',
            'revoked_at' => 'immutable_datetime',
            'revoked_by' => 'integer',
            'active_marker' => 'integer',
        ];
    }

    /** @return BelongsTo<Meeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class, 'meeting_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function substituteEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'substitute_employee_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by', 'id');
    }

    /** @return HasMany<RealizationRevision, $this> */
    public function realizationRevisions(): HasMany
    {
        return $this->hasMany(RealizationRevision::class, 'substitute_assignment_id', 'id');
    }
}
