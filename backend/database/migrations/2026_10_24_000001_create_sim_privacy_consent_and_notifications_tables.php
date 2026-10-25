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
        // FR-16-01: register dasar pemrosesan data pribadi (UU PDP No. 27/2022).
        Schema::create('data_processing_registers', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('data_domain', 30);
            $table->text('purpose');
            $table->string('legal_basis', 30);
            $table->json('data_categories');
            $table->foreignId('retention_policy_id')->nullable();
            $table->string('responsible_role', 50)->nullable()->collation('utf8mb4_bin');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique('code', 'uq_dpr_code');
            $table->index(['data_domain', 'is_active'], 'ix_dpr_domain_active');
            $table->foreign('retention_policy_id', 'fk_dpr_retention')->references('id')->on('retention_policies')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('created_by', 'fk_dpr_created_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement("ALTER TABLE `data_processing_registers` ADD CONSTRAINT `ck_dpr_domain` CHECK (`data_domain` IN ('student', 'guardian', 'employee', 'bk', 'honor', 'attendance_capture', 'publication', 'audit'))");
        DB::statement("ALTER TABLE `data_processing_registers` ADD CONSTRAINT `ck_dpr_legal_basis` CHECK (`legal_basis` IN ('legal_obligation', 'consent', 'legitimate_interest', 'vital_interest', 'public_interest'))");

        // FR-16-04 / DEC-21: persetujuan publikasi foto/identitas siswa per siswa, berversi, dapat ditarik.
        Schema::create('student_publication_consents', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('student_id');
            $table->string('consent_scope', 30);
            $table->foreignId('given_by_guardian_id')->nullable();
            $table->string('consent_document_version', 50);
            $table->dateTime('given_at', 6);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->dateTime('withdrawn_at', 6)->nullable();
            $table->foreignId('withdrawn_by')->nullable();
            $table->text('withdraw_reason')->nullable();
            $table->foreignId('created_by');
            $table->unsignedTinyInteger('active_marker')->nullable()->storedAs('CASE WHEN withdrawn_at IS NULL THEN 1 ELSE NULL END');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['student_id', 'consent_scope', 'effective_from'], 'uq_spc_student_scope_from');
            $table->unique(['student_id', 'consent_scope', 'active_marker'], 'uq_spc_student_scope_active');
            $table->index(['given_by_guardian_id'], 'ix_spc_given_by_guardian');
            $table->foreign('student_id', 'fk_spc_student')->references('id')->on('students')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('given_by_guardian_id', 'fk_spc_guardian')->references('id')->on('student_guardians')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('withdrawn_by', 'fk_spc_withdrawn_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('created_by', 'fk_spc_created_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement("ALTER TABLE `student_publication_consents` ADD CONSTRAINT `ck_spc_scope` CHECK (`consent_scope` IN ('photo', 'name', 'achievement'))");
        DB::statement('ALTER TABLE `student_publication_consents` ADD CONSTRAINT `ck_spc_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');
        DB::statement('ALTER TABLE `student_publication_consents` ADD CONSTRAINT `ck_spc_withdraw` CHECK (`withdrawn_at` IS NULL OR (`withdrawn_at` >= `given_at` AND `withdrawn_by` IS NOT NULL))');

        // FR-13-03: penanda siswa yang tampil pada satu revisi konten; dasar pemeriksaan persetujuan sebelum terbit.
        Schema::create('content_student_links', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('content_revision_id');
            $table->foreignId('student_id');
            $table->string('link_type', 30);
            $table->foreignId('consent_id')->nullable();
            $table->dateTime('checked_at', 6)->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['content_revision_id', 'student_id'], 'uq_csl_revision_student');
            $table->index(['student_id'], 'ix_csl_student');
            $table->foreign('content_revision_id', 'fk_csl_revision')->references('id')->on('content_revisions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('student_id', 'fk_csl_student')->references('id')->on('students')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('consent_id', 'fk_csl_consent')->references('id')->on('student_publication_consents')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('created_by', 'fk_csl_created_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement("ALTER TABLE `content_student_links` ADD CONSTRAINT `ck_csl_link_type` CHECK (`link_type` IN ('photo', 'name', 'achievement'))");

        // FR-16-03 / BR-27: permintaan hak subjek data.
        Schema::create('data_subject_requests', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('request_number', 50)->collation('utf8mb4_bin');
            $table->foreignId('subject_person_id');
            $table->foreignId('on_behalf_of_guardian_id')->nullable();
            $table->string('request_type', 30);
            $table->text('detail');
            $table->boolean('identity_verified')->default(false);
            $table->foreignId('verification_evidence_file_id')->nullable();
            $table->string('status', 30);
            $table->dateTime('due_at', 6)->nullable();
            $table->foreignId('handled_by')->nullable();
            $table->dateTime('handled_at', 6)->nullable();
            $table->text('result_summary')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique('request_number', 'uq_dsr_number');
            $table->index(['status', 'created_at'], 'ix_dsr_status_created');
            $table->index(['subject_person_id'], 'ix_dsr_subject');
            $table->foreign('subject_person_id', 'fk_dsr_subject_person')->references('id')->on('persons')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('on_behalf_of_guardian_id', 'fk_dsr_guardian')->references('id')->on('student_guardians')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('verification_evidence_file_id', 'fk_dsr_evidence')->references('id')->on('files')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('handled_by', 'fk_dsr_handled_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('created_by', 'fk_dsr_created_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement("ALTER TABLE `data_subject_requests` ADD CONSTRAINT `ck_dsr_type` CHECK (`request_type` IN ('access', 'correction', 'limitation', 'objection', 'deletion'))");
        DB::statement("ALTER TABLE `data_subject_requests` ADD CONSTRAINT `ck_dsr_status` CHECK (`status` IN ('received', 'in_progress', 'fulfilled', 'rejected', 'closed'))");
        DB::statement("ALTER TABLE `data_subject_requests` ADD CONSTRAINT `ck_dsr_rejection` CHECK (`status` <> 'rejected' OR `rejection_reason` IS NOT NULL)");

        // FR-17 / DEC-20: notifikasi in-app dengan status terbaca; kanal eksternal nonaktif.
        Schema::create('notifications', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('recipient_user_id');
            $table->string('category', 30);
            $table->string('title', 255);
            $table->text('body');
            $table->json('payload')->nullable();
            $table->string('operation_key', 100)->nullable()->collation('utf8mb4_bin');
            $table->dateTime('read_at', 6)->nullable();
            $table->foreignId('created_by')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique('operation_key', 'uq_ntf_operation');
            $table->index(['recipient_user_id', 'read_at', 'created_at'], 'ix_ntf_recipient_read_created');
            $table->foreign('recipient_user_id', 'fk_ntf_recipient')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
            $table->foreign('created_by', 'fk_ntf_created_by')->references('id')->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement("ALTER TABLE `notifications` ADD CONSTRAINT `ck_ntf_category` CHECK (`category` IN ('announcement', 'realization_verification', 'payroll_approval', 'attendance_correction', 'system'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('content_student_links');
        Schema::dropIfExists('student_publication_consents');
        Schema::dropIfExists('data_processing_registers');
    }
};
