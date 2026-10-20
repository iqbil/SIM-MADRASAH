<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('honor_policy_versions')]
#[Fillable([
    'code',
    'version_number',
    'status',
    'source_basis',
    'jp_minutes',
    'recognized_quantity_rule',
    'rate_selection_rule',
    'rounding_rule',
    'substitute_rule',
    'holiday_rule',
    'allowance_rule',
    'deduction_rule',
    'approval_rule',
    'effective_from',
    'effective_to',
    'decision_id',
])]
class HonorPolicyVersion extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'version_number' => 'integer',
            'jp_minutes' => 'integer',
            'recognized_quantity_rule' => 'array',
            'rate_selection_rule' => 'array',
            'rounding_rule' => 'array',
            'substitute_rule' => 'array',
            'holiday_rule' => 'array',
            'allowance_rule' => 'array',
            'deduction_rule' => 'array',
            'approval_rule' => 'array',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'decision_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'created_by' => 'integer',
        ];
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

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<HonorRate, $this> */
    public function honorRates(): HasMany
    {
        return $this->hasMany(HonorRate::class, 'policy_version_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function payrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'policy_version_id', 'id');
    }
}
