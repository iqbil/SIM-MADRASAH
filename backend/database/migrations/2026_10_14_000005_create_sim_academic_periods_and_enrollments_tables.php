<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 20)->collation('utf8mb4_bin');
            $table->string('name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30);
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_academic_years_code');
            $table->index(['created_by'], 'ix_academic_years_created_by');
        });

        Schema::create('semesters', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('academic_year_id');
            $table->unsignedTinyInteger('number');
            $table->string('name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30);
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['academic_year_id', 'number'], 'uq_semesters_academic_year_id_number');
            $table->unique(['academic_year_id', 'id'], 'uq_semesters_academic_year_id_id');
            $table->index(['created_by'], 'ix_semesters_created_by');
        });

        Schema::create('academic_contexts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->unsignedTinyInteger('singleton_key');
            $table->foreignId('active_academic_year_id')->nullable();
            $table->foreignId('active_semester_id')->nullable();
            $table->foreignId('changed_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['singleton_key'], 'uq_academic_contexts_singleton_key');
            $table->index(['active_academic_year_id'], 'ix_academic_contexts_active_academic_year_id');
            $table->index(['active_semester_id'], 'ix_academic_contexts_active_semester_id');
            $table->index(['changed_by'], 'ix_academic_contexts_changed_by');
        });

        Schema::create('classes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 30)->collation('utf8mb4_bin');
            $table->string('name', 100);
            $table->unsignedTinyInteger('grade');
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_classes_code');
        });

        Schema::create('class_periods', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('class_id');
            $table->foreignId('academic_year_id');
            $table->string('display_name', 100);
            $table->unsignedTinyInteger('grade_snapshot');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('status', 30);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['class_id', 'academic_year_id'], 'uq_class_periods_class_id_academic_year_id');
            $table->index(['academic_year_id', 'status'], 'ix_class_periods_academic_year_id_status');
        });

        Schema::create('student_enrollments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('class_period_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('entry_reason', 30);
            $table->string('exit_reason', 30)->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['student_id', 'effective_from'], 'uq_student_enrollments_student_id_effective_from');
            $table->index(['class_period_id', 'effective_from', 'effective_to'], 'ix_student_enrollments_class_period_id_effective_fr_3368b5cd765e');
            $table->unique(['id', 'student_id', 'class_period_id'], 'uq_student_enrollments_id_student_id_class_period_id');
            $table->unique(['id', 'student_id'], 'uq_student_enrollments_id_student_id');
            $table->index(['created_by'], 'ix_student_enrollments_created_by');
        });

        Schema::create('student_transitions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('from_enrollment_id')->nullable();
            $table->foreignId('to_enrollment_id')->nullable();
            $table->foreignId('from_status_id')->nullable();
            $table->foreignId('to_status_id');
            $table->string('type', 30);
            $table->date('effective_date');
            $table->text('reason');
            $table->string('batch_key', 100)->collation('utf8mb4_bin')->nullable();
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->foreignId('confirmed_by');
            $table->dateTime('confirmed_at', 6);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['operation_key'], 'uq_student_transitions_operation_key');
            $table->index(['student_id', 'effective_date'], 'ix_student_transitions_student_id_effective_date');
            $table->index(['from_enrollment_id'], 'ix_student_transitions_from_enrollment_id');
            $table->index(['to_enrollment_id'], 'ix_student_transitions_to_enrollment_id');
            $table->index(['from_status_id'], 'ix_student_transitions_from_status_id');
            $table->index(['to_status_id'], 'ix_student_transitions_to_status_id');
            $table->index(['confirmed_by'], 'ix_student_transitions_confirmed_by');
        });

        Schema::create('academic_calendar_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('academic_year_id');
            $table->foreignId('semester_id')->nullable();
            $table->foreignId('class_period_id')->nullable();
            $table->string('title', 200);
            $table->string('event_type', 30);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('blocks_teaching')->default(false);
            $table->boolean('blocks_employee_work')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['academic_year_id', 'start_date', 'end_date'], 'ix_academic_calendar_events_academic_year_id_start_date_end_date');
            $table->index(['semester_id'], 'ix_academic_calendar_events_semester_id');
            $table->index(['class_period_id'], 'ix_academic_calendar_events_class_period_id');
            $table->index(['created_by'], 'ix_academic_calendar_events_created_by');
        });

        Schema::create('lesson_slots', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('semester_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->unsignedSmallInteger('sequence');
            $table->string('label', 50);
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('jp_units', 8, 3);
            $table->boolean('is_break')->default(false);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['semester_id', 'day_of_week', 'sequence'], 'uq_lesson_slots_semester_id_day_of_week_sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_slots');

        Schema::dropIfExists('academic_calendar_events');

        Schema::dropIfExists('student_transitions');

        Schema::dropIfExists('student_enrollments');

        Schema::dropIfExists('class_periods');

        Schema::dropIfExists('classes');

        Schema::dropIfExists('academic_contexts');

        Schema::dropIfExists('semesters');

        Schema::dropIfExists('academic_years');
    }
};
