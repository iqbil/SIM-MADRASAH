<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GuardedMigrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class MysqlRollbackGuardTest extends TestCase
{
    public function test_identity_rollback_is_stopped_before_any_down_but_geofence_only_rollback_is_allowed(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Use the isolated MySQL test database.');
        }
        $this->assertSame('testing', app()->environment());
        $this->assertMatchesRegularExpression('/_tests?$/', DB::connection()->getDatabaseName());
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $user = User::factory()->withoutEmail()->create();
        $person = $user->person;
        $batches = DB::table('migrations')->pluck('batch', 'migration')->all();
        $before = DB::table('migrations')->count();
        $migrator = app('migrator');
        $this->assertInstanceOf(GuardedMigrator::class, $migrator);
        try {
            DB::table('migrations')->update(['batch' => 77]);
            foreach (['rollback', 'reset'] as $method) {
                try {
                    $migrator->{$method}([database_path('migrations')]);
                    $this->fail('A full identity downgrade must be rejected before destructive migrations start.');
                } catch (RuntimeException $error) {
                    $this->assertStringContainsString('Rollback dihentikan sebelum tabel dihapus', $error->getMessage());
                }
                $this->assertTrue(Schema::hasTable('employees'));
                $this->assertTrue(Schema::hasTable('auth_sessions'));
                $this->assertSame($before, DB::table('migrations')->count());
                $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => null]);
            }

            $this->artisan('migrate:rollback', [
                '--path' => ['database/migrations/2026_10_27_090758_add_geofence_to_employee_attendance_policies.php'],
                '--force' => true,
            ])->assertSuccessful();
            $this->assertFalse(Schema::hasColumn('employee_attendance_policies', 'geofence_enabled'));
            $this->assertTrue(Schema::hasTable('employees'));
            $this->assertTrue(Schema::hasTable('auth_sessions'));
            $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => null]);
            $this->artisan('migrate', ['--force' => true])->assertSuccessful();
            $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true])->assertSuccessful();
            $this->assertFalse(Schema::hasColumn('employee_attendance_policies', 'geofence_enabled'));
        } finally {
            $this->artisan('migrate', ['--force' => true])->assertSuccessful();
            foreach ($batches as $name => $batch) {
                DB::table('migrations')->where('migration', $name)->update(['batch' => $batch]);
            }
            $user->delete();
            $person->delete();
        }
    }
}
