<?php

namespace App\Models;

use Database\Factories\WorkPatternVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('work_pattern_versions')]
#[Fillable([
    'work_pattern_id',
    'version_number',
    'day_of_week',
    'start_time',
    'end_time',
    'crosses_midnight',
    'late_tolerance_minutes',
    'effective_from',
    'effective_to',
    'decision_id',
])]
class WorkPatternVersion extends DomainModel
{
    /** @use HasFactory<WorkPatternVersionFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'crosses_midnight' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'work_pattern_id' => 'integer',
            'version_number' => 'integer',
            'day_of_week' => 'integer',
            'crosses_midnight' => 'boolean',
            'late_tolerance_minutes' => 'integer',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'decision_id' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<WorkPattern, $this> */
    public function workPattern(): BelongsTo
    {
        return $this->belongsTo(WorkPattern::class, 'work_pattern_id', 'id');
    }

    /** @return BelongsTo<BusinessDecision, $this> */
    public function decision(): BelongsTo
    {
        return $this->belongsTo(BusinessDecision::class, 'decision_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<EmployeeWorkDay, $this> */
    public function employeeWorkDays(): HasMany
    {
        return $this->hasMany(EmployeeWorkDay::class, 'work_pattern_version_id', 'id');
    }
}
