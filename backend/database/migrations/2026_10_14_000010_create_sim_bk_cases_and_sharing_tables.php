<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bk_categories', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->foreignId('retention_policy_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_bk_categories_code');
            $table->index(['retention_policy_id'], 'ix_bk_categories_retention_policy_id');
        });

        Schema::create('bk_category_grants', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('category_id');
            $table->boolean('can_read_internal')->default(false);
            $table->boolean('can_write')->default(false);
            $table->boolean('can_share')->default(false);
            $table->boolean('can_export')->default(false);
            $table->boolean('can_download')->default(false);
            $table->dateTime('effective_from', 6);
            $table->dateTime('effective_to', 6)->nullable();
            $table->foreignId('granted_by');
            $table->dateTime('revoked_at', 6)->nullable();
            $table->text('reason');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['user_id', 'category_id', 'effective_from'], 'uq_bk_category_grants_user_id_category_id_effective_from');
            $table->index(['user_id', 'category_id', 'effective_to'], 'ix_bk_category_grants_user_id_category_id_effective_to');
            $table->index(['category_id'], 'ix_bk_category_grants_category_id');
            $table->index(['granted_by'], 'ix_bk_category_grants_granted_by');
        });

        Schema::create('bk_cases', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('category_id');
            $table->foreignId('assigned_employee_id');
            $table->string('case_number', 50);
            $table->date('opened_on');
            $table->date('closed_on')->nullable();
            $table->string('status', 30);
            $table->string('title', 200);
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['case_number'], 'uq_bk_cases_case_number');
            $table->index(['student_id', 'opened_on'], 'ix_bk_cases_student_id_opened_on');
            $table->index(['category_id', 'status', 'opened_on'], 'ix_bk_cases_category_id_status_opened_on');
            $table->index(['assigned_employee_id'], 'ix_bk_cases_assigned_employee_id');
            $table->index(['created_by'], 'ix_bk_cases_created_by');
        });

        Schema::create('bk_notes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('case_id');
            $table->string('note_type', 30);
            $table->dateTime('occurred_at', 6);
            $table->longText('internal_content');
            $table->string('violation_code', 50)->collation('utf8mb4_bin')->nullable();
            $table->decimal('violation_points', 8, 2)->nullable();
            $table->text('action_taken')->nullable();
            $table->foreignId('previous_note_id')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['case_id', 'occurred_at'], 'ix_bk_notes_case_id_occurred_at');
            $table->unique(['case_id', 'id'], 'uq_bk_notes_case_id_id');
            $table->index(['previous_note_id'], 'ix_bk_notes_previous_note_id');
            $table->index(['created_by'], 'ix_bk_notes_created_by');
        });

        Schema::create('bk_followups', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('case_id');
            $table->foreignId('note_id')->nullable();
            $table->foreignId('assigned_employee_id');
            $table->date('due_date')->nullable();
            $table->string('status', 30);
            $table->text('internal_plan');
            $table->text('internal_result')->nullable();
            $table->dateTime('completed_at', 6)->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['assigned_employee_id', 'status', 'due_date'], 'ix_bk_followups_assigned_employee_id_status_due_date');
            $table->unique(['case_id', 'id'], 'uq_bk_followups_case_id_id');
            $table->index(['note_id'], 'ix_bk_followups_note_id');
            $table->index(['created_by'], 'ix_bk_followups_created_by');
        });

        Schema::create('bk_summaries', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('case_id');
            $table->text('summary_text');
            $table->date('summary_date');
            $table->string('status', 30)->default('draft');
            $table->foreignId('previous_summary_id')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['case_id', 'summary_date'], 'ix_bk_summaries_case_id_summary_date');
            $table->unique(['case_id', 'id'], 'uq_bk_summaries_case_id_id');
            $table->index(['previous_summary_id'], 'ix_bk_summaries_previous_summary_id');
            $table->index(['approved_by'], 'ix_bk_summaries_approved_by');
            $table->index(['created_by'], 'ix_bk_summaries_created_by');
        });

        Schema::create('bk_summary_recipients', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('summary_id');
            $table->foreignId('recipient_user_id');
            $table->foreignId('student_guardian_id')->nullable();
            $table->foreignId('homeroom_assignment_id')->nullable();
            $table->foreignId('shared_by');
            $table->dateTime('shared_at', 6);
            $table->dateTime('expires_at', 6)->nullable();
            $table->dateTime('revoked_at', 6)->nullable();
            $table->text('revoke_reason')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['summary_id', 'recipient_user_id', 'shared_at'], 'uq_bk_summary_recipients_summary_id_recipient_user_id_shared_at');
            $table->index(['recipient_user_id', 'revoked_at', 'expires_at'], 'ix_bk_summary_recipients_recipient_user_id_revoked_at_expires_at');
            $table->index(['student_guardian_id'], 'ix_bk_summary_recipients_student_guardian_id');
            $table->index(['homeroom_assignment_id'], 'ix_bk_summary_recipients_homeroom_assignment_id');
            $table->index(['shared_by'], 'ix_bk_summary_recipients_shared_by');
        });

        Schema::create('bk_files', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('case_id');
            $table->foreignId('note_id')->nullable();
            $table->foreignId('followup_id')->nullable();
            $table->foreignId('summary_id')->nullable();
            $table->foreignId('file_id');
            $table->string('visibility', 30);
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['case_id', 'file_id'], 'uq_bk_files_case_id_file_id');
            $table->index(['note_id'], 'ix_bk_files_note_id');
            $table->index(['followup_id'], 'ix_bk_files_followup_id');
            $table->index(['summary_id'], 'ix_bk_files_summary_id');
            $table->index(['file_id'], 'ix_bk_files_file_id');
            $table->index(['created_by'], 'ix_bk_files_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bk_files');

        Schema::dropIfExists('bk_summary_recipients');

        Schema::dropIfExists('bk_summaries');

        Schema::dropIfExists('bk_followups');

        Schema::dropIfExists('bk_notes');

        Schema::dropIfExists('bk_cases');

        Schema::dropIfExists('bk_category_grants');

        Schema::dropIfExists('bk_categories');
    }
};
