<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class MigrationDriverTest extends TestCase
{
    public function test_domain_migration_rejects_sqlite_before_creating_domain_tables(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $migration = require database_path('migrations/2026_10_14_000001_create_sim_identity_reference_tables.php');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('require MySQL 8.4');
        $migration->up();
    }
}
