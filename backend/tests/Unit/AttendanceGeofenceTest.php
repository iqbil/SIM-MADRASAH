<?php

namespace Tests\Unit;

use App\Models\EmployeeAttendancePolicy;
use App\Services\AttendanceGeofence;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttendanceGeofenceTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_distance_handles_known_locations_and_antimeridian(): void
    {
        $this->assertSame(0.0, AttendanceGeofence::distanceMeters(0, 0, 0, 0));
        $this->assertEqualsWithDelta(111195.08, AttendanceGeofence::distanceMeters(0, 0, 1, 0), 0.02);
        $this->assertEqualsWithDelta(222.39, AttendanceGeofence::distanceMeters(0, 179.999, 0, -179.999), 0.02);
        $this->assertTrue(is_finite(AttendanceGeofence::distanceMeters(0, 0, 0, 180)));
    }

    public function test_accepts_only_positions_whose_accuracy_circle_is_inside(): void
    {
        $policy = EmployeeAttendancePolicy::factory()->make(['decision_id' => 1,
            'geofence_enabled' => true, 'geofence_name' => 'Madrasah uji',
            'geofence_latitude' => 0, 'geofence_longitude' => 0, 'geofence_radius_meters' => 100]);
        $this->assertSame(0.0, AttendanceGeofence::assertWithin($policy, 0, 0, 100));
        $this->assertEqualsWithDelta(44.478, AttendanceGeofence::assertWithin($policy, 0.0004, 0, 10), 0.01);
        foreach ([[0.002, 5, 'luar radius'], [0.00089, 5, 'belum pasti'], [0, 120, 'belum pasti']] as [$latitude, $accuracy, $message]) {
            try {
                AttendanceGeofence::assertWithin($policy, $latitude, 0, $accuracy);
                $this->fail('Posisi di luar/meragukan harus ditolak.');
            } catch (ValidationException $error) {
                $this->assertStringContainsString($message, $error->errors()['geofence'][0]);
            }
        }
    }

    public function test_old_policy_without_geofence_stays_usable(): void
    {
        $policy = EmployeeAttendancePolicy::factory()->make(['decision_id' => 1]);
        $this->assertFalse($policy->geofence_enabled);
        $this->assertNull(AttendanceGeofence::assertWithin($policy, -6.7, 108.5, 10));
    }
}
