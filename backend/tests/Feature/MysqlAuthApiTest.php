<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\AuthRefreshToken;
use App\Models\AuthSession;
use App\Models\Student;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;
use Tests\MysqlAuthTestCase;

class MysqlAuthApiTest extends MysqlAuthTestCase
{
    public function test_login_issues_signed_jwt_and_hashed_refresh_token_with_minimal_profile(): void
    {
        $user = User::factory()->create();
        $response = $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'password'])->assertOk();
        $data = $response->json('data');
        $claims = JWT::decode($data['access_token'], new Key(config('jwt.secret'), 'HS256'));

        $this->assertSame((string) $user->id, $claims->sub);
        $this->assertSame('sim-madrasah-api', $claims->aud);
        $this->assertSame('Bearer', $data['token_type']);
        $this->assertSame(900, $data['expires_in']);
        $this->assertSame(7200, $data['refresh_expires_in']);
        $this->assertSame($user->username, $data['user']['username']);
        foreach (['password', 'remember_token', 'temporary_password_used_at', 'person_id'] as $field) {
            $this->assertArrayNotHasKey($field, $data['user']);
        }
        $this->assertDatabaseHas('auth_refresh_tokens', ['token_hash' => hash('sha256', $data['refresh_token'])]);
        $this->assertDatabaseMissing('auth_refresh_tokens', ['token_hash' => $data['refresh_token']]);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $audit = AuditLog::query()->get()->toJson();
        $this->assertStringNotContainsString($data['access_token'], $audit);
        $this->assertStringNotContainsString($data['refresh_token'], $audit);
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$data['access_token']])->assertOk()->assertJsonPath('data.id', $user->id);
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_username_login_works_without_an_email(): void
    {
        $user = User::factory()->withoutEmail()->create();
        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'password'])->assertOk()->assertJsonPath('data.user.email', null);
    }

    public function test_wrong_credentials_disabled_person_and_student_only_accounts_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'wrong'])->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', ['login' => 'missing', 'password' => 'password'])->assertUnauthorized();
        $user->person->update(['is_active' => false]);
        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'password'])->assertUnauthorized();
        $student = Student::factory()->create();
        $account = User::factory()->create(['person_id' => $student->person_id]);
        $this->postJson('/api/v1/auth/login', ['login' => $account->username, 'password' => 'password'])->assertUnauthorized();
        $this->assertDatabaseCount('auth_sessions', 0);
    }

    public function test_login_validation_and_rate_limits_are_enforced(): void
    {
        $this->postJson('/api/v1/auth/login', ['login' => ['invalid'], 'password' => 'password'])->assertUnprocessable()->assertJsonValidationErrors('login');
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'wrong'])->assertUnauthorized();
        }
        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'password'])->assertTooManyRequests();
    }

    public function test_forged_wrong_audience_expired_and_missing_claim_tokens_are_rejected(): void
    {
        $data = $this->loginUser(User::factory()->create());
        $claims = (array) JWT::decode($data['access_token'], new Key(config('jwt.secret'), 'HS256'));
        foreach ([['aud' => 'another-app'], ['iss' => 'another-issuer'], ['exp' => time() - 60], ['token_use' => 'refresh'], ['sub' => 1], ['jti' => 'not-a-uuid']] as $changes) {
            $jwt = JWT::encode(array_replace($claims, $changes), config('jwt.secret'), 'HS256');
            $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$jwt])->assertUnauthorized();
        }
        unset($claims['exp']);
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.JWT::encode($claims, config('jwt.secret'), 'HS256')])->assertUnauthorized();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.JWT::encode(['sub' => '1'], str_repeat('wrong-secret-', 8), 'HS256')])->assertUnauthorized();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer malformed.token'])->assertUnauthorized();
    }

    public function test_refresh_rotates_both_tokens_and_replay_revokes_the_session(): void
    {
        $data = $this->loginUser(User::factory()->create());
        $rotated = $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $data['refresh_token']])->assertOk()->json('data');
        $this->assertNotSame($data['refresh_token'], $rotated['refresh_token']);
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$data['access_token']])->assertUnauthorized();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$rotated['access_token']])->assertOk();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $data['refresh_token']])->assertUnauthorized();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$rotated['access_token']])->assertUnauthorized();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $rotated['refresh_token']])->assertUnauthorized();
        $this->assertNotNull(AuthSession::query()->sole()->revoked_at);
        $this->assertDatabaseHas('audit_logs', ['event' => 'auth.refresh_reuse']);
    }

    public function test_random_and_expired_refresh_tokens_are_rejected(): void
    {
        $data = $this->loginUser(User::factory()->create());
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => str_repeat('a', 64)])->assertUnauthorized();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $data['access_token']])->assertUnprocessable();
        AuthRefreshToken::query()->update(['expires_at' => now('UTC')->subMinute()]);
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $data['refresh_token']])->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_device_and_logout_all_revokes_every_device(): void
    {
        $user = User::factory()->create();
        $first = $this->loginUser($user);
        $second = $this->loginUser($user);
        $this->postJson('/api/v1/auth/logout', [], ['Authorization' => 'Bearer '.$first['access_token']])->assertOk();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$first['access_token']])->assertUnauthorized();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $first['refresh_token']])->assertUnauthorized();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$second['access_token']])->assertOk();
        $this->postJson('/api/v1/auth/logout-all', [], ['Authorization' => 'Bearer '.$second['access_token']])->assertOk();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$second['access_token']])->assertUnauthorized();
    }

    public function test_disabling_and_reenabling_account_does_not_resurrect_old_tokens(): void
    {
        $user = User::factory()->create();
        $data = $this->loginUser($user);
        $user->forceFill(['status' => 'disabled', 'disabled_at' => now('UTC')])->save();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$data['access_token']])->assertUnauthorized();
        $user->forceFill(['status' => 'active', 'disabled_at' => null])->save();
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$data['access_token']])->assertUnauthorized();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $data['refresh_token']])->assertUnauthorized();
    }

    public function test_password_change_checks_old_password_and_revokes_all_sessions(): void
    {
        $user = User::factory()->create();
        $data = $this->loginUser($user);
        $headers = ['Authorization' => 'Bearer '.$data['access_token']];
        $this->putJson('/api/v1/auth/password', ['current_password' => 'incorrect', 'password' => 'NewSecurePassword123', 'password_confirmation' => 'NewSecurePassword123'], $headers)->assertUnprocessable();
        $this->putJson('/api/v1/auth/password', ['current_password' => 'password', 'password' => 'NewSecurePassword123', 'password_confirmation' => 'NewSecurePassword123'], $headers)->assertOk();
        $this->assertTrue(Hash::check('NewSecurePassword123', $user->fresh()->password));
        $this->getJson('/api/v1/auth/me', $headers)->assertUnauthorized();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $data['refresh_token']])->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'NewSecurePassword123'])->assertOk();
    }

    public function test_temporary_password_can_only_login_once_and_requires_change(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true, 'temporary_password_expires_at' => now('UTC')->addDay()])->save();
        $data = $this->loginUser($user);
        $headers = ['Authorization' => 'Bearer '.$data['access_token']];
        $this->assertNotNull($user->fresh()->temporary_password_used_at);
        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'password'])->assertUnauthorized();
        $this->getJson('/api/v1/auth/me', $headers)->assertOk()->assertJsonPath('data.must_change_password', true);
        $this->postJson('/api/v1/auth/logout-all', [], $headers)->assertForbidden();
        $this->postJson('/api/v1/auth/refresh', ['refresh_token' => $data['refresh_token']])->assertForbidden();
        $this->putJson('/api/v1/auth/password', ['current_password' => 'password', 'password' => 'NewSecurePassword123', 'password_confirmation' => 'NewSecurePassword123'], $headers)->assertOk();
        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertNull($user->fresh()->temporary_password_expires_at);
        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'NewSecurePassword123'])->assertOk();
    }

    public function test_expired_temporary_password_does_not_issue_a_session(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['must_change_password' => true, 'temporary_password_expires_at' => now('UTC')->subMinute()])->save();
        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'password'])->assertUnauthorized();
        $this->assertDatabaseCount('auth_sessions', 0);
    }

    public function test_options_and_cors_are_configurable_and_public_registration_is_unavailable(): void
    {
        $this->getJson('/api/v1/auth/options')->assertOk()->assertJsonPath('data.google', false)->assertJsonPath('data.password', true);
        $this->getJson('/api/v1/auth/google/redirect')->assertNotFound();
        $this->postJson('/api/v1/auth/register', ['name' => 'Unauthorized signup'])->assertNotFound();
        $this->assertDatabaseCount('users', 0);
        $this->options('/api/v1/auth/login', [], ['Origin' => 'http://localhost:3000', 'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'content-type,authorization'])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response = $this->options('/api/v1/auth/login', [], ['Origin' => 'https://untrusted.example', 'Access-Control-Request-Method' => 'POST']);
        $this->assertNotContains($response->headers->get('Access-Control-Allow-Origin'), ['*', 'https://untrusted.example']);
    }

    public function test_missing_signing_secret_fails_without_partial_session_or_token(): void
    {
        $user = User::factory()->create();
        config(['jwt.secret' => '']);
        $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'password'])->assertStatus(503);
        $this->assertDatabaseCount('auth_sessions', 0);
        $this->assertDatabaseCount('auth_refresh_tokens', 0);
    }
}
