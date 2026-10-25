<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Table('users')]
#[Fillable([
    'person_id',
    'name',
    'username',
    'email',
    'password',
])]
#[Hidden(['password', 'remember_token', 'temporary_password_expires_at', 'temporary_password_used_at'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $dateFormat = 'Y-m-d H:i:s.u';

    /** @var array<string, mixed> */
    protected $attributes = [
        'must_change_password' => false,
        'lock_version' => 0,
        'status' => 'active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'email_verified_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'person_id' => 'integer',
            'disabled_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'must_change_password' => 'boolean',
            'temporary_password_expires_at' => 'immutable_datetime',
            'temporary_password_used_at' => 'immutable_datetime',
            'lock_version' => 'integer',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (User $user): void {
            if ($user->wasChanged(['password', 'status', 'must_change_password', 'email'])) {
                $user->authSessions()->whereNull('revoked_at')->update(['revoked_at' => now('UTC')]);
            }
        });
    }

    public function canSignIn(): bool
    {
        if ($this->status !== 'active' || ! $this->person?->is_active) {
            return false;
        }

        return ! $this->person->student()->exists()
            || $this->person->employee()->exists()
            || $this->person->guardian()->exists();
    }

    /** @return HasMany<AuthSession, $this> */
    public function authSessions(): HasMany
    {
        return $this->hasMany(AuthSession::class, 'user_id', 'id');
    }

    /** @return HasMany<OAuthAccount, $this> */
    public function oauthAccounts(): HasMany
    {
        return $this->hasMany(OAuthAccount::class, 'user_id', 'id');
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }

    /** @return HasMany<UserRole, $this> */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(UserRole::class, 'user_id', 'id');
    }

    /** @return HasMany<AuthorizationMandate, $this> */
    public function authorizationMandates(): HasMany
    {
        return $this->hasMany(AuthorizationMandate::class, 'user_id', 'id');
    }

    /** @return HasMany<BkCategoryGrant, $this> */
    public function bkCategoryGrants(): HasMany
    {
        return $this->hasMany(BkCategoryGrant::class, 'user_id', 'id');
    }

    /** @return HasMany<BkSummaryRecipient, $this> */
    public function bkSummaryRecipients(): HasMany
    {
        return $this->hasMany(BkSummaryRecipient::class, 'recipient_user_id', 'id');
    }

    /** @return HasMany<DocumentCategoryGrant, $this> */
    public function documentCategoryGrants(): HasMany
    {
        return $this->hasMany(DocumentCategoryGrant::class, 'user_id', 'id');
    }

    /** @return HasMany<LetterDisposition, $this> */
    public function letterDispositions(): HasMany
    {
        return $this->hasMany(LetterDisposition::class, 'assigned_to_user_id', 'id');
    }

    /** @return HasMany<UserNotification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'recipient_user_id', 'id');
    }
}
