<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('payroll_lines')]
#[Fillable([
    'payroll_version_id',
    'component_id',
    'line_key',
    'component_code_snapshot',
    'description_snapshot',
    'component_type_snapshot',
    'quantity',
    'rate_snapshot',
    'amount',
    'direction',
    'formula_snapshot',
])]
#[Hidden(['formula_snapshot'])]
class PayrollLine extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'payroll_version_id' => 'integer',
            'component_id' => 'integer',
            'quantity' => 'decimal:3',
            'rate_snapshot' => 'decimal:2',
            'amount' => 'decimal:2',
            'formula_snapshot' => 'array',
        ];
    }

    /** @return BelongsTo<PayrollVersion, $this> */
    public function payrollVersion(): BelongsTo
    {
        return $this->belongsTo(PayrollVersion::class, 'payroll_version_id', 'id');
    }

    /** @return BelongsTo<HonorComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(HonorComponent::class, 'component_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'payroll_line_id', 'id');
    }
}
