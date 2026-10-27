<?php

namespace Tests\Feature;

use App\Models\BusinessDecision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MysqlGeofenceMigrationTest extends TestCase
{
    public function test_geofence_migration_preserves_legacy_rows_and_normalizes_adjacent_dates(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Use the isolated MySQL test database.');
        }
        $this->assertSame('testing', app()->environment());
        $this->assertMatchesRegularExpression('/_tests?$/', DB::connection()->getDatabaseName());
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $migration = require database_path('migrations/2026_10_27_090758_add_geofence_to_employee_attendance_policies.php');
        $decision = BusinessDecision::factory()->create();
        $code = 'GEO-LEGACY-'.Str::random(12);
        try {
            $migration->down();
            $base = ['code' => $code, 'status' => 'baseline', 'correction_deadline_days' => 7,
                'decision_id' => $decision->id, 'created_at' => now('UTC'), 'updated_at' => now('UTC')];
            $first = DB::table('employee_attendance_policies')->insertGetId([...$base, 'version_number' => 1, 'effective_from' => '2026-10-01', 'effective_to' => '2026-10-19']);
            $second = DB::table('employee_attendance_policies')->insertGetId([...$base, 'version_number' => 2, 'effective_from' => '2026-10-20']);
            $migration->up();
            $this->assertDatabaseHas('employee_attendance_policies', ['id' => $first, 'geofence_enabled' => 0, 'geofence_latitude' => null, 'effective_to' => '2026-10-20']);
            $this->assertDatabaseHas('employee_attendance_policies', ['id' => $second, 'geofence_enabled' => 0, 'geofence_radius_meters' => null, 'effective_from' => '2026-10-20']);
            $this->assertSame(2, DB::table('employee_attendance_policies')->where('code', $code)->count());
            $this->assertTrue(Schema::hasTable('employee_attendances'));
            $this->assertTrue(Schema::hasTable('employee_attendance_captures'));
            $migration->down();
            $this->assertFalse(Schema::hasColumn('employee_attendance_policies', 'geofence_enabled'));
            $this->assertDatabaseHas('employee_attendance_policies', ['id' => $first, 'effective_to' => '2026-10-20']);
            $migration->up();
            $this->assertDatabaseHas('employee_attendance_policies', ['id' => $first, 'effective_to' => '2026-10-20']);
        } finally {
            if (! Schema::hasColumn('employee_attendance_policies', 'geofence_enabled')) {
                $migration->up();
            }
            DB::table('employee_attendance_policies')->where('code', $code)->delete();
            $decision->delete();
        }
    }
}
