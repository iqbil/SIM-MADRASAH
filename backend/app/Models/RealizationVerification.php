<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('realization_verifications')]
#[Fillable([
    'realization_revision_id',
    'action',
    'mandate_id',
    'reason',
    'operation_key',
])]
class RealizationVerification extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'realization_revision_id' => 'integer',
            'verifier_user_id' => 'integer',
            'mandate_id' => 'integer',
            'verified_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<RealizationRevision, $this> */
    public function realizationRevision(): BelongsTo
    {
        return $this->belongsTo(RealizationRevision::class, 'realization_revision_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_user_id', 'id');
    }

    /** @return BelongsTo<AuthorizationMandate, $this> */
    public function mandate(): BelongsTo
    {
        return $this->belongsTo(AuthorizationMandate::class, 'mandate_id', 'id');
    }

    /** @return HasMany<PayrollLineSource, $this> */
    public function payrollLineSources(): HasMany
    {
        return $this->hasMany(PayrollLineSource::class, 'verification_id', 'id');
    }
}
