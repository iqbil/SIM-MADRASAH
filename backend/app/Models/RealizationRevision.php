<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

#[Table('realization_revisions')]
#[Fillable([
    'realization_id',
    'revision_number',
    'actual_employee_id',
    'substitute_assignment_id',
    'execution_status',
    'verification_status',
    'material',
    'actual_jp',
    'actual_minutes',
    'actual_started_at',
    'actual_ended_at',
    'nonexecution_reason',
    'notes',
    'revision_reason',
])]
class RealizationRevision extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'realization_id' => 'integer',
            'revision_number' => 'integer',
            'actual_employee_id' => 'integer',
            'substitute_assignment_id' => 'integer',
            'actual_jp' => 'decimal:3',
            'actual_minutes' => 'integer',
            'actual_started_at' => 'immutable_datetime',
            'actual_ended_at' => 'immutable_datetime',
            'submitted_at' => 'immutable_datetime',
            'submitted_by' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<TeachingRealization, $this> */
    public function realization(): BelongsTo
    {
        return $this->belongsTo(TeachingRealization::class, 'realization_id', 'id');
    }

    /** Pertemuan sumber realisasi (melalui teaching_realizations). @return HasOneThrough<Meeting, TeachingRealization, $this> */
    public function meeting(): HasOneThrough
    {
        return $this->hasOneThrough(Meeting::class, TeachingRealization::class, 'id', 'id', 'realization_id', 'meeting_id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function actualEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actual_employee_id', 'id');
    }

    /** @return BelongsTo<SubstituteAssignment, $this> */
    public function substituteAssignment(): BelongsTo
    {
        return $this->belongsTo(SubstituteAssignment::class, 'substitute_assignment_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasOne<RealizationVerification, $this> */
    public function verification(): HasOne
    {
        return $this->hasOne(RealizationVerification::class, 'realization_revision_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function payrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'realization_revision_id', 'id');
    }

    /** @return HasMany<PayrollAdjustment, $this> */
    public function payrollAdjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class, 'correction_realization_revision_id', 'id');
    }
}
