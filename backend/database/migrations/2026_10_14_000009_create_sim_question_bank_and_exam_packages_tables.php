<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_types', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 30)->collation('utf8mb4_bin');
            $table->string('name', 100);
            $table->boolean('has_options')->default(false);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_question_types_code');
        });

        Schema::create('subject_topics', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('subject_id');
            $table->unsignedTinyInteger('grade');
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 200);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['subject_id', 'grade', 'code'], 'uq_subject_topics_subject_id_grade_code');
        });

        Schema::create('questions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('owner_employee_id');
            $table->foreignId('subject_id');
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->foreignId('current_version_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_questions_code');
            $table->index(['owner_employee_id', 'status'], 'ix_questions_owner_employee_id_status');
            $table->index(['subject_id', 'status'], 'ix_questions_subject_id_status');
            $table->index(['current_version_id'], 'ix_questions_current_version_id');
            $table->index(['created_by'], 'ix_questions_created_by');
        });

        Schema::create('question_versions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('question_id');
            $table->unsignedSmallInteger('version_number');
            $table->foreignId('question_type_id');
            $table->foreignId('topic_id')->nullable();
            $table->unsignedTinyInteger('grade');
            $table->string('difficulty', 30);
            $table->unsignedTinyInteger('cognitive_level');
            $table->longText('body');
            $table->string('body_format', 30);
            $table->text('revision_reason')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['question_id', 'version_number'], 'uq_question_versions_question_id_version_number');
            $table->unique(['question_id', 'id'], 'uq_question_versions_question_id_id');
            $table->index(['grade', 'difficulty', 'cognitive_level'], 'ix_question_versions_grade_difficulty_cognitive_level');
            $table->index(['question_type_id'], 'ix_question_versions_question_type_id');
            $table->index(['topic_id'], 'ix_question_versions_topic_id');
            $table->index(['created_by'], 'ix_question_versions_created_by');
        });

        Schema::create('question_options', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('question_version_id');
            $table->char('option_key', 36)->collation('utf8mb4_bin');
            $table->unsignedSmallInteger('position');
            $table->string('label', 10);
            $table->text('body');
            $table->string('body_format', 30);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['question_version_id', 'option_key'], 'uq_question_options_question_version_id_option_key');
            $table->unique(['question_version_id', 'position'], 'uq_question_options_question_version_id_position');
            $table->unique(['question_version_id', 'id'], 'uq_question_options_question_version_id_id');
        });

        Schema::create('question_keys', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('question_version_id');
            $table->json('correct_option_ids')->nullable();
            $table->longText('answer_text')->nullable();
            $table->longText('explanation')->nullable();
            $table->json('scoring_rubric')->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['question_version_id'], 'uq_question_keys_question_version_id');
            $table->index(['created_by'], 'ix_question_keys_created_by');
        });

        Schema::create('question_files', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('question_version_id');
            $table->foreignId('option_id')->nullable();
            $table->foreignId('file_id');
            $table->string('purpose', 30);
            $table->unsignedSmallInteger('position');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['question_version_id', 'file_id', 'purpose'], 'uq_question_files_question_version_id_file_id_purpose');
            $table->index(['option_id'], 'ix_question_files_option_id');
            $table->index(['file_id'], 'ix_question_files_file_id');
            $table->index(['created_by'], 'ix_question_files_created_by');
        });

        Schema::create('question_collaborators', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('question_id');
            $table->foreignId('employee_id');
            $table->string('access_level', 30);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('granted_by');
            $table->dateTime('revoked_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['question_id', 'employee_id', 'effective_from'], 'uq_question_collaborators_question_id_employee_id_effective_from');
            $table->index(['employee_id'], 'ix_question_collaborators_employee_id');
            $table->index(['granted_by'], 'ix_question_collaborators_granted_by');
        });

        Schema::create('exam_packages', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 200);
            $table->foreignId('subject_id');
            $table->unsignedTinyInteger('grade');
            $table->foreignId('semester_id');
            $table->foreignId('owner_employee_id');
            $table->string('status', 30)->default('draft');
            $table->json('template_snapshot');
            $table->foreignId('finalized_by')->nullable();
            $table->dateTime('finalized_at', 6)->nullable();
            $table->char('content_hash', 64)->collation('utf8mb4_bin')->nullable();
            $table->foreignId('supersedes_package_id')->nullable();
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_exam_packages_code');
            $table->index(['semester_id', 'subject_id', 'status'], 'ix_exam_packages_semester_id_subject_id_status');
            $table->index(['subject_id'], 'ix_exam_packages_subject_id');
            $table->index(['owner_employee_id'], 'ix_exam_packages_owner_employee_id');
            $table->index(['finalized_by'], 'ix_exam_packages_finalized_by');
            $table->index(['supersedes_package_id'], 'ix_exam_packages_supersedes_package_id');
            $table->index(['created_by'], 'ix_exam_packages_created_by');
        });

        Schema::create('exam_package_items', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('exam_package_id');
            $table->foreignId('question_version_id');
            $table->unsignedSmallInteger('position');
            $table->decimal('points', 8, 2)->nullable();
            $table->string('question_type_snapshot', 30);
            $table->longText('body_snapshot');
            $table->string('format_snapshot', 30);
            $table->json('metadata_snapshot');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['exam_package_id', 'position'], 'uq_exam_package_items_exam_package_id_position');
            $table->unique(['exam_package_id', 'question_version_id'], 'uq_exam_package_items_exam_package_id_question_version_id');
            $table->index(['question_version_id'], 'ix_exam_package_items_question_version_id');
        });

        Schema::create('exam_package_options', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('package_item_id');
            $table->foreignId('source_option_id');
            $table->unsignedSmallInteger('position');
            $table->string('label_snapshot', 10);
            $table->text('body_snapshot');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['package_item_id', 'position'], 'uq_exam_package_options_package_item_id_position');
            $table->unique(['package_item_id', 'source_option_id'], 'uq_exam_package_options_package_item_id_source_option_id');
            $table->index(['source_option_id'], 'ix_exam_package_options_source_option_id');
        });

        Schema::create('exam_package_keys', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('package_item_id');
            $table->json('correct_package_option_ids')->nullable();
            $table->json('correct_labels_snapshot')->nullable();
            $table->longText('answer_snapshot')->nullable();
            $table->longText('explanation_snapshot')->nullable();
            $table->json('rubric_snapshot')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['package_item_id'], 'uq_exam_package_keys_package_item_id');
        });

        Schema::create('exam_package_files', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('exam_package_id');
            $table->foreignId('file_id');
            $table->string('purpose', 30);
            $table->unsignedSmallInteger('position');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['exam_package_id', 'file_id', 'purpose'], 'uq_exam_package_files_exam_package_id_file_id_purpose');
            $table->index(['file_id'], 'ix_exam_package_files_file_id');
            $table->index(['created_by'], 'ix_exam_package_files_created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_package_files');

        Schema::dropIfExists('exam_package_keys');

        Schema::dropIfExists('exam_package_options');

        Schema::dropIfExists('exam_package_items');

        Schema::dropIfExists('exam_packages');

        Schema::dropIfExists('question_collaborators');

        Schema::dropIfExists('question_files');

        Schema::dropIfExists('question_keys');

        Schema::dropIfExists('question_options');

        Schema::dropIfExists('question_versions');

        Schema::dropIfExists('questions');

        Schema::dropIfExists('subject_topics');

        Schema::dropIfExists('question_types');
    }
};
