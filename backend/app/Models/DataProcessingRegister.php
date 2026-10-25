<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('data_processing_registers')]
#[Fillable([
    'code',
    'data_domain',
    'purpose',
    'legal_basis',
    'data_categories',
    'retention_policy_id',
    'responsible_role',
    'notes',
    'is_active',
])]
class DataProcessingRegister extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'is_active' => true,
        'lock_version' => 0,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'retention_policy_id' => 'integer',
            'is_active' => 'boolean',
            'data_categories' => 'array',
            'created_by' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    /** @return BelongsTo<RetentionPolicy, $this> */
    public function retentionPolicy(): BelongsTo
    {
        return $this->belongsTo(RetentionPolicy::class, 'retention_policy_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
