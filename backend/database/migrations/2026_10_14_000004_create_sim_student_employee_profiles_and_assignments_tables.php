<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('person_id');
            $table->string('internal_code', 50)->collation('utf8mb4_bin');
            $table->string('nis', 30)->nullable();
            $table->string('nisn', 20)->nullable();
            $table->date('admitted_on')->nullable();
            $table->foreignId('status_id');
            $table->dateTime('archived_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['person_id'], 'uq_students_person_id');
            $table->unique(['internal_code'], 'uq_students_internal_code');
            $table->unique(['nis'], 'uq_students_nis');
            $table->unique(['nisn'], 'uq_students_nisn');
            $table->index(['status_id', 'admitted_on'], 'ix_students_status_id_admitted_on');
        });

        Schema::create('guardians', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('person_id');
            $table->string('internal_code', 50)->collation('utf8mb4_bin');
            $table->string('occupation', 150)->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['person_id'], 'uq_guardians_person_id');
            $table->unique(['internal_code'], 'uq_guardians_internal_code');
        });

        Schema::create('student_guardians', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('guardian_id');
            $table->string('relationship', 30);
            $table->boolean('is_primary')->default(false);
            $table->string('verification_status', 30);
            $table->foreignId('verified_by')->nullable();
            $table->dateTime('verified_at', 6)->nullable();
            $table->foreignId('verification_evidence_file_id')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->dateTime('revoked_at', 6)->nullable();
            $table->foreignId('revoked_by')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['student_id', 'guardian_id', 'effective_from'], 'uq_student_guardians_student_id_guardian_id_effective_from');
            $table->index(['guardian_id', 'verification_status', 'effective_to'], 'ix_student_guardians_guardian_id_verification_statu_c462f8ee8cb9');
            $table->index(['verified_by'], 'ix_student_guardians_verified_by');
            $table->index(['verification_evidence_file_id'], 'ix_student_guardians_verification_evidence_file_id');
            $table->index(['revoked_by'], 'ix_student_guardians_revoked_by');
            $table->index(['created_by'], 'ix_student_guardians_created_by');
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('person_id');
            $table->string('employee_number', 50);
            $table->string('nip', 30)->nullable();
            $table->string('nuptk', 30)->nullable();
            $table->date('joined_on')->nullable();
            $table->date('left_on')->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['person_id'], 'uq_employees_person_id');
            $table->unique(['employee_number'], 'uq_employees_employee_number');
            $table->unique(['nip'], 'uq_employees_nip');
            $table->unique(['nuptk'], 'uq_employees_nuptk');
            $table->index(['is_active', 'joined_on'], 'ix_employees_is_active_joined_on');
        });

        Schema::create('employee_category_assignments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('category_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('reason');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['employee_id', 'effective_from'], 'uq_employee_category_assignments_employee_id_effective_from');
            $table->unique(['id', 'employee_id', 'category_id'], 'uq_employee_category_assignments_id_employee_id_category_id');
            $table->index(['category_id', 'effective_from', 'effective_to'], 'ix_employee_category_assignments_category_id_effect_60e80f64736f');
            $table->index(['created_by'], 'ix_employee_category_assignments_created_by');
        });

        Schema::create('employee_positions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('position_id');
            $table->string('appointment_number', 100)->nullable();
            $table->foreignId('appointment_file_id')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['employee_id', 'position_id', 'effective_from'], 'uq_employee_positions_employee_id_position_id_effective_from');
            $table->index(['position_id', 'effective_from', 'effective_to'], 'ix_employee_positions_position_id_effective_from_effective_to');
            $table->index(['appointment_file_id'], 'ix_employee_positions_appointment_file_id');
            $table->index(['created_by'], 'ix_employee_positions_created_by');
        });

        Schema::create('homeroom_assignments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('class_period_id');
            $table->foreignId('semester_id')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('appointment_file_id')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['class_period_id', 'effective_from'], 'uq_homeroom_assignments_class_period_id_effective_from');
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'ix_homeroom_assignments_employee_id_effective_from_effective_to');
            $table->index(['semester_id'], 'ix_homeroom_assignments_semester_id');
            $table->index(['appointment_file_id'], 'ix_homeroom_assignments_appointment_file_id');
            $table->index(['created_by'], 'ix_homeroom_assignments_created_by');
        });

        Schema::create('teaching_assignments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('subject_id');
            $table->foreignId('class_period_id');
            $table->foreignId('semester_id');
            $table->decimal('planned_weekly_jp', 8, 3)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(1);
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['employee_id', 'subject_id', 'class_period_id', 'semester_id', 'effective_from'], 'uq_teaching_assignments_employee_id_subject_id_clas_fcef794d7043');
            $table->index(['semester_id', 'class_period_id'], 'ix_teaching_assignments_semester_id_class_period_id');
            $table->index(['employee_id', 'semester_id', 'effective_from'], 'ix_teaching_assignments_employee_id_semester_id_effective_from');
            $table->index(['subject_id'], 'ix_teaching_assignments_subject_id');
            $table->index(['class_period_id'], 'ix_teaching_assignments_class_period_id');
            $table->index(['created_by'], 'ix_teaching_assignments_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');

        Schema::dropIfExists('homeroom_assignments');

        Schema::dropIfExists('employee_positions');

        Schema::dropIfExists('employee_category_assignments');

        Schema::dropIfExists('employees');

        Schema::dropIfExists('student_guardians');

        Schema::dropIfExists('guardians');

        Schema::dropIfExists('students');
    }
};
