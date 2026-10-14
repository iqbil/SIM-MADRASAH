<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 150);
            $table->string('access_class', 30);
            $table->foreignId('retention_policy_id')->nullable();
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_document_categories_code');
            $table->index(['retention_policy_id'], 'ix_document_categories_retention_policy_id');
        });

        Schema::create('document_category_grants', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('category_id');
            $table->boolean('can_read')->default(false);
            $table->boolean('can_write')->default(false);
            $table->boolean('can_download')->default(false);
            $table->boolean('can_export')->default(false);
            $table->dateTime('effective_from', 6);
            $table->dateTime('effective_to', 6)->nullable();
            $table->foreignId('granted_by');
            $table->dateTime('revoked_at', 6)->nullable();
            $table->text('reason');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['user_id', 'category_id', 'effective_from'], 'uq_document_category_grants_user_id_category_id_effective_from');
            $table->index(['user_id', 'category_id', 'effective_to'], 'ix_document_category_grants_user_id_category_id_effective_to');
            $table->index(['category_id'], 'ix_document_category_grants_category_id');
            $table->index(['granted_by'], 'ix_document_category_grants_granted_by');
        });

        Schema::create('letter_number_sequences', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('scope_key', 150)->collation('utf8mb4_bin');
            $table->string('scope_type', 30);
            $table->unsignedSmallInteger('year');
            $table->string('prefix', 50);
            $table->string('format_template', 255);
            $table->unsignedInteger('next_value');
            $table->foreignId('decision_id');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['scope_key'], 'uq_letter_number_sequences_scope_key');
            $table->index(['decision_id'], 'ix_letter_number_sequences_decision_id');
            $table->index(['created_by'], 'ix_letter_number_sequences_created_by');
        });

        Schema::create('letters', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('direction', 30);
            $table->string('status', 30)->default('draft');
            $table->foreignId('category_id');
            $table->foreignId('number_sequence_id')->nullable();
            $table->unsignedInteger('sequence_number')->nullable();
            $table->string('formatted_number', 255)->nullable();
            $table->string('external_number', 255)->nullable();
            $table->date('letter_date');
            $table->date('received_on')->nullable();
            $table->string('sender', 255);
            $table->string('recipient', 255);
            $table->string('subject', 255);
            $table->text('body')->nullable();
            $table->foreignId('issued_by')->nullable();
            $table->dateTime('issued_at', 6)->nullable();
            $table->text('cancel_reason')->nullable();
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['number_sequence_id', 'sequence_number'], 'uq_letters_number_sequence_id_sequence_number');
            $table->unique(['number_sequence_id', 'formatted_number'], 'uq_letters_number_sequence_id_formatted_number');
            $table->index(['direction', 'letter_date'], 'ix_letters_direction_letter_date');
            $table->index(['category_id', 'status', 'letter_date'], 'ix_letters_category_id_status_letter_date');
            $table->index(['issued_by'], 'ix_letters_issued_by');
            $table->index(['created_by'], 'ix_letters_created_by');
        });

        Schema::create('letter_agendas', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('letter_id');
            $table->foreignId('agenda_sequence_id');
            $table->unsignedInteger('agenda_number');
            $table->date('agenda_date');
            $table->text('note')->nullable();
            $table->foreignId('recorded_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['letter_id'], 'uq_letter_agendas_letter_id');
            $table->unique(['agenda_sequence_id', 'agenda_number'], 'uq_letter_agendas_agenda_sequence_id_agenda_number');
            $table->index(['agenda_date'], 'ix_letter_agendas_agenda_date');
            $table->index(['recorded_by'], 'ix_letter_agendas_recorded_by');
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('category_id');
            $table->foreignId('letter_id')->nullable();
            $table->string('archive_code', 100)->collation('utf8mb4_bin');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('document_date')->nullable();
            $table->string('status', 30);
            $table->foreignId('retention_policy_id')->nullable();
            $table->date('retain_until')->nullable();
            $table->boolean('legal_hold')->default(false);
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['archive_code'], 'uq_documents_archive_code');
            $table->index(['category_id', 'status', 'document_date'], 'ix_documents_category_id_status_document_date');
            $table->index(['letter_id'], 'ix_documents_letter_id');
            $table->index(['retention_policy_id'], 'ix_documents_retention_policy_id');
            $table->index(['created_by'], 'ix_documents_created_by');
        });

        Schema::create('document_files', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('document_id');
            $table->foreignId('file_id');
            $table->char('attachment_key', 36)->collation('utf8mb4_bin');
            $table->unsignedSmallInteger('version_number');
            $table->string('purpose', 30);
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['document_id', 'attachment_key', 'version_number'], 'uq_document_files_document_id_attachment_key_version_number');
            $table->index(['file_id'], 'ix_document_files_file_id');
            $table->index(['created_by'], 'ix_document_files_created_by');
        });

        Schema::create('letter_dispositions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('letter_id');
            $table->foreignId('parent_disposition_id')->nullable();
            $table->foreignId('assigned_to_user_id');
            $table->text('instruction');
            $table->date('due_date')->nullable();
            $table->string('status', 30);
            $table->foreignId('assigned_by');
            $table->dateTime('assigned_at', 6);
            $table->dateTime('completed_at', 6)->nullable();
            $table->text('result')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['assigned_to_user_id', 'status', 'due_date'], 'ix_letter_dispositions_assigned_to_user_id_status_due_date');
            $table->unique(['letter_id', 'id'], 'uq_letter_dispositions_letter_id_id');
            $table->index(['parent_disposition_id'], 'ix_letter_dispositions_parent_disposition_id');
            $table->index(['assigned_by'], 'ix_letter_dispositions_assigned_by');
        });

        Schema::create('disposition_actions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('disposition_id');
            $table->string('action', 30);
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->text('note')->nullable();
            $table->foreignId('actor_user_id');
            $table->dateTime('acted_at', 6);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->index(['disposition_id', 'acted_at'], 'ix_disposition_actions_disposition_id_acted_at');
            $table->index(['actor_user_id'], 'ix_disposition_actions_actor_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposition_actions');

        Schema::dropIfExists('letter_dispositions');

        Schema::dropIfExists('document_files');

        Schema::dropIfExists('documents');

        Schema::dropIfExists('letter_agendas');

        Schema::dropIfExists('letters');

        Schema::dropIfExists('letter_number_sequences');

        Schema::dropIfExists('document_category_grants');

        Schema::dropIfExists('document_categories');
    }
};
