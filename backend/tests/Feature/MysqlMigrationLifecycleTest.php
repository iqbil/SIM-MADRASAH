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

        $this->artisan('migrate:rollback', ['--step' => 1, '--force' => true])->assertSuccessful();
        $this->assertFalse(Schema::hasTable('employee_attendance_captures'));
        $this->assertFalse(Schema::hasColumn('employee_attendance_policies', 'requires_photo'));
        $this->assertTrue(Schema::hasTable('employee_attendances'));
        $this->assertDatabaseHas('users', ['id' => $legacy, 'person_id' => $person]);
        [$legacyPolicy, $legacyAttendance] = $this->legacyAttendance($legacy, $person);
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->assertTrue(Schema::hasTable('employee_attendance_captures'));
        $this->assertDatabaseHas('employee_attendance_policies', ['id' => $legacyPolicy, 'version_number' => 1, 'requires_photo' => 0, 'requires_gps' => 0]);
        $this->assertDatabaseHas('employee_attendances', ['id' => $legacyAttendance, 'check_in_at' => '2026-10-19 00:00:00', 'recorded_by' => $legacy]);
        $this->assertDatabaseMissing('employee_attendance_captures', ['employee_attendance_id' => $legacyAttendance]);

        $this->artisan('migrate:rollback', ['--step' => 14, '--force' => true])->assertSuccessful();
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

    private function legacyAttendance(int $user, int $person): array
    {
        $insert = static fn (string $table, array $values): int => DB::table($table)->insertGetId($values + ['created_at' => '2026-10-19 00:00:00', 'updated_at' => '2026-10-19 00:00:00']);
        $employee = $insert('employees', ['person_id' => $person, 'employee_number' => 'LEGACY-EMP']);
        $decision = $insert('business_decisions', ['code' => 'DEC-16', 'title' => 'Absensi sebelum addendum', 'status' => 'baseline', 'decision_text' => 'Synthetic legacy baseline', 'source_reference' => 'Test', 'recorded_at' => '2026-10-14 00:00:00', 'version' => 1, 'effective_from' => '2026-07-01']);
        $pattern = $insert('work_patterns', ['code' => 'LEGACY', 'name' => 'Pola kerja lama']);
        $version = $insert('work_pattern_versions', ['work_pattern_id' => $pattern, 'version_number' => 1, 'day_of_week' => 1, 'start_time' => '07:00:00', 'end_time' => '14:00:00', 'late_tolerance_minutes' => 10, 'effective_from' => '2026-07-01', 'decision_id' => $decision, 'created_by' => $user]);
        $policy = $insert('employee_attendance_policies', ['code' => 'LEGACY', 'version_number' => 1, 'status' => 'baseline', 'correction_deadline_days' => 7, 'requires_correction_approval' => 1, 'allow_late_correction_with_mandate' => 1, 'effective_from' => '2026-07-01', 'decision_id' => $decision]);
        $assignment = $insert('employee_work_assignments', ['employee_id' => $employee, 'work_pattern_id' => $pattern, 'effective_from' => '2026-07-01', 'created_by' => $user]);
        $day = $insert('employee_work_days', ['employee_id' => $employee, 'work_assignment_id' => $assignment, 'work_pattern_version_id' => $version, 'policy_id' => $policy, 'work_date' => '2026-10-19', 'shift_number' => 1, 'scheduled_start_at' => '2026-10-19 00:00:00', 'scheduled_end_at' => '2026-10-19 07:00:00', 'tolerance_minutes_snapshot' => 10, 'is_work_required' => 1]);
        $status = $insert('attendance_statuses', ['domain' => 'employee', 'code' => 'hadir', 'name' => 'Hadir']);
        $attendance = $insert('employee_attendances', ['work_day_id' => $day, 'attendance_status_id' => $status, 'check_in_at' => '2026-10-19 00:00:00', 'entry_mode' => 'self', 'recorded_by' => $user, 'recorded_at' => '2026-10-19 00:00:00']);

        return [$policy, $attendance];
    }
}
