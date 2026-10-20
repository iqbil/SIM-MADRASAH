<?php

namespace Tests;

use App\Models\User;
use Illuminate\Support\Facades\DB;

abstract class MysqlAuthTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Run authentication integration tests with phpunit.mysql.xml on an isolated MySQL database.');
        }
        $this->assertSame('testing', app()->environment());
        $this->assertMatchesRegularExpression('/_tests?$/', DB::connection()->getDatabaseName());
        config(['jwt.secret' => str_repeat('test-key-', 8), 'jwt.issuer' => 'https://backend.example.test', 'jwt.audience' => 'sim-madrasah-api', 'jwt.access_ttl' => 15, 'jwt.refresh_ttl' => 120, 'services.google.enabled' => false, 'app.debug' => false]);
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->app !== null && DB::getDriverName() === 'mysql' && DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }

    /** @return array<string, mixed> */
    protected function loginUser(User $user): array
    {
        return $this->postJson('/api/v1/auth/login', ['login' => $user->username, 'password' => 'password'])->assertOk()->json('data');
    }
}
