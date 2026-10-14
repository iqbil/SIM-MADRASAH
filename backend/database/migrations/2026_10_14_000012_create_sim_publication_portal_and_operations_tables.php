<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_categories', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 150);
            $table->string('content_type', 30);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_content_categories_code');
        });

        Schema::create('contents', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('type', 30);
            $table->string('slug', 200)->collation('utf8mb4_bin');
            $table->foreignId('category_id')->nullable();
            $table->foreignId('current_revision_id')->nullable();
            $table->foreignId('published_revision_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->dateTime('scheduled_publish_at', 6)->nullable();
            $table->dateTime('published_at', 6)->nullable();
            $table->dateTime('archived_at', 6)->nullable();
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['slug'], 'uq_contents_slug');
            $table->index(['type', 'status', 'published_at'], 'ix_contents_type_status_published_at');
            $table->index(['category_id'], 'ix_contents_category_id');
            $table->index(['current_revision_id'], 'ix_contents_current_revision_id');
            $table->index(['published_revision_id'], 'ix_contents_published_revision_id');
            $table->index(['created_by'], 'ix_contents_created_by');
        });

        Schema::create('content_revisions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('content_id');
            $table->unsignedSmallInteger('version_number');
            $table->string('title', 255);
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('body_format', 30);
            $table->dateTime('event_start_at', 6)->nullable();
            $table->dateTime('event_end_at', 6)->nullable();
            $table->string('location', 255)->nullable();
            $table->date('achievement_date')->nullable();
            $table->string('achievement_level', 100)->nullable();
            $table->string('contact_email', 254)->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->json('metadata')->nullable();
            $table->text('revision_reason')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['content_id', 'version_number'], 'uq_content_revisions_content_id_version_number');
            $table->unique(['content_id', 'id'], 'uq_content_revisions_content_id_id');
            $table->index(['created_by'], 'ix_content_revisions_created_by');
        });

        Schema::create('content_files', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('content_revision_id');
            $table->foreignId('file_id');
            $table->unsignedSmallInteger('position');
            $table->string('caption', 255)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->string('purpose', 30);
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['content_revision_id', 'position'], 'uq_content_files_content_revision_id_position');
            $table->index(['file_id'], 'ix_content_files_file_id');
            $table->index(['created_by'], 'ix_content_files_created_by');
        });

        Schema::create('publication_actions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('content_id');
            $table->foreignId('content_revision_id');
            $table->string('action', 30);
            $table->foreignId('actor_user_id');
            $table->foreignId('mandate_id')->nullable();
            $table->text('reason')->nullable();
            $table->dateTime('acted_at', 6);
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['operation_key'], 'uq_publication_actions_operation_key');
            $table->index(['content_id', 'acted_at'], 'ix_publication_actions_content_id_acted_at');
            $table->unique(['content_revision_id', 'id'], 'uq_publication_actions_content_revision_id_id');
            $table->index(['actor_user_id'], 'ix_publication_actions_actor_user_id');
            $table->index(['mandate_id'], 'ix_publication_actions_mandate_id');
        });

        Schema::create('publication_assets', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('content_revision_id');
            $table->foreignId('source_file_id')->nullable();
            $table->foreignId('public_file_id');
            $table->foreignId('publication_action_id');
            $table->foreignId('source_document_id')->nullable();
            $table->text('approval_basis');
            $table->foreignId('approved_by');
            $table->dateTime('approved_at', 6);
            $table->dateTime('revoked_at', 6)->nullable();
            $table->text('revoke_reason')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['content_revision_id', 'public_file_id'], 'uq_publication_assets_content_revision_id_public_file_id');
            $table->index(['public_file_id', 'revoked_at'], 'ix_publication_assets_public_file_id_revoked_at');
            $table->index(['source_file_id'], 'ix_publication_assets_source_file_id');
            $table->index(['publication_action_id'], 'ix_publication_assets_publication_action_id');
            $table->index(['source_document_id'], 'ix_publication_assets_source_document_id');
            $table->index(['approved_by'], 'ix_publication_assets_approved_by');
        });

        Schema::create('public_staff_profiles', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('content_revision_id');
            $table->string('display_name', 200);
            $table->string('public_position', 150)->nullable();
            $table->text('bio')->nullable();
            $table->foreignId('photo_public_file_id')->nullable();
            $table->foreignId('approved_by');
            $table->dateTime('approved_at', 6);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['employee_id', 'content_revision_id'], 'uq_public_staff_profiles_employee_id_content_revision_id');
            $table->index(['content_revision_id'], 'ix_public_staff_profiles_content_revision_id');
            $table->index(['photo_public_file_id'], 'ix_public_staff_profiles_photo_public_file_id');
            $table->index(['approved_by'], 'ix_public_staff_profiles_approved_by');
        });

        Schema::create('portal_announcements', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('title', 255);
            $table->text('body');
            $table->string('status', 30)->default('draft');
            $table->dateTime('starts_at', 6);
            $table->dateTime('ends_at', 6)->nullable();
            $table->foreignId('published_by')->nullable();
            $table->dateTime('published_at', 6)->nullable();
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['status', 'starts_at', 'ends_at'], 'ix_portal_announcements_status_starts_at_ends_at');
            $table->index(['published_by'], 'ix_portal_announcements_published_by');
            $table->index(['created_by'], 'ix_portal_announcements_created_by');
        });

        Schema::create('portal_announcement_targets', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('announcement_id');
            $table->string('scope_type', 30);
            $table->foreignId('class_period_id')->nullable();
            $table->foreignId('student_id')->nullable();
            $table->string('scope_key', 100)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['announcement_id', 'scope_key'], 'uq_portal_announcement_targets_announcement_id_scope_key');
            $table->index(['class_period_id'], 'ix_portal_announcement_targets_class_period_id');
            $table->index(['student_id'], 'ix_portal_announcement_targets_student_id');
        });

        Schema::create('portal_announcement_files', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('announcement_id');
            $table->foreignId('file_id');
            $table->unsignedSmallInteger('position');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['announcement_id', 'position'], 'uq_portal_announcement_files_announcement_id_position');
            $table->unique(['announcement_id', 'file_id'], 'uq_portal_announcement_files_announcement_id_file_id');
            $table->index(['file_id'], 'ix_portal_announcement_files_file_id');
            $table->index(['created_by'], 'ix_portal_announcement_files_created_by');
        });

        Schema::create('report_jobs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('requested_by');
            $table->string('report_type', 80);
            $table->string('output_format', 10);
            $table->json('filters');
            $table->json('scope_snapshot');
            $table->string('status', 30);
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->foreignId('output_file_id')->nullable();
            $table->dateTime('requested_at', 6);
            $table->dateTime('started_at', 6)->nullable();
            $table->dateTime('finished_at', 6)->nullable();
            $table->dateTime('expires_at', 6);
            $table->unsignedSmallInteger('attempts');
            $table->string('error_code', 100)->collation('utf8mb4_bin')->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('dataset_cutoff_at', 6)->nullable();
            $table->string('dataset_status', 30);
            $table->unsignedInteger('dataset_row_count');
            $table->char('dataset_hash', 64)->collation('utf8mb4_bin')->nullable();
            $table->dateTime('dataset_extracted_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['requested_by', 'operation_key'], 'uq_report_jobs_requested_by_operation_key');
            $table->index(['requested_by', 'status', 'requested_at'], 'ix_report_jobs_requested_by_status_requested_at');
            $table->index(['status', 'expires_at'], 'ix_report_jobs_status_expires_at');
            $table->index(['output_file_id'], 'ix_report_jobs_output_file_id');
        });

        Schema::create('report_dataset_rows', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('report_job_id');
            $table->unsignedInteger('row_number');
            $table->json('payload');
            $table->char('row_hash', 64)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['report_job_id', 'row_number'], 'uq_report_dataset_rows_report_job_id_row_number');
        });

        Schema::create('import_batches', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('requested_by');
            $table->string('module', 50);
            $table->string('source_system', 100)->collation('utf8mb4_bin');
            $table->foreignId('source_file_id');
            $table->char('file_hash', 64)->collation('utf8mb4_bin');
            $table->string('template_version', 30);
            $table->string('status', 30);
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->unsignedInteger('total_rows');
            $table->unsignedInteger('valid_rows');
            $table->unsignedInteger('failed_rows');
            $table->unsignedInteger('inserted_rows');
            $table->unsignedInteger('updated_rows');
            $table->string('atomicity_mode', 30);
            $table->foreignId('confirmed_by')->nullable();
            $table->dateTime('confirmed_at', 6)->nullable();
            $table->dateTime('started_at', 6)->nullable();
            $table->dateTime('finished_at', 6)->nullable();
            $table->foreignId('error_report_file_id')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['requested_by', 'operation_key'], 'uq_import_batches_requested_by_operation_key');
            $table->index(['module', 'file_hash'], 'ix_import_batches_module_file_hash');
            $table->index(['status', 'created_at'], 'ix_import_batches_status_created_at');
            $table->index(['source_file_id'], 'ix_import_batches_source_file_id');
            $table->index(['confirmed_by'], 'ix_import_batches_confirmed_by');
            $table->index(['error_report_file_id'], 'ix_import_batches_error_report_file_id');
        });

        Schema::create('source_records', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('source_system', 100)->collation('utf8mb4_bin');
            $table->string('entity_type', 30);
            $table->string('external_id', 150)->collation('utf8mb4_bin');
            $table->foreignId('person_id')->nullable();
            $table->foreignId('student_id')->nullable();
            $table->foreignId('guardian_id')->nullable();
            $table->foreignId('employee_id')->nullable();
            $table->foreignId('subject_id')->nullable();
            $table->foreignId('class_id')->nullable();
            $table->char('content_hash', 64)->collation('utf8mb4_bin');
            $table->foreignId('last_batch_id');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['source_system', 'entity_type', 'external_id'], 'uq_source_records_source_system_entity_type_external_id');
            $table->index(['person_id'], 'ix_source_records_person_id');
            $table->index(['student_id'], 'ix_source_records_student_id');
            $table->index(['guardian_id'], 'ix_source_records_guardian_id');
            $table->index(['employee_id'], 'ix_source_records_employee_id');
            $table->index(['subject_id'], 'ix_source_records_subject_id');
            $table->index(['class_id'], 'ix_source_records_class_id');
            $table->index(['last_batch_id'], 'ix_source_records_last_batch_id');
        });

        Schema::create('import_rows', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('import_batch_id');
            $table->unsignedInteger('row_number');
            $table->foreignId('source_record_id')->nullable();
            $table->string('external_id', 150)->collation('utf8mb4_bin')->nullable();
            $table->json('normalized_values')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('duplicate_candidates')->nullable();
            $table->string('status', 30);
            $table->string('result_action', 30)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['import_batch_id', 'row_number'], 'uq_import_rows_import_batch_id_row_number');
            $table->index(['import_batch_id', 'status'], 'ix_import_rows_import_batch_id_status');
            $table->index(['source_record_id'], 'ix_import_rows_source_record_id');
        });

        Schema::create('idempotency_requests', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('actor_user_id');
            $table->string('operation', 100);
            $table->string('request_key', 100)->collation('utf8mb4_bin');
            $table->char('request_hash', 64)->collation('utf8mb4_bin');
            $table->string('status', 30);
            $table->json('response_reference')->nullable();
            $table->dateTime('expires_at', 6);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['actor_user_id', 'operation', 'request_key'], 'uq_idempotency_requests_actor_user_id_operation_request_key');
            $table->index(['expires_at'], 'ix_idempotency_requests_expires_at');
        });

        Schema::create('retention_policies', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 80)->collation('utf8mb4_bin');
            $table->unsignedSmallInteger('version_number');
            $table->string('domain', 50);
            $table->unsignedInteger('retention_days')->nullable();
            $table->json('retention_rule')->nullable();
            $table->string('disposal_method', 30);
            $table->string('status', 30)->default('draft');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('decision_id');
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code', 'version_number'], 'uq_retention_policies_code_version_number');
            $table->index(['decision_id'], 'ix_retention_policies_decision_id');
            $table->index(['approved_by'], 'ix_retention_policies_approved_by');
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('actor_user_id')->nullable();
            $table->string('actor_label_snapshot', 200)->nullable();
            $table->string('event', 120);
            $table->string('object_type', 100);
            $table->string('object_id', 100);
            $table->dateTime('occurred_at', 6);
            $table->text('reason')->nullable();
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->char('request_id', 36);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('classification', 30);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['object_type', 'object_id', 'occurred_at'], 'ix_audit_logs_object_type_object_id_occurred_at');
            $table->index(['actor_user_id', 'occurred_at'], 'ix_audit_logs_actor_user_id_occurred_at');
            $table->index(['event', 'occurred_at'], 'ix_audit_logs_event_occurred_at');
            $table->index(['request_id'], 'ix_audit_logs_request_id');
        });

        Schema::create('backup_runs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('backup_key', 100)->collation('utf8mb4_bin');
            $table->string('status', 30);
            $table->dateTime('started_at', 6);
            $table->dateTime('finished_at', 6)->nullable();
            $table->string('database_snapshot_reference', 500)->nullable();
            $table->string('file_manifest_reference', 500)->nullable();
            $table->string('destination_reference', 500);
            $table->boolean('is_encrypted')->default(false);
            $table->string('encryption_key_reference', 150)->nullable();
            $table->char('checksum', 64)->nullable();
            $table->unsignedBigInteger('total_size_bytes')->nullable();
            $table->foreignId('retention_policy_id')->nullable();
            $table->dateTime('expires_at', 6)->nullable();
            $table->foreignId('initiated_by')->nullable();
            $table->string('error_code', 100)->collation('utf8mb4_bin')->nullable();
            $table->text('error_message')->nullable();
            $table->string('application_version', 100);
            $table->string('schema_version', 100);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['backup_key'], 'uq_backup_runs_backup_key');
            $table->index(['status', 'started_at'], 'ix_backup_runs_status_started_at');
            $table->index(['expires_at'], 'ix_backup_runs_expires_at');
            $table->index(['retention_policy_id'], 'ix_backup_runs_retention_policy_id');
            $table->index(['initiated_by'], 'ix_backup_runs_initiated_by');
        });

        Schema::create('restore_runs', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('backup_run_id');
            $table->string('target_environment', 100);
            $table->string('status', 30);
            $table->dateTime('started_at', 6);
            $table->dateTime('finished_at', 6)->nullable();
            $table->unsignedInteger('measured_rpo_seconds')->nullable();
            $table->unsignedInteger('measured_rto_seconds')->nullable();
            $table->json('validation_result')->nullable();
            $table->foreignId('evidence_file_id')->nullable();
            $table->foreignId('performed_by');
            $table->text('error_message')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['backup_run_id', 'started_at'], 'ix_restore_runs_backup_run_id_started_at');
            $table->index(['evidence_file_id'], 'ix_restore_runs_evidence_file_id');
            $table->index(['performed_by'], 'ix_restore_runs_performed_by');
        });

        Schema::create('system_events', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('event_type', 100);
            $table->string('severity', 20);
            $table->string('status', 30);
            $table->dateTime('occurred_at', 6);
            $table->string('correlation_id', 100)->nullable();
            $table->text('safe_message');
            $table->json('metadata')->nullable();
            $table->foreignId('acknowledged_by')->nullable();
            $table->dateTime('acknowledged_at', 6)->nullable();
            $table->dateTime('resolved_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['status', 'severity', 'occurred_at'], 'ix_system_events_status_severity_occurred_at');
            $table->index(['acknowledged_by'], 'ix_system_events_acknowledged_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_events');

        Schema::dropIfExists('restore_runs');

        Schema::dropIfExists('backup_runs');

        Schema::dropIfExists('audit_logs');

        Schema::dropIfExists('retention_policies');

        Schema::dropIfExists('idempotency_requests');

        Schema::dropIfExists('import_rows');

        Schema::dropIfExists('source_records');

        Schema::dropIfExists('import_batches');

        Schema::dropIfExists('report_dataset_rows');

        Schema::dropIfExists('report_jobs');

        Schema::dropIfExists('portal_announcement_files');

        Schema::dropIfExists('portal_announcement_targets');

        Schema::dropIfExists('portal_announcements');

        Schema::dropIfExists('public_staff_profiles');

        Schema::dropIfExists('publication_assets');

        Schema::dropIfExists('publication_actions');

        Schema::dropIfExists('content_files');

        Schema::dropIfExists('content_revisions');

        Schema::dropIfExists('contents');

        Schema::dropIfExists('content_categories');
    }
};
