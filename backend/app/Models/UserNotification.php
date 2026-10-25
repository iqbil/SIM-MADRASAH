<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notifikasi in-app per pengguna (FR-17/DEC-20). Model sengaja bernama
 * UserNotification agar tidak bentrok dengan facade Notification bawaan
 * Laravel; tabel fisik tetap `notifications` sesuai migration.
 *
 * Payload hanya boleh referensi entitas (tipe+ID); title/body tidak boleh
 * memuat BK internal, rincian honor pihak lain, atau bukti foto/GPS.
 */
#[Table('notifications')]
#[Fillable([
    'recipient_user_id',
    'category',
    'title',
    'body',
    'payload',
    'operation_key',
    'read_at',
    'created_by',
])]
class UserNotification extends DomainModel
{
    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            ...parent::casts(),
            'id' => 'integer',
            'recipient_user_id' => 'integer',
            'payload' => 'array',
            'read_at' => 'immutable_datetime',
            'created_by' => 'integer',
        ];
    }

    /** @param Builder<self> $query */
    public function scopeUnread($query): Builder
    {
        return $query->whereNull('read_at');
    }

    /** @return BelongsTo<User, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id', 'id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
