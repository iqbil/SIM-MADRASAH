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
            $table->boolean('requires_photo')->default(false);
            $table->boolean('requires_gps')->default(false);
            $table->unsignedInteger('photo_max_size_bytes')->default(307200);
            $table->unsignedSmallInteger('photo_max_dimension')->default(1280);
            $table->unsignedTinyInteger('photo_quality')->default(80);
            $table->decimal('gps_max_accuracy_meters', 8, 2)->default(100);
            $table->unsignedSmallInteger('gps_max_age_seconds')->default(60);
        });

        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_eap_capture_flags` CHECK (`requires_photo` IN (0, 1) AND `requires_gps` IN (0, 1) AND `requires_photo` = `requires_gps`)');
        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_eap_photo_limits` CHECK (`photo_max_size_bytes` > 0 AND `photo_max_size_bytes` <= 10485760 AND `photo_max_dimension` BETWEEN 1 AND 8192 AND `photo_quality` BETWEEN 1 AND 100)');
        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_eap_gps_limits` CHECK (`gps_max_accuracy_meters` > 0 AND `gps_max_age_seconds` > 0)');

        Schema::create('employee_attendance_captures', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_attendance_id');
            $table->string('event_type', 30);
            $table->foreignId('photo_file_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 10, 2);
            $table->dateTime('photo_taken_at', 6);
            $table->dateTime('location_captured_at', 6);
            $table->dateTime('recorded_at', 6);
            $table->unsignedBigInteger('original_size_bytes');
            $table->unsignedSmallInteger('photo_width');
            $table->unsignedSmallInteger('photo_height');
            $table->unsignedTinyInteger('compression_quality');
            $table->json('policy_snapshot');
            $table->foreignId('recorded_by');
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['employee_attendance_id', 'event_type'], 'uq_eac_attendance_event');
            $table->unique('photo_file_id', 'uq_eac_photo_file');
            $table->unique('operation_key', 'uq_eac_operation');
            $table->index(['recorded_by', 'recorded_at'], 'ix_eac_actor_time');
            $table->foreign('employee_attendance_id', 'fk_eac_attendance')->references('id')->on('employee_attendances')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('photo_file_id', 'fk_eac_photo')->references('id')->on('files')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('recorded_by', 'fk_eac_actor')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement("ALTER TABLE `employee_attendance_captures` ADD CONSTRAINT `ck_eac_event` CHECK (`event_type` IN ('check_in', 'check_out'))");
        DB::statement('ALTER TABLE `employee_attendance_captures` ADD CONSTRAINT `ck_eac_coordinates` CHECK (`latitude` BETWEEN -90 AND 90 AND `longitude` BETWEEN -180 AND 180 AND `accuracy_meters` >= 0)');
        DB::statement('ALTER TABLE `employee_attendance_captures` ADD CONSTRAINT `ck_eac_photo_metadata` CHECK (`original_size_bytes` > 0 AND `photo_width` BETWEEN 1 AND 8192 AND `photo_height` BETWEEN 1 AND 8192 AND `compression_quality` BETWEEN 1 AND 100)');
        DB::statement('ALTER TABLE `employee_attendance_captures` ADD CONSTRAINT `ck_eac_capture_times` CHECK (`photo_taken_at` <= `recorded_at` AND `location_captured_at` <= `recorded_at`)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_captures');

        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_eap_gps_limits`');
        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_eap_photo_limits`');
        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_eap_capture_flags`');

        Schema::table('employee_attendance_policies', function (Blueprint $table): void {
            $table->dropColumn(['requires_photo', 'requires_gps', 'photo_max_size_bytes', 'photo_max_dimension', 'photo_quality', 'gps_max_accuracy_meters', 'gps_max_age_seconds']);
        });
    }
};
