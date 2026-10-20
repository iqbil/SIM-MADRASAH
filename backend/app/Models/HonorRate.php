<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('honor_rates')]
#[Fillable([
    'rate_scope_id',
    'amount',
    'unit',
    'effective_from',
    'effective_to',
    'policy_version_id',
    'reason',
])]
class HonorRate extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'rate_scope_id' => 'integer',
            'amount' => 'decimal:2',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'policy_version_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<HonorRateScope, $this> */
    public function rateScope(): BelongsTo
    {
        return $this->belongsTo(HonorRateScope::class, 'rate_scope_id', 'id');
    }

    /** @return BelongsTo<HonorPolicyVersion, $this> */
    public function policyVersion(): BelongsTo
    {
        return $this->belongsTo(HonorPolicyVersion::class, 'policy_version_id', 'id');
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

    /** @return HasMany<PayrollLineSource, $this> */
    public function payrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'rate_id', 'id');
    }
}
