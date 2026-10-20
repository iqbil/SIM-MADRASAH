<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('bk_followups')]
#[Fillable([
    'case_id',
    'note_id',
    'assigned_employee_id',
    'due_date',
    'status',
    'internal_plan',
    'internal_result',
    'completed_at',
])]
#[Hidden(['internal_plan', 'internal_result'])]
class BkFollowup extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'case_id' => 'integer',
            'note_id' => 'integer',
            'assigned_employee_id' => 'integer',
            'due_date' => 'immutable_date:Y-m-d',
            'completed_at' => 'immutable_datetime',
            'created_by' => 'integer',
        ];
    }

    /** @return BelongsTo<BkCase, $this> */
    public function bkCase(): BelongsTo
    {
        return $this->belongsTo(BkCase::class, 'case_id', 'id');
    }

    /** @return BelongsTo<Employee, $this> */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /** @return BelongsTo<BkNote, $this> */
    public function note(): BelongsTo
    {
        return $this->belongsTo(BkNote::class, 'note_id', 'id');
    }

    /** @return HasMany<BkFile, $this> */
    public function bkFiles(): HasMany
    {
        return $this->hasMany(BkFile::class, 'followup_id', 'id');
    }
}
