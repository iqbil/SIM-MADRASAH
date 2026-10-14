<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MysqlMigrationLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Run with phpunit.mysql.xml on an isolated MySQL 8.4 test database.');
        }

        $this->assertSame('testing', app()->environment());
        $this->assertMatchesRegularExpression('/_tests?$/', DB::connection()->getDatabaseName());
    }

    public function test_domain_migration_and_rollback_preserve_existing_accounts_without_duplicate_backfill(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->artisan('migrate:reset', ['--force' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('users'));
        $this->assertFalse(Schema::hasTable('persons'));
        $this->assertFalse(Schema::hasTable('payrolls'));

        $this->artisan('migrate', ['--path' => [
            'database/migrations/0001_01_01_000000_create_users_table.php',
            'database/migrations/0001_01_01_000001_create_cache_table.php',
            'database/migrations/0001_01_01_000002_create_jobs_table.php',
        ], '--force' => true])->assertSuccessful();
        $legacy = DB::table('users')->insertGetId([
            'name' => 'Akun lama',
            'email' => 'legacy@example.test',
            'password' => 'existing-password-hash',
            'created_at' => '2026-10-01 00:00:00',
            'updated_at' => '2026-10-01 00:00:00',
        ]);

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseHas('users', ['id' => $legacy, 'username' => 'user_'.$legacy, 'email' => 'legacy@example.test', 'password' => 'existing-password-hash', 'status' => 'active']);
        $person = DB::table('users')->where('id', $legacy)->value('person_id');
        $this->assertDatabaseHas('persons', ['id' => $person, 'full_name' => 'Akun lama', 'contact_email' => 'legacy@example.test']);
        $this->assertDatabaseCount('persons', 1);

        $this->artisan('migrate:rollback', ['--step' => 13, '--force' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('persons'));
        $this->assertFalse(Schema::hasColumn('users', 'person_id'));
        $this->assertDatabaseHas('users', ['id' => $legacy, 'name' => 'Akun lama', 'email' => 'legacy@example.test', 'password' => 'existing-password-hash']);

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('users', 1);
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertDatabaseCount('persons', 1);

        DB::table('users')->where('id', $legacy)->delete();
        DB::table('persons')->where('id', $person)->delete();
    }
}
