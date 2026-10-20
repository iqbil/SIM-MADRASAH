<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('payroll_actions')]
#[Fillable([
    'payroll_version_id',
    'action',
    'from_status',
    'to_status',
    'mandate_id',
    'reason',
    'acted_at',
    'operation_key',
])]
class PayrollAction extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'payroll_version_id' => 'integer',
            'actor_user_id' => 'integer',
            'mandate_id' => 'integer',
            'acted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PayrollVersion, $this> */
    public function payrollVersion(): BelongsTo
    {
        return $this->belongsTo(PayrollVersion::class, 'payroll_version_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id', 'id');
    }

    /** @return BelongsTo<AuthorizationMandate, $this> */
    public function mandate(): BelongsTo
    {
        return $this->belongsTo(AuthorizationMandate::class, 'mandate_id', 'id');
    }
}
