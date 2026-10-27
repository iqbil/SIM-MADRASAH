<?php

namespace Tests\Feature;

use App\Models\OAuthAccount;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Tests\MysqlAuthTestCase;

class GoogleOAuthTest extends MysqlAuthTestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $httpHistory = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.enabled' => true, 'services.google.client_id' => 'test-client.apps.googleusercontent.com', 'services.google.client_secret' => 'google-test-secret', 'services.google.redirect' => 'https://backend.example.test/api/v1/auth/google/callback']);
    }

    public function test_authorization_uses_session_state_and_pkce_and_callback_returns_jwt(): void
    {
        $user = User::factory()->create();
        $this->mockGoogle($user->email);
        $state = $this->startGoogle();
        $response = $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertOk()->assertJsonPath('data.user.id', $user->id);
        $this->assertDatabaseHas('oauth_accounts', ['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'google-subject-123']);
        $this->assertDatabaseCount('users', 1);
        $this->assertCount(2, $this->httpHistory);
        $tokenRequest = (string) $this->httpHistory[0]['request']->getBody();
        $this->assertStringContainsString('code_verifier=', $tokenRequest);
        $this->assertStringNotContainsString('google-test-secret', $response->getContent());
        $this->assertStringNotContainsString('google-access-token', $response->getContent());
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$response->json('data.access_token')])->assertOk();
        $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertUnauthorized();
        $this->assertDatabaseCount('auth_sessions', 1);
    }

    public function test_seeded_owner_can_login_with_google_without_a_password(): void
    {
        $this->freezeTime();
        $this->setInitialAdminEnvironment(['email' => 'owner@example.test', 'password' => null]);
        $this->seed(SuperAdminSeeder::class);
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $this->mockGoogle($owner->email);
        $state = $this->startGoogle();
        $response = $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')
            ->assertOk()->assertJsonPath('data.user.id', $owner->id)
            ->assertJsonPath('data.expires_in', 900)
            ->assertJsonPath('data.refresh_expires_in', 7200);
        $this->getJson('/api/v1/auth/me', ['Authorization' => 'Bearer '.$response->json('data.access_token')])
            ->assertOk()->assertJsonPath('data.id', $owner->id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_wrong_missing_and_expired_state_are_rejected_before_google_requests(): void
    {
        $this->mockGoogle('unknown@example.test');
        $this->startGoogle();
        $this->getJson('/api/v1/auth/google/callback?state=wrong-state&code=google-code')->assertUnauthorized();
        $this->assertCount(0, $this->httpHistory);
        $this->getJson('/api/v1/auth/google/callback?state=state&code=google-code')->assertUnauthorized();
        $state = $this->startGoogle();
        $this->withSession(['google_auth_started_at' => now('UTC')->subMinutes(11)->timestamp])->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertUnauthorized();
        $this->assertDatabaseCount('auth_sessions', 0);
    }

    public function test_google_does_not_register_unknown_accounts(): void
    {
        $this->mockGoogle('unknown@example.test');
        $state = $this->startGoogle();
        $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertUnauthorized();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('oauth_accounts', 0);
    }

    public function test_unverified_google_email_cannot_link(): void
    {
        $user = User::factory()->create();
        $this->mockGoogle($user->email, false);
        $state = $this->startGoogle();
        $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertUnauthorized();
        $this->assertDatabaseCount('oauth_accounts', 0);
    }

    public function test_unverified_local_account_is_not_auto_linked(): void
    {
        $user = User::factory()->unverified()->create();
        $this->mockGoogle($user->email);
        $state = $this->startGoogle();
        $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertUnauthorized();
        $this->assertDatabaseCount('oauth_accounts', 0);
    }

    public function test_disabled_users_cannot_bypass_with_google(): void
    {
        $user = User::factory()->disabled()->create();
        $this->mockGoogle($user->email);
        $state = $this->startGoogle();
        $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertUnauthorized();
        $this->assertDatabaseCount('auth_sessions', 0);
    }

    public function test_google_cannot_bypass_a_required_password_change(): void
    {
        $user = User::factory()->create(['must_change_password' => true, 'temporary_password_expires_at' => now('UTC')->addDay()]);
        $this->mockGoogle($user->email);
        $state = $this->startGoogle();
        $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertUnauthorized();
        $this->assertDatabaseCount('oauth_accounts', 0);
        $this->assertDatabaseCount('auth_sessions', 0);
        $this->assertNull($user->fresh()->temporary_password_used_at);
    }

    public function test_google_service_errors_do_not_leak_credentials_or_create_sessions(): void
    {
        $user = User::factory()->create();
        $this->mockGoogle($user->email, tokenStatus: 400);
        $state = $this->startGoogle();
        $response = $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertUnauthorized();
        $this->assertStringNotContainsString('google-access-token', $response->getContent());
        $this->assertStringNotContainsString('google-test-secret', $response->getContent());
        $this->assertNull(session('state'));
        $this->assertNull(session('code_verifier'));
        $this->assertDatabaseCount('oauth_accounts', 0);
        $this->assertDatabaseCount('auth_sessions', 0);
    }

    public function test_google_cannot_replace_an_existing_provider_identity(): void
    {
        $user = User::factory()->create();
        OAuthAccount::factory()->create(['user_id' => $user->id, 'provider_user_id' => 'different-subject']);
        $this->mockGoogle($user->email);
        $state = $this->startGoogle();
        $this->getJson('/api/v1/auth/google/callback?state='.$state.'&code=google-code')->assertUnauthorized();
        $this->assertDatabaseHas('oauth_accounts', ['user_id' => $user->id, 'provider_user_id' => 'different-subject']);
        $this->assertDatabaseCount('auth_sessions', 0);
    }

    public function test_denied_consent_clears_state_without_issuing_tokens(): void
    {
        $this->mockGoogle('unknown@example.test');
        $this->startGoogle();
        $this->getJson('/api/v1/auth/google/callback?error=access_denied')->assertUnauthorized();
        $this->assertNull(session('state'));
        $this->assertNull(session('code_verifier'));
        $this->assertCount(0, $this->httpHistory);
    }

    private function startGoogle(): string
    {
        $response = $this->get('/api/v1/auth/google/redirect')->assertRedirect();
        $query = [];
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertSame(session('state'), $query['state']);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', session('code_verifier'), true)), '+/', '-_'), '=');
        $this->assertSame($challenge, $query['code_challenge']);

        return $query['state'];
    }

    private function mockGoogle(string $email, bool $verified = true, int $tokenStatus = 200): void
    {
        $mock = new MockHandler([
            new Response($tokenStatus, ['Content-Type' => 'application/json'], json_encode(['access_token' => 'google-access-token', 'expires_in' => 3600, 'scope' => 'openid profile email'])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['sub' => 'google-subject-123', 'email' => $email, 'email_verified' => $verified, 'name' => 'Nama Google'])),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->httpHistory));
        $client = new Client(['handler' => $stack]);
        Socialite::shouldReceive('buildProvider')->andReturnUsing(function (string $provider, array $config) use ($client): GoogleProvider {
            return (new GoogleProvider(app(Request::class), $config['client_id'], $config['client_secret'], $config['redirect']))->setHttpClient($client);
        });
    }
}
