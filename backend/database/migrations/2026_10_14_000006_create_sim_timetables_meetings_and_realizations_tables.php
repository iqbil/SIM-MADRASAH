<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('semester_id');
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 150);
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['semester_id', 'code'], 'uq_timetables_semester_id_code');
            $table->index(['created_by'], 'ix_timetables_created_by');
        });

        Schema::create('timetable_versions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('timetable_id');
            $table->unsignedSmallInteger('version_number');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('published_by')->nullable();
            $table->dateTime('published_at', 6)->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['timetable_id', 'version_number'], 'uq_timetable_versions_timetable_id_version_number');
            $table->index(['timetable_id', 'status', 'effective_from'], 'ix_timetable_versions_timetable_id_status_effective_from');
            $table->index(['published_by'], 'ix_timetable_versions_published_by');
            $table->index(['created_by'], 'ix_timetable_versions_created_by');
        });

        Schema::create('schedule_entries', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('timetable_id');
            $table->char('entry_key', 36)->collation('utf8mb4_bin');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['entry_key'], 'uq_schedule_entries_entry_key');
            $table->index(['timetable_id'], 'ix_schedule_entries_timetable_id');
            $table->index(['created_by'], 'ix_schedule_entries_created_by');
        });

        Schema::create('schedule_entry_versions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('schedule_entry_id');
            $table->foreignId('timetable_version_id');
            $table->foreignId('teaching_assignment_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time_snapshot');
            $table->time('end_time_snapshot');
            $table->decimal('planned_jp', 8, 3);
            $table->unsignedSmallInteger('planned_minutes');
            $table->string('room', 100)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['schedule_entry_id', 'timetable_version_id'], 'uq_schedule_entry_versions_schedule_entry_id_timeta_fb81612ebda7');
            $table->index(['timetable_version_id', 'day_of_week', 'start_time_snapshot'], 'ix_schedule_entry_versions_timetable_version_id_day_55558a3e499e');
            $table->unique(['schedule_entry_id', 'id'], 'uq_schedule_entry_versions_schedule_entry_id_id');
            $table->index(['teaching_assignment_id'], 'ix_schedule_entry_versions_teaching_assignment_id');
        });

        Schema::create('schedule_entry_slots', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('schedule_entry_version_id');
            $table->foreignId('lesson_slot_id');
            $table->unsignedSmallInteger('position');
            $table->time('start_time_snapshot');
            $table->time('end_time_snapshot');
            $table->decimal('jp_snapshot', 8, 3);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['schedule_entry_version_id', 'lesson_slot_id'], 'uq_schedule_entry_slots_schedule_entry_version_id_lesson_slot_id');
            $table->unique(['schedule_entry_version_id', 'position'], 'uq_schedule_entry_slots_schedule_entry_version_id_position');
            $table->index(['lesson_slot_id'], 'ix_schedule_entry_slots_lesson_slot_id');
        });

        Schema::create('meetings', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('schedule_entry_id');
            $table->foreignId('schedule_entry_version_id');
            $table->date('meeting_date');
            $table->unsignedSmallInteger('occurrence_number');
            $table->foreignId('semester_id');
            $table->foreignId('class_period_id');
            $table->foreignId('subject_id');
            $table->foreignId('scheduled_employee_id');
            $table->time('start_time_snapshot');
            $table->time('end_time_snapshot');
            $table->decimal('planned_jp_snapshot', 8, 3);
            $table->unsignedSmallInteger('planned_minutes_snapshot');
            $table->foreignId('calendar_event_id')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('rescheduled_from_id')->nullable();
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['schedule_entry_id', 'meeting_date', 'occurrence_number'], 'uq_meetings_schedule_entry_id_meeting_date_occurrence_number');
            $table->index(['class_period_id', 'meeting_date'], 'ix_meetings_class_period_id_meeting_date');
            $table->index(['scheduled_employee_id', 'meeting_date'], 'ix_meetings_scheduled_employee_id_meeting_date');
            $table->index(['semester_id', 'meeting_date'], 'ix_meetings_semester_id_meeting_date');
            $table->index(['schedule_entry_version_id'], 'ix_meetings_schedule_entry_version_id');
            $table->index(['subject_id'], 'ix_meetings_subject_id');
            $table->index(['calendar_event_id'], 'ix_meetings_calendar_event_id');
            $table->index(['rescheduled_from_id'], 'ix_meetings_rescheduled_from_id');
            $table->index(['created_by'], 'ix_meetings_created_by');
        });

        Schema::create('substitute_assignments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('meeting_id');
            $table->foreignId('substitute_employee_id');
            $table->foreignId('assigned_by');
            $table->dateTime('assigned_at', 6);
            $table->text('reason');
            $table->dateTime('revoked_at', 6)->nullable();
            $table->foreignId('revoked_by')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->unsignedTinyInteger('active_marker')->nullable()->storedAs('CASE WHEN revoked_at IS NULL THEN 1 ELSE NULL END');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['meeting_id', 'active_marker'], 'uq_substitute_assignments_meeting_id_active_marker');
            $table->index(['substitute_employee_id', 'assigned_at'], 'ix_substitute_assignments_substitute_employee_id_assigned_at');
            $table->index(['assigned_by'], 'ix_substitute_assignments_assigned_by');
            $table->index(['revoked_by'], 'ix_substitute_assignments_revoked_by');
        });

        Schema::create('teaching_realizations', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('meeting_id');
            $table->foreignId('current_revision_id')->nullable();
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['meeting_id'], 'uq_teaching_realizations_meeting_id');
            $table->index(['current_revision_id'], 'ix_teaching_realizations_current_revision_id');
            $table->index(['created_by'], 'ix_teaching_realizations_created_by');
        });

        Schema::create('realization_revisions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('realization_id');
            $table->unsignedSmallInteger('revision_number');
            $table->foreignId('actual_employee_id');
            $table->foreignId('substitute_assignment_id')->nullable();
            $table->string('execution_status', 30);
            $table->string('verification_status', 30);
            $table->text('material')->nullable();
            $table->decimal('actual_jp', 8, 3);
            $table->unsignedSmallInteger('actual_minutes');
            $table->dateTime('actual_started_at', 6)->nullable();
            $table->dateTime('actual_ended_at', 6)->nullable();
            $table->text('nonexecution_reason')->nullable();
            $table->text('notes')->nullable();
            $table->text('revision_reason')->nullable();
            $table->dateTime('submitted_at', 6)->nullable();
            $table->foreignId('submitted_by')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['realization_id', 'revision_number'], 'uq_realization_revisions_realization_id_revision_number');
            $table->unique(['realization_id', 'id'], 'uq_realization_revisions_realization_id_id');
            $table->index(['actual_employee_id', 'created_at'], 'ix_realization_revisions_actual_employee_id_created_at');
            $table->index(['verification_status', 'submitted_at'], 'ix_realization_revisions_verification_status_submitted_at');
            $table->index(['substitute_assignment_id'], 'ix_realization_revisions_substitute_assignment_id');
            $table->index(['submitted_by'], 'ix_realization_revisions_submitted_by');
            $table->index(['created_by'], 'ix_realization_revisions_created_by');
        });

        Schema::create('realization_verifications', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('realization_revision_id');
            $table->string('action', 30);
            $table->foreignId('verifier_user_id');
            $table->foreignId('mandate_id');
            $table->text('reason')->nullable();
            $table->dateTime('verified_at', 6);
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['operation_key'], 'uq_realization_verifications_operation_key');
            $table->index(['realization_revision_id', 'verified_at'], 'ix_realization_verifications_realization_revision_id_verified_at');
            $table->unique(['realization_revision_id'], 'uq_realization_verifications_realization_revision_id');
            $table->index(['verifier_user_id'], 'ix_realization_verifications_verifier_user_id');
            $table->index(['mandate_id'], 'ix_realization_verifications_mandate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realization_verifications');

        Schema::dropIfExists('realization_revisions');

        Schema::dropIfExists('teaching_realizations');

        Schema::dropIfExists('substitute_assignments');

        Schema::dropIfExists('meetings');

        Schema::dropIfExists('schedule_entry_slots');

        Schema::dropIfExists('schedule_entry_versions');

        Schema::dropIfExists('schedule_entries');

        Schema::dropIfExists('timetable_versions');

        Schema::dropIfExists('timetables');
    }
};
