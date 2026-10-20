<?php

namespace App\Models;

use Database\Factories\BusinessDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('business_decisions')]
#[Fillable([
    'code',
    'title',
    'status',
    'decision_text',
    'rationale',
    'source_reference',
    'supersedes_id',
    'version',
    'effective_from',
    'effective_to',
])]
class BusinessDecision extends DomainModel
{
    /** @use HasFactory<BusinessDecisionFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'recorded_at' => 'immutable_datetime',
            'owner_user_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'supersedes_id' => 'integer',
            'version' => 'integer',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /** @return BelongsTo<BusinessDecision, $this> */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(BusinessDecision::class, 'supersedes_id', 'id');
    }

    /** @return HasMany<WorkPatternVersion, $this> */
    public function workPatternVersions(): HasMany
    {
        return $this->hasMany(WorkPatternVersion::class, 'decision_id', 'id');
    }

    /** @return HasMany<EmployeeAttendancePolicy, $this> */
    public function employeeAttendancePolicies(): HasMany
    {
        return $this->hasMany(EmployeeAttendancePolicy::class, 'decision_id', 'id');
    }

    /** @return HasMany<AttendancePolicy, $this> */
    public function attendancePolicies(): HasMany
    {
        return $this->hasMany(AttendancePolicy::class, 'decision_id', 'id');
    }

    /** @return HasMany<HonorPolicyVersion, $this> */
    public function honorPolicyVersions(): HasMany
    {
        return $this->hasMany(HonorPolicyVersion::class, 'decision_id', 'id');
    }

    /** @return HasMany<LetterNumberSequence, $this> */
    public function letterNumberSequences(): HasMany
    {
        return $this->hasMany(LetterNumberSequence::class, 'decision_id', 'id');
    }

    /** @return HasMany<RetentionPolicy, $this> */
    public function retentionPolicies(): HasMany
    {
        return $this->hasMany(RetentionPolicy::class, 'decision_id', 'id');
    }
}
