<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuthRefreshToken;
use App\Models\AuthSession;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JwtTokenService
{
    /** @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, refresh_expires_in: int} */
    public function issue(User $user, Request $request, string $method): array
    {
        $session = AuthSession::query()->forceCreate([
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'access_jti_hash' => str_repeat('0', 64),
            'login_method' => $method,
            'expires_at' => now('UTC')->addMinutes(max(1, (int) config('jwt.refresh_ttl'))),
            'last_used_at' => now('UTC'),
        ]);
        $tokens = $this->tokens($user, $session);
        $this->audit($user, 'auth.login', $request, $session);

        return $tokens;
    }

    public function authenticate(Request $request): ?User
    {
        $encoded = $request->bearerToken();
        if (! is_string($encoded) || strlen($encoded) > 8192) {
            return null;
        }
        $secret = $this->secret();
        try {
            $claims = JWT::decode($encoded, new Key($secret, 'HS256'));
        } catch (\UnexpectedValueException|\DomainException|\InvalidArgumentException $exception) {
            return null;
        }
        if (! is_string($claims->sub ?? null) || ! ctype_digit($claims->sub)
            || ! is_string($claims->sid ?? null) || ! Str::isUuid($claims->sid)
            || ! is_string($claims->jti ?? null) || ! Str::isUuid($claims->jti)
            || ($claims->iss ?? null) !== config('jwt.issuer')
            || ($claims->aud ?? null) !== config('jwt.audience')
            || ($claims->token_use ?? null) !== 'access'
            || ! is_int($claims->exp ?? null) || ! is_int($claims->iat ?? null) || ! is_int($claims->nbf ?? null)) {
            return null;
        }
        $session = AuthSession::query()->with('user.person')
            ->where('public_id', $claims->sid)->where('user_id', $claims->sub)
            ->whereNull('revoked_at')->where('expires_at', '>', now('UTC'))->first();
        if ($session === null || ! hash_equals($session->access_jti_hash, hash('sha256', $claims->jti))) {
            return null;
        }
        if (! $session->user->canSignIn()) {
            $session->forceFill(['revoked_at' => now('UTC')])->save();

            return null;
        }
        $request->attributes->set('auth_session', $session);

        return $session->user;
    }

    /** @return array{tokens: array<string, mixed>, user: User} */
    public function refresh(string $rawToken, Request $request): array
    {
        $known = AuthRefreshToken::query()->with('session')->where('token_hash', hash('sha256', $rawToken))->first();
        if ($known === null) {
            throw new AuthenticationException('Refresh token tidak valid.', ['api']);
        }
        $result = DB::transaction(function () use ($known, $request): ?array {
            $user = User::query()->lockForUpdate()->findOrFail($known->session->user_id);
            $session = AuthSession::query()->lockForUpdate()->findOrFail($known->auth_session_id);
            $refresh = AuthRefreshToken::query()->lockForUpdate()->findOrFail($known->id);
            if ($refresh->consumed_at !== null) {
                $session->forceFill(['revoked_at' => now('UTC')])->save();
                $this->audit($user, 'auth.refresh_reuse', $request, $session);

                return null;
            }
            if ($session->revoked_at !== null || $session->expires_at->isPast() || $refresh->expires_at->isPast() || ! $user->canSignIn()) {
                return null;
            }
            abort_if($user->must_change_password, 403, 'Ganti sandi sementara sebelum memperbarui token.');
            $refresh->forceFill(['consumed_at' => now('UTC')])->save();
            $tokens = $this->tokens($user, $session);
            $this->audit($user, 'auth.refresh', $request, $session);

            return ['tokens' => $tokens, 'user' => $user];
        }, 3);
        if ($result === null) {
            throw new AuthenticationException('Refresh token tidak valid.', ['api']);
        }

        return $result;
    }

    public function logout(User $user, AuthSession $session, Request $request): void
    {
        DB::transaction(function () use ($user, $session, $request): void {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $session = AuthSession::query()->lockForUpdate()->findOrFail($session->id);
            $session->forceFill(['revoked_at' => now('UTC')])->save();
            $this->audit($user, 'auth.logout', $request, $session);
        }, 3);
    }

    public function logoutAll(User $user, Request $request): void
    {
        DB::transaction(function () use ($user, $request): void {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $user->authSessions()->whereNull('revoked_at')->update(['revoked_at' => now('UTC')]);
            $this->audit($user, 'auth.logout_all', $request);
        }, 3);
    }

    public function audit(User $user, string $event, Request $request, ?AuthSession $session = null): void
    {
        AuditLog::query()->forceCreate([
            'actor_user_id' => $user->id,
            'actor_label_snapshot' => Str::limit($user->name, 200, ''),
            'event' => $event,
            'object_type' => 'user',
            'object_id' => (string) $user->id,
            'occurred_at' => now('UTC'),
            'request_id' => (string) Str::uuid(),
            'ip_address' => $request->ip(),
            'classification' => 'internal',
            'after_values' => $session === null ? null : ['session_id' => $session->public_id, 'login_method' => $session->login_method],
        ]);
    }

    /** @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, refresh_expires_in: int} */
    private function tokens(User $user, AuthSession $session): array
    {
        $now = now('UTC')->timestamp;
        $expiry = min($now + max(1, (int) config('jwt.access_ttl')) * 60, $session->expires_at->timestamp);
        $jti = (string) Str::uuid();
        $jwt = JWT::encode([
            'iss' => config('jwt.issuer'), 'aud' => config('jwt.audience'), 'sub' => (string) $user->id,
            'sid' => $session->public_id, 'jti' => $jti, 'iat' => $now, 'nbf' => $now,
            'exp' => $expiry, 'token_use' => 'access',
        ], $this->secret(), 'HS256');
        $refresh = bin2hex(random_bytes(32));
        $session->forceFill(['access_jti_hash' => hash('sha256', $jti), 'last_used_at' => now('UTC')])->save();
        AuthRefreshToken::query()->forceCreate([
            'auth_session_id' => $session->id, 'token_hash' => hash('sha256', $refresh), 'expires_at' => $session->expires_at,
        ]);

        return ['access_token' => $jwt, 'refresh_token' => $refresh, 'token_type' => 'Bearer', 'expires_in' => $expiry - $now, 'refresh_expires_in' => $session->expires_at->timestamp - $now];
    }

    private function secret(): string
    {
        $secret = config('jwt.secret');
        abort_unless(is_string($secret) && strlen($secret) >= 32, 503, 'Konfigurasi JWT belum siap.');

        return $secret;
    }
}
