<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_statuses', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('domain', 30);
            $table->string('code', 30)->collation('utf8mb4_bin');
            $table->string('name', 100);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['domain', 'code'], 'uq_attendance_statuses_domain_code');
        });

        Schema::create('work_patterns', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 150);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_work_patterns_code');
        });

        Schema::create('work_pattern_versions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('work_pattern_id');
            $table->unsignedSmallInteger('version_number');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('crosses_midnight')->default(false);
            $table->unsignedSmallInteger('late_tolerance_minutes');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('decision_id');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['work_pattern_id', 'version_number', 'day_of_week'], 'uq_work_pattern_versions_work_pattern_id_version_nu_d4514816cb60');
            $table->index(['decision_id'], 'ix_work_pattern_versions_decision_id');
            $table->index(['created_by'], 'ix_work_pattern_versions_created_by');
        });

        Schema::create('employee_attendance_policies', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->unsignedSmallInteger('version_number');
            $table->string('status', 30);
            $table->unsignedSmallInteger('correction_deadline_days');
            $table->boolean('requires_correction_approval')->default(false);
            $table->boolean('allow_late_correction_with_mandate')->default(false);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('decision_id');
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code', 'version_number'], 'uq_employee_attendance_policies_code_version_number');
            $table->index(['decision_id'], 'ix_employee_attendance_policies_decision_id');
            $table->index(['approved_by'], 'ix_employee_attendance_policies_approved_by');
        });

        Schema::create('employee_work_assignments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('work_pattern_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['employee_id', 'work_pattern_id', 'effective_from'], 'uq_employee_work_assignments_employee_id_work_patte_c09900dcce03');
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'ix_employee_work_assignments_employee_id_effective__8bd0b0dda51d');
            $table->unique(['id', 'employee_id'], 'uq_employee_work_assignments_id_employee_id');
            $table->index(['work_pattern_id'], 'ix_employee_work_assignments_work_pattern_id');
            $table->index(['created_by'], 'ix_employee_work_assignments_created_by');
        });

        Schema::create('employee_work_days', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('work_assignment_id');
            $table->foreignId('work_pattern_version_id');
            $table->foreignId('policy_id');
            $table->date('work_date');
            $table->unsignedSmallInteger('shift_number');
            $table->dateTime('scheduled_start_at', 6);
            $table->dateTime('scheduled_end_at', 6);
            $table->unsignedSmallInteger('tolerance_minutes_snapshot');
            $table->foreignId('calendar_event_id')->nullable();
            $table->boolean('is_work_required')->default(false);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['employee_id', 'work_date', 'shift_number'], 'uq_employee_work_days_employee_id_work_date_shift_number');
            $table->index(['work_date', 'is_work_required'], 'ix_employee_work_days_work_date_is_work_required');
            $table->index(['work_assignment_id'], 'ix_employee_work_days_work_assignment_id');
            $table->index(['work_pattern_version_id'], 'ix_employee_work_days_work_pattern_version_id');
            $table->index(['policy_id'], 'ix_employee_work_days_policy_id');
            $table->index(['calendar_event_id'], 'ix_employee_work_days_calendar_event_id');
        });

        Schema::create('employee_attendances', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('work_day_id');
            $table->foreignId('attendance_status_id');
            $table->dateTime('check_in_at', 6)->nullable();
            $table->dateTime('check_out_at', 6)->nullable();
            $table->unsignedSmallInteger('late_minutes')->nullable();
            $table->string('entry_mode', 30);
            $table->text('notes')->nullable();
            $table->foreignId('evidence_file_id')->nullable();
            $table->foreignId('recorded_by');
            $table->dateTime('recorded_at', 6);
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['work_day_id'], 'uq_employee_attendances_work_day_id');
            $table->index(['attendance_status_id'], 'ix_employee_attendances_attendance_status_id');
            $table->index(['evidence_file_id'], 'ix_employee_attendances_evidence_file_id');
            $table->index(['recorded_by'], 'ix_employee_attendances_recorded_by');
        });

        Schema::create('attendance_policies', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('semester_id');
            $table->unsignedSmallInteger('version_number');
            $table->string('status', 30);
            $table->string('mode', 30);
            $table->json('daily_summary_rule')->nullable();
            $table->time('input_deadline_time');
            $table->unsignedSmallInteger('correction_deadline_days');
            $table->boolean('requires_correction_approval')->default(false);
            $table->boolean('allow_late_correction_with_mandate')->default(false);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('decision_id');
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['semester_id', 'version_number'], 'uq_attendance_policies_semester_id_version_number');
            $table->index(['decision_id'], 'ix_attendance_policies_decision_id');
            $table->index(['approved_by'], 'ix_attendance_policies_approved_by');
        });

        Schema::create('student_daily_attendances', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('student_id');
            $table->date('attendance_date');
            $table->foreignId('class_period_id');
            $table->foreignId('enrollment_id');
            $table->foreignId('attendance_status_id');
            $table->foreignId('policy_id');
            $table->string('source_type', 30);
            $table->json('summary_source_snapshot')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by');
            $table->dateTime('recorded_at', 6);
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['student_id', 'attendance_date'], 'uq_student_daily_attendances_student_id_attendance_date');
            $table->index(['class_period_id', 'attendance_date', 'attendance_status_id'], 'ix_student_daily_attendances_class_period_id_attend_80a6803e8c41');
            $table->index(['enrollment_id'], 'ix_student_daily_attendances_enrollment_id');
            $table->index(['attendance_status_id'], 'ix_student_daily_attendances_attendance_status_id');
            $table->index(['policy_id'], 'ix_student_daily_attendances_policy_id');
            $table->index(['recorded_by'], 'ix_student_daily_attendances_recorded_by');
        });

        Schema::create('student_meeting_attendances', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('meeting_id');
            $table->foreignId('enrollment_id');
            $table->foreignId('attendance_status_id');
            $table->foreignId('policy_id');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by');
            $table->dateTime('recorded_at', 6);
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['student_id', 'meeting_id'], 'uq_student_meeting_attendances_student_id_meeting_id');
            $table->index(['meeting_id', 'attendance_status_id'], 'ix_student_meeting_attendances_meeting_id_attendance_status_id');
            $table->index(['enrollment_id'], 'ix_student_meeting_attendances_enrollment_id');
            $table->index(['attendance_status_id'], 'ix_student_meeting_attendances_attendance_status_id');
            $table->index(['policy_id'], 'ix_student_meeting_attendances_policy_id');
            $table->index(['recorded_by'], 'ix_student_meeting_attendances_recorded_by');
        });

        Schema::create('attendance_corrections', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_attendance_id')->nullable();
            $table->foreignId('student_daily_attendance_id')->nullable();
            $table->foreignId('student_meeting_attendance_id')->nullable();
            $table->json('before_values');
            $table->json('after_values');
            $table->text('reason');
            $table->foreignId('requested_by');
            $table->dateTime('requested_at', 6);
            $table->string('status', 30);
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->dateTime('applied_at', 6)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('expected_lock_version');
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['operation_key'], 'uq_attendance_corrections_operation_key');
            $table->index(['status', 'requested_at'], 'ix_attendance_corrections_status_requested_at');
            $table->index(['employee_attendance_id'], 'ix_attendance_corrections_employee_attendance_id');
            $table->index(['student_daily_attendance_id'], 'ix_attendance_corrections_student_daily_attendance_id');
            $table->index(['student_meeting_attendance_id'], 'ix_attendance_corrections_student_meeting_attendance_id');
            $table->index(['requested_by'], 'ix_attendance_corrections_requested_by');
            $table->index(['approved_by'], 'ix_attendance_corrections_approved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');

        Schema::dropIfExists('student_meeting_attendances');

        Schema::dropIfExists('student_daily_attendances');

        Schema::dropIfExists('attendance_policies');

        Schema::dropIfExists('employee_attendances');

        Schema::dropIfExists('employee_work_days');

        Schema::dropIfExists('employee_work_assignments');

        Schema::dropIfExists('employee_attendance_policies');

        Schema::dropIfExists('work_pattern_versions');

        Schema::dropIfExists('work_patterns');

        Schema::dropIfExists('attendance_statuses');
    }
};
