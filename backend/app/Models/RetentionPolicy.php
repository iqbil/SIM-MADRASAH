<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('retention_policies')]
#[Fillable([
    'code',
    'version_number',
    'domain',
    'retention_days',
    'retention_rule',
    'disposal_method',
    'status',
    'effective_from',
    'effective_to',
    'decision_id',
])]
class RetentionPolicy extends DomainModel
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
            'retention_days' => 'integer',
            'retention_rule' => 'array',
            'effective_from' => 'immutable_date:Y-m-d',
            'effective_to' => 'immutable_date:Y-m-d',
            'decision_id' => 'integer',
            'approved_by' => 'integer',
            'approved_at' => 'immutable_datetime',
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

    /** @return HasMany<File, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'retention_policy_id', 'id');
    }

    /** @return HasMany<BkCategory, $this> */
    public function bkCategories(): HasMany
    {
        return $this->hasMany(BkCategory::class, 'retention_policy_id', 'id');
    }

    /** @return HasMany<DocumentCategory, $this> */
    public function documentCategories(): HasMany
    {
        return $this->hasMany(DocumentCategory::class, 'retention_policy_id', 'id');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'retention_policy_id', 'id');
    }

    /** @return HasMany<BackupRun, $this> */
    public function backupRuns(): HasMany
    {
        return $this->hasMany(BackupRun::class, 'retention_policy_id', 'id');
    }
}
