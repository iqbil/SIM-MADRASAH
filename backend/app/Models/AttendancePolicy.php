<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('attendance_policies')]
#[Fillable([
    'semester_id',
    'version_number',
    'status',
    'mode',
    'daily_summary_rule',
    'input_deadline_time',
    'correction_deadline_days',
    'requires_correction_approval',
    'allow_late_correction_with_mandate',
    'effective_from',
    'effective_to',
    'decision_id',
])]
class AttendancePolicy extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'requires_correction_approval' => false,
        'allow_late_correction_with_mandate' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'semester_id' => 'integer',
            'version_number' => 'integer',
            'daily_summary_rule' => 'array',
            'correction_deadline_days' => 'integer',
            'requires_correction_approval' => 'boolean',
            'allow_late_correction_with_mandate' => 'boolean',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'decision_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Semester, $this> */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'id');
    }

    /** @return BelongsTo<BusinessDecision, $this> */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(BusinessDecision::class, 'decision_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    /** @return HasMany<StudentDailyAttendance, $this> */
    public function studentDailyAttendances(): HasMany
    {
        return $this->hasMany(StudentDailyAttendance::class, 'policy_id', 'id');
    }

    /** @return HasMany<StudentMeetingAttendance, $this> */
    public function studentMeetingAttendances(): HasMany
    {
        return $this->hasMany(StudentMeetingAttendance::class, 'policy_id', 'id');
    }
}
