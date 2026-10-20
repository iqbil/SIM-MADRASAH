<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('backup_runs')]
#[Fillable([
    'backup_key',
    'status',
    'started_at',
    'finished_at',
    'database_snapshot_reference',
    'file_manifest_reference',
    'destination_reference',
    'is_encrypted',
    'encryption_key_reference',
    'checksum',
    'total_size_bytes',
    'retention_policy_id',
    'expires_at',
    'error_code',
    'error_message',
    'application_version',
    'schema_version',
])]
#[Hidden(['database_snapshot_reference', 'file_manifest_reference', 'destination_reference', 'encryption_key_reference', 'error_message'])]
class BackupRun extends DomainModel
{
    /** @var array<string, mixed> */
    protected $attributes = [
        'is_encrypted' => false,
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'is_encrypted' => 'boolean',
            'total_size_bytes' => 'integer',
            'retention_policy_id' => 'integer',
            'expires_at' => 'immutable_datetime',
            'initiated_by' => 'integer',
        ];
    }

    /** @return BelongsTo<RetentionPolicy, $this> */
    public function retentionPolicy(): BelongsTo
    {
        return $this->belongsTo(RetentionPolicy::class, 'retention_policy_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by', 'id');
    }

    /** @return HasMany<RestoreRun, $this> */
    public function restoreRuns(): HasMany
    {
        return $this->hasMany(RestoreRun::class, 'backup_run_id', 'id');
    }
}
