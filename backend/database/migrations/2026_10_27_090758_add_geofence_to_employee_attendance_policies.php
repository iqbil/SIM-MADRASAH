<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_attendance_policies', function (Blueprint $table): void {
            $table->boolean('geofence_enabled')->default(false);
            $table->string('geofence_name', 100)->nullable();
            $table->decimal('geofence_latitude', 10, 7)->nullable();
            $table->decimal('geofence_longitude', 10, 7)->nullable();
            $table->unsignedInteger('geofence_radius_meters')->nullable();
        });
        // Pertahankan hari berlaku policy lama saat menormalkan batas akhir eksklusif.
        DB::statement('UPDATE employee_attendance_policies AS older
            JOIN employee_attendance_policies AS newer ON newer.code = older.code
                AND newer.version_number = older.version_number + 1
                AND newer.effective_from = DATE_ADD(older.effective_to, INTERVAL 1 DAY)
            SET older.effective_to = newer.effective_from');
        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_eap_geofence` CHECK (
            `geofence_enabled` IN (0, 1) AND (
                (`geofence_enabled` = 0 AND `geofence_name` IS NULL AND `geofence_latitude` IS NULL AND `geofence_longitude` IS NULL AND `geofence_radius_meters` IS NULL)
                OR (`geofence_enabled` = 1 AND `requires_gps` = 1 AND `requires_photo` = 1
                    AND `geofence_name` IS NOT NULL AND CHAR_LENGTH(TRIM(`geofence_name`)) > 0
                    AND `geofence_latitude` IS NOT NULL AND `geofence_latitude` BETWEEN -90 AND 90
                    AND `geofence_longitude` IS NOT NULL AND `geofence_longitude` BETWEEN -180 AND 180
                    AND `geofence_radius_meters` IS NOT NULL AND `geofence_radius_meters` BETWEEN 25 AND 5000)
            ))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_eap_geofence`');
        Schema::table('employee_attendance_policies', function (Blueprint $table): void {
            $table->dropColumn(['geofence_enabled', 'geofence_name', 'geofence_latitude', 'geofence_longitude', 'geofence_radius_meters']);
        });
    }
};
