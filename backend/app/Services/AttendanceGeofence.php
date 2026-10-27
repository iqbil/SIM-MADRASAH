<?php

namespace App\Services;

use App\Models\EmployeeAttendancePolicy;
use Illuminate\Validation\ValidationException;

class AttendanceGeofence
{
    /** Haversine dengan radius bumi rata-rata IUGG, tanpa pembulatan sebelum keputusan. */
    public static function distanceMeters(float $latitude, float $longitude, float $centerLatitude, float $centerLongitude): float
    {
        $deltaLatitude = deg2rad($latitude - $centerLatitude);
        $deltaLongitude = deg2rad($longitude - $centerLongitude);
        $haversine = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad($centerLatitude)) * sin($deltaLongitude / 2) ** 2;

        return 6371008.8 * 2 * asin(sqrt(min(1.0, max(0.0, $haversine))));
    }

    /** Hanya posisi beserta lingkaran ketelitian GPS yang berada dalam radius diterima. */
    public static function assertWithin(EmployeeAttendancePolicy $policy, float $latitude, float $longitude, float $accuracy): ?float
    {
        if (! $policy->geofence_enabled) {
            return null;
        }
        abort_if($policy->geofence_latitude === null || $policy->geofence_longitude === null || $policy->geofence_radius_meters === null, 422,
            'Titik absensi belum lengkap. Hubungi petugas absensi.');
        $distance = self::distanceMeters($latitude, $longitude, (float) $policy->geofence_latitude, (float) $policy->geofence_longitude);
        $radius = $policy->geofence_radius_meters;
        if ($distance + $accuracy <= $radius) {
            return $distance;
        }
        $message = $distance - $accuracy > $radius
            ? 'Anda berada di luar radius absensi '.$policy->geofence_name.' (jarak sekitar '.(int) round($distance).' m; radius '.$radius.' m). Mendekat ke lokasi lalu muat ulang GPS.'
            : 'Posisi GPS belum pasti di dalam radius absensi. Mendekat ke pusat lokasi atau keluar ke area terbuka lalu muat ulang GPS.';
        throw ValidationException::withMessages(['geofence' => $message]);
    }
}
