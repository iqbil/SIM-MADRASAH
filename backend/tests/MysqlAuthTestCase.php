<?php

namespace Tests;

use App\Models\User;
use Illuminate\Support\Facades\DB;

abstract class MysqlAuthTestCase extends TestCase
{
    /** @var array<string, array{env: mixed, server: mixed, process: string|false}> */
    private array $initialAdminEnvironment = [];

    /** @param array<string, string|false|null> $values */
    protected function setInitialAdminEnvironment(array $values): void
    {
        foreach ($values as $key => $value) {
            $name = 'INITIAL_ADMIN_'.strtoupper($key);
            $this->initialAdminEnvironment[$name] ??= [
                'env' => $_ENV[$name] ?? null,
                'server' => $_SERVER[$name] ?? null,
                'process' => getenv($name),
            ];
            if ($value === false) {
                unset($_ENV[$name], $_SERVER[$name]);
                putenv($name);
            } else {
                $_ENV[$name] = $_SERVER[$name] = $value ?? '(null)';
                putenv($name.'='.($value ?? '(null)'));
            }
        }
    }

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
        foreach ($this->initialAdminEnvironment as $name => $values) {
            if ($values['env'] === null) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $values['env'];
            }
            if ($values['server'] === null) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $values['server'];
            }
            putenv($values['process'] === false ? $name : $name.'='.$values['process']);
        }
        $this->initialAdminEnvironment = [];

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
