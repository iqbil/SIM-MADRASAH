<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('authorization_mandates')]
#[Fillable([
    'user_id',
    'permission_id',
    'scope_type',
    'semester_id',
    'class_period_id',
    'employee_id',
    'effective_from',
    'effective_to',
    'reason',
])]
class AuthorizationMandate extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'user_id' => 'integer',
            'permission_id' => 'integer',
            'semester_id' => 'integer',
            'class_period_id' => 'integer',
            'employee_id' => 'integer',
            'effective_from' => 'immutable_datetime',
            'effective_to' => 'immutable_datetime',
            'granted_by' => 'integer',
            'revoked_at' => 'immutable_datetime',
            'revoked_by' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /** @return BelongsTo<Permission, $this> */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'id');
    }

    /** @return BelongsTo<Semester, $this> */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class, 'semester_id', 'id');
    }

    /** @return BelongsTo<ClassPeriod, $this> */
    public function classPeriod(): BelongsTo
    {
        return $this->belongsTo(ClassPeriod::class, 'class_period_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function grantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by', 'id');
    }

    /** @return HasMany<RealizationVerification, $this> */
    public function realizationVerifications(): HasMany
    {
        return $this->hasMany(RealizationVerification::class, 'mandate_id', 'id');
    }

    /** @return HasMany<PayrollAction, $this> */
    public function payrollActions(): HasMany
    {
        return $this->hasMany(PayrollAction::class, 'mandate_id', 'id');
    }

    /** @return HasMany<PublicationAction, $this> */
    public function publicationActions(): HasMany
    {
        return $this->hasMany(PublicationAction::class, 'mandate_id', 'id');
    }
}
