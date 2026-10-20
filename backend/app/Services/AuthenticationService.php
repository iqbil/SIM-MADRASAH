<?php

namespace App\Services;

use App\Models\OAuthAccount;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as GoogleUser;

class AuthenticationService
{
    public function __construct(private JwtTokenService $tokens) {}

    /** @return array{user: User, tokens: array<string, mixed>} */
    public function login(string $login, string $password, Request $request): array
    {
        return DB::transaction(function () use ($login, $password, $request): array {
            $user = User::query()->where('username', $login)->lockForUpdate()->first();
            if ($user === null && filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $user = User::query()->where('email', $login)->lockForUpdate()->first();
            }
            if ($user === null || ! Hash::check($password, $user->password) || ! $user->canSignIn()) {
                throw new AuthenticationException('Kredensial tidak valid atau akun tidak dapat digunakan.', ['api']);
            }
            if ($user->must_change_password) {
                if ($user->temporary_password_expires_at === null || $user->temporary_password_expires_at->isPast() || $user->temporary_password_used_at !== null) {
                    throw new AuthenticationException('Kredensial tidak valid atau akun tidak dapat digunakan.', ['api']);
                }
                $user->forceFill(['temporary_password_used_at' => now('UTC')]);
            }
            $user->forceFill(['last_login_at' => now('UTC')])->save();

            return ['user' => $user, 'tokens' => $this->tokens->issue($user, $request, 'password')];
        }, 3);
    }

    /** @return array{user: User, tokens: array<string, mixed>} */
    public function google(GoogleUser $google, Request $request): array
    {
        $subject = $google->getId();
        $email = $google->getEmail();
        if (! is_string($subject) || $subject === '' || strlen($subject) > 255
            || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || ($google->user['email_verified'] ?? null) !== true) {
            throw new AuthenticationException('Akun Google tidak dapat digunakan.', ['api']);
        }

        return DB::transaction(function () use ($subject, $email, $request): array {
            $identity = OAuthAccount::query()->where('provider', 'google')->where('provider_user_id', $subject)->first();
            $query = User::query()->lockForUpdate();
            $user = $identity === null ? $query->where('email', $email)->first() : $query->find($identity->user_id);
            if ($user === null || ! $user->canSignIn() || $user->must_change_password
                || $user->email_verified_at === null || strcasecmp((string) $user->email, $email) !== 0) {
                throw new AuthenticationException('Akun Google belum diizinkan untuk login.', ['api']);
            }
            $linked = OAuthAccount::query()->where('user_id', $user->id)->where('provider', 'google')->lockForUpdate()->first();
            if ($linked !== null && ! hash_equals($linked->provider_user_id, $subject)) {
                throw new AuthenticationException('Akun Google belum diizinkan untuk login.', ['api']);
            }
            if ($linked === null) {
                OAuthAccount::query()->forceCreate(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => $subject]);
                $this->tokens->audit($user, 'auth.google_linked', $request);
            }
            $user->forceFill(['last_login_at' => now('UTC')])->save();

            return ['user' => $user, 'tokens' => $this->tokens->issue($user, $request, 'google')];
        }, 3);
    }

    public function changePassword(User $user, string $currentPassword, string $password, Request $request): void
    {
        DB::transaction(function () use ($user, $currentPassword, $password, $request): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            if (! $user->canSignIn()) {
                throw new AuthenticationException('Akun tidak dapat digunakan.', ['api']);
            }
            if (! Hash::check($currentPassword, $user->password)) {
                throw ValidationException::withMessages(['current_password' => 'Sandi saat ini tidak sesuai.']);
            }
            if (Hash::check($password, $user->password)) {
                throw ValidationException::withMessages(['password' => 'Sandi baru harus berbeda dari sandi saat ini.']);
            }
            $user->forceFill([
                'password' => $password, 'must_change_password' => false,
                'temporary_password_expires_at' => null, 'temporary_password_used_at' => null,
            ])->save();
            $user->authSessions()->whereNull('revoked_at')->update(['revoked_at' => now('UTC')]);
            $this->tokens->audit($user, 'auth.password_changed', $request);
        }, 3);
    }
}
