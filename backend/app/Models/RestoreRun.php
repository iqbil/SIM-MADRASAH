<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('restore_runs')]
#[Fillable([
    'backup_run_id',
    'target_environment',
    'status',
    'started_at',
    'finished_at',
    'measured_rpo_seconds',
    'measured_rto_seconds',
    'validation_result',
    'evidence_file_id',
    'error_message',
])]
class RestoreRun extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'backup_run_id' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'measured_rpo_seconds' => 'integer',
            'measured_rto_seconds' => 'integer',
            'validation_result' => 'array',
            'evidence_file_id' => 'integer',
            'performed_by' => 'integer',
        ];
    }

    /** @return BelongsTo<BackupRun, $this> */
    public function backupRun(): BelongsTo
    {
        return $this->belongsTo(BackupRun::class, 'backup_run_id', 'id');
    }

    /** @return BelongsTo<File, $this> */
    public function evidenceFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'evidence_file_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by', 'id');
    }
}
