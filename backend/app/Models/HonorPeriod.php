<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('honor_periods')]
#[Fillable([
    'code',
    'name',
    'start_date',
    'end_date',
    'status',
])]
class HonorPeriod extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'start_date' => 'immutable_date:Y-m-d',
            'end_date' => 'immutable_date:Y-m-d',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return HasMany<HonorDeduction, $this> */
    public function honorDeductions(): HasMany
    {
        return $this->hasMany(HonorDeduction::class, 'honor_period_id', 'id');
    }

    /** @return HasMany<Payroll, $this> */
    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class, 'honor_period_id', 'id');
    }

    /** @return HasMany<PayrollAdjustmentBatche, $this> */
    public function payrollAdjustmentBatches(): HasMany
    {
        return $this->hasMany(PayrollAdjustmentBatche::class, 'target_honor_period_id', 'id');
    }

    /** @return HasMany<PayrollAdjustment, $this> */
    public function payrollAdjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class, 'target_honor_period_id', 'id');
    }
}
