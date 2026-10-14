<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('honor_policy_versions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->unsignedSmallInteger('version_number');
            $table->string('status', 30)->default('draft');
            $table->string('source_basis', 30);
            $table->unsignedSmallInteger('jp_minutes');
            $table->json('recognized_quantity_rule');
            $table->json('rate_selection_rule');
            $table->json('rounding_rule');
            $table->json('substitute_rule');
            $table->json('holiday_rule');
            $table->json('allowance_rule');
            $table->json('deduction_rule');
            $table->json('approval_rule');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('decision_id');
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code', 'version_number'], 'uq_honor_policy_versions_code_version_number');
            $table->index(['decision_id'], 'ix_honor_policy_versions_decision_id');
            $table->index(['approved_by'], 'ix_honor_policy_versions_approved_by');
            $table->index(['created_by'], 'ix_honor_policy_versions_created_by');
        });

        Schema::create('honor_components', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 50)->collation('utf8mb4_bin');
            $table->string('name', 150);
            $table->string('component_type', 30);
            $table->string('calculation_method', 30);
            $table->boolean('is_active')->default(1);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_honor_components_code');
        });

        Schema::create('honor_rate_scopes', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('component_id');
            $table->foreignId('employee_id')->nullable();
            $table->foreignId('employee_category_id')->nullable();
            $table->string('scope_key', 100)->collation('utf8mb4_bin');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['scope_key'], 'uq_honor_rate_scopes_scope_key');
            $table->index(['component_id'], 'ix_honor_rate_scopes_component_id');
            $table->index(['employee_id'], 'ix_honor_rate_scopes_employee_id');
            $table->index(['employee_category_id'], 'ix_honor_rate_scopes_employee_category_id');
            $table->index(['created_by'], 'ix_honor_rate_scopes_created_by');
        });

        Schema::create('honor_rates', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('rate_scope_id');
            $table->decimal('amount', 19, 2);
            $table->string('unit', 30);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('policy_version_id');
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->text('reason');
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['rate_scope_id', 'effective_from'], 'uq_honor_rates_rate_scope_id_effective_from');
            $table->index(['rate_scope_id', 'effective_from', 'effective_to'], 'ix_honor_rates_rate_scope_id_effective_from_effective_to');
            $table->index(['policy_version_id'], 'ix_honor_rates_policy_version_id');
            $table->index(['approved_by'], 'ix_honor_rates_approved_by');
            $table->index(['created_by'], 'ix_honor_rates_created_by');
        });

        Schema::create('employee_additional_duties', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('component_id');
            $table->foreignId('position_assignment_id')->nullable();
            $table->foreignId('homeroom_assignment_id')->nullable();
            $table->string('description', 255);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('appointment_file_id')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['employee_id', 'component_id', 'effective_from'], 'uq_employee_additional_duties_employee_id_component_eb615ba459b8');
            $table->index(['employee_id', 'effective_from', 'effective_to'], 'ix_employee_additional_duties_employee_id_effective_dd9069cbdc18');
            $table->index(['component_id'], 'ix_employee_additional_duties_component_id');
            $table->index(['position_assignment_id'], 'ix_employee_additional_duties_position_assignment_id');
            $table->index(['homeroom_assignment_id'], 'ix_employee_additional_duties_homeroom_assignment_id');
            $table->index(['appointment_file_id'], 'ix_employee_additional_duties_appointment_file_id');
            $table->index(['approved_by'], 'ix_employee_additional_duties_approved_by');
            $table->index(['created_by'], 'ix_employee_additional_duties_created_by');
        });

        Schema::create('honor_periods', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('code', 30)->collation('utf8mb4_bin');
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30);
            $table->foreignId('created_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['code'], 'uq_honor_periods_code');
            $table->index(['start_date', 'end_date'], 'ix_honor_periods_start_date_end_date');
            $table->index(['created_by'], 'ix_honor_periods_created_by');
        });

        Schema::create('honor_deductions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('honor_period_id');
            $table->foreignId('component_id');
            $table->date('source_date');
            $table->decimal('amount', 19, 2);
            $table->text('reason');
            $table->string('status', 30)->default('draft');
            $table->foreignId('requested_by');
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('applied_payroll_id')->nullable();
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['operation_key'], 'uq_honor_deductions_operation_key');
            $table->index(['employee_id', 'honor_period_id', 'status'], 'ix_honor_deductions_employee_id_honor_period_id_status');
            $table->index(['honor_period_id'], 'ix_honor_deductions_honor_period_id');
            $table->index(['component_id'], 'ix_honor_deductions_component_id');
            $table->index(['requested_by'], 'ix_honor_deductions_requested_by');
            $table->index(['approved_by'], 'ix_honor_deductions_approved_by');
            $table->index(['applied_payroll_id'], 'ix_honor_deductions_applied_payroll_id');
        });

        Schema::create('payrolls', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('honor_period_id');
            $table->string('payroll_type', 30);
            $table->foreignId('active_version_id')->nullable();
            $table->foreignId('created_by');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['employee_id', 'honor_period_id', 'payroll_type'], 'uq_payrolls_employee_id_honor_period_id_payroll_type');
            $table->unique(['id', 'employee_id', 'honor_period_id'], 'uq_payrolls_id_employee_id_honor_period_id');
            $table->unique(['id', 'employee_id'], 'uq_payrolls_id_employee_id');
            $table->index(['honor_period_id'], 'ix_payrolls_honor_period_id');
            $table->index(['active_version_id'], 'ix_payrolls_active_version_id');
            $table->index(['created_by'], 'ix_payrolls_created_by');
        });

        Schema::create('payroll_versions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('payroll_id');
            $table->unsignedSmallInteger('version_number');
            $table->foreignId('previous_version_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('policy_snapshot');
            $table->json('employee_snapshot');
            $table->char('source_fingerprint', 64)->collation('utf8mb4_bin');
            $table->decimal('teaching_total', 19, 2);
            $table->decimal('allowance_total', 19, 2);
            $table->decimal('deduction_total', 19, 2);
            $table->decimal('adjustment_total', 19, 2);
            $table->decimal('net_total', 19, 2);
            $table->foreignId('calculated_by');
            $table->dateTime('calculated_at', 6);
            $table->foreignId('reviewed_by')->nullable();
            $table->dateTime('reviewed_at', 6)->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->dateTime('locked_at', 6)->nullable();
            $table->text('revision_reason')->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['payroll_id', 'version_number'], 'uq_payroll_versions_payroll_id_version_number');
            $table->unique(['payroll_id', 'id'], 'uq_payroll_versions_payroll_id_id');
            $table->index(['status', 'calculated_at'], 'ix_payroll_versions_status_calculated_at');
            $table->index(['previous_version_id'], 'ix_payroll_versions_previous_version_id');
            $table->index(['calculated_by'], 'ix_payroll_versions_calculated_by');
            $table->index(['reviewed_by'], 'ix_payroll_versions_reviewed_by');
            $table->index(['approved_by'], 'ix_payroll_versions_approved_by');
        });

        Schema::create('payroll_lines', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('payroll_version_id');
            $table->foreignId('component_id');
            $table->string('line_key', 120)->collation('utf8mb4_bin');
            $table->string('component_code_snapshot', 50);
            $table->string('description_snapshot', 255);
            $table->string('component_type_snapshot', 30);
            $table->decimal('quantity', 8, 3);
            $table->decimal('rate_snapshot', 19, 2);
            $table->decimal('amount', 19, 2);
            $table->string('direction', 10);
            $table->json('formula_snapshot');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['payroll_version_id', 'line_key'], 'uq_payroll_lines_payroll_version_id_line_key');
            $table->unique(['payroll_version_id', 'id'], 'uq_payroll_lines_payroll_version_id_id');
            $table->index(['component_id'], 'ix_payroll_lines_component_id');
        });

        Schema::create('payroll_line_sources', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('payroll_line_id');
            $table->foreignId('payroll_version_id');
            $table->string('source_key', 150)->collation('utf8mb4_bin');
            $table->foreignId('meeting_id')->nullable();
            $table->foreignId('realization_revision_id')->nullable();
            $table->foreignId('verification_id')->nullable();
            $table->foreignId('additional_duty_id')->nullable();
            $table->foreignId('deduction_id')->nullable();
            $table->foreignId('adjustment_id')->nullable();
            $table->foreignId('rate_id')->nullable();
            $table->foreignId('policy_version_id');
            $table->date('source_date');
            $table->foreignId('scheduled_employee_id')->nullable();
            $table->foreignId('actual_employee_id')->nullable();
            $table->foreignId('beneficiary_employee_id');
            $table->foreignId('category_assignment_id')->nullable();
            $table->foreignId('employee_category_id')->nullable();
            $table->decimal('quantity_snapshot', 8, 3);
            $table->decimal('rate_snapshot', 19, 2);
            $table->decimal('amount_snapshot', 19, 2);
            $table->json('source_snapshot');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['payroll_version_id', 'source_key'], 'uq_payroll_line_sources_payroll_version_id_source_key');
            $table->index(['meeting_id'], 'ix_payroll_line_sources_meeting_id');
            $table->index(['realization_revision_id'], 'ix_payroll_line_sources_realization_revision_id');
            $table->index(['source_date', 'beneficiary_employee_id'], 'ix_payroll_line_sources_source_date_beneficiary_employee_id');
            $table->unique(['payroll_version_id', 'id'], 'uq_payroll_line_sources_payroll_version_id_id');
            $table->unique(['meeting_id', 'id'], 'uq_payroll_line_sources_meeting_id_id');
            $table->unique(['beneficiary_employee_id', 'id'], 'uq_payroll_line_sources_beneficiary_employee_id_id');
            $table->index(['payroll_line_id'], 'ix_payroll_line_sources_payroll_line_id');
            $table->index(['verification_id'], 'ix_payroll_line_sources_verification_id');
            $table->index(['additional_duty_id'], 'ix_payroll_line_sources_additional_duty_id');
            $table->index(['deduction_id'], 'ix_payroll_line_sources_deduction_id');
            $table->index(['adjustment_id'], 'ix_payroll_line_sources_adjustment_id');
            $table->index(['rate_id'], 'ix_payroll_line_sources_rate_id');
            $table->index(['policy_version_id'], 'ix_payroll_line_sources_policy_version_id');
            $table->index(['scheduled_employee_id'], 'ix_payroll_line_sources_scheduled_employee_id');
            $table->index(['actual_employee_id'], 'ix_payroll_line_sources_actual_employee_id');
            $table->index(['category_assignment_id'], 'ix_payroll_line_sources_category_assignment_id');
            $table->index(['employee_category_id'], 'ix_payroll_line_sources_employee_category_id');
        });

        Schema::create('payroll_source_claims', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('meeting_id');
            $table->foreignId('payroll_id');
            $table->foreignId('payroll_version_id');
            $table->foreignId('payroll_line_source_id');
            $table->foreignId('beneficiary_employee_id');
            $table->dateTime('claimed_at', 6);
            $table->foreignId('claimed_by');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['meeting_id'], 'uq_payroll_source_claims_meeting_id');
            $table->unique(['payroll_line_source_id'], 'uq_payroll_source_claims_payroll_line_source_id');
            $table->index(['payroll_id'], 'ix_payroll_source_claims_payroll_id');
            $table->index(['payroll_version_id'], 'ix_payroll_source_claims_payroll_version_id');
            $table->index(['beneficiary_employee_id'], 'ix_payroll_source_claims_beneficiary_employee_id');
            $table->index(['claimed_by'], 'ix_payroll_source_claims_claimed_by');
        });

        Schema::create('payroll_actions', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('payroll_version_id');
            $table->string('action', 30);
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->foreignId('actor_user_id');
            $table->foreignId('mandate_id');
            $table->text('reason')->nullable();
            $table->dateTime('acted_at', 6);
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['operation_key'], 'uq_payroll_actions_operation_key');
            $table->index(['payroll_version_id', 'acted_at'], 'ix_payroll_actions_payroll_version_id_acted_at');
            $table->index(['actor_user_id'], 'ix_payroll_actions_actor_user_id');
            $table->index(['mandate_id'], 'ix_payroll_actions_mandate_id');
        });

        Schema::create('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->string('batch_key', 100)->collation('utf8mb4_bin');
            $table->string('batch_type', 30);
            $table->foreignId('original_line_source_id')->nullable();
            $table->foreignId('from_employee_id');
            $table->foreignId('to_employee_id')->nullable();
            $table->foreignId('target_honor_period_id');
            $table->decimal('transfer_amount', 19, 2)->nullable();
            $table->char('expected_origin_fingerprint', 64)->collation('utf8mb4_bin');
            $table->text('reason');
            $table->string('status', 30)->default('draft');
            $table->foreignId('requested_by');
            $table->foreignId('reviewed_by')->nullable();
            $table->dateTime('reviewed_at', 6)->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->foreignId('applied_by')->nullable();
            $table->dateTime('applied_at', 6)->nullable();
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->unsignedInteger('lock_version')->default(0);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['batch_key'], 'uq_payroll_adjustment_batches_batch_key');
            $table->unique(['operation_key'], 'uq_payroll_adjustment_batches_operation_key');
            $table->index(['original_line_source_id', 'status'], 'ix_payroll_adjustment_batches_original_line_source_id_status');
            $table->unique(['id', 'target_honor_period_id'], 'uq_payroll_adjustment_batches_id_target_honor_period_id');
            $table->index(['from_employee_id'], 'ix_payroll_adjustment_batches_from_employee_id');
            $table->index(['to_employee_id'], 'ix_payroll_adjustment_batches_to_employee_id');
            $table->index(['target_honor_period_id'], 'ix_payroll_adjustment_batches_target_honor_period_id');
            $table->index(['requested_by'], 'ix_payroll_adjustment_batches_requested_by');
            $table->index(['reviewed_by'], 'ix_payroll_adjustment_batches_reviewed_by');
            $table->index(['approved_by'], 'ix_payroll_adjustment_batches_approved_by');
            $table->index(['applied_by'], 'ix_payroll_adjustment_batches_applied_by');
        });

        Schema::create('payroll_adjustments', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->foreignId('batch_id');
            $table->foreignId('original_payroll_version_id');
            $table->foreignId('original_line_source_id')->nullable();
            $table->foreignId('target_honor_period_id');
            $table->foreignId('employee_id');
            $table->string('direction', 10);
            $table->decimal('amount', 19, 2);
            $table->text('reason');
            $table->foreignId('correction_realization_revision_id')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('requested_by');
            $table->foreignId('approved_by')->nullable();
            $table->dateTime('approved_at', 6)->nullable();
            $table->foreignId('applied_line_source_id')->nullable();
            $table->string('operation_key', 100)->collation('utf8mb4_bin');
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
            $table->unique(['operation_key'], 'uq_payroll_adjustments_operation_key');
            $table->unique(['applied_line_source_id'], 'uq_payroll_adjustments_applied_line_source_id');
            $table->unique(['batch_id', 'employee_id', 'direction'], 'uq_payroll_adjustments_batch_id_employee_id_direction');
            $table->index(['employee_id', 'target_honor_period_id', 'status'], 'ix_payroll_adjustments_employee_id_target_honor_period_id_status');
            $table->index(['original_payroll_version_id'], 'ix_payroll_adjustments_original_payroll_version_id');
            $table->index(['original_line_source_id'], 'ix_payroll_adjustments_original_line_source_id');
            $table->index(['target_honor_period_id'], 'ix_payroll_adjustments_target_honor_period_id');
            $table->index(['correction_realization_revision_id'], 'ix_payroll_adjustments_correction_realization_revision_id');
            $table->index(['requested_by'], 'ix_payroll_adjustments_requested_by');
            $table->index(['approved_by'], 'ix_payroll_adjustments_approved_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');

        Schema::dropIfExists('payroll_adjustment_batches');

        Schema::dropIfExists('payroll_actions');

        Schema::dropIfExists('payroll_source_claims');

        Schema::dropIfExists('payroll_line_sources');

        Schema::dropIfExists('payroll_lines');

        Schema::dropIfExists('payroll_versions');

        Schema::dropIfExists('payrolls');

        Schema::dropIfExists('honor_deductions');

        Schema::dropIfExists('honor_periods');

        Schema::dropIfExists('employee_additional_duties');

        Schema::dropIfExists('honor_rates');

        Schema::dropIfExists('honor_rate_scopes');

        Schema::dropIfExists('honor_components');

        Schema::dropIfExists('honor_policy_versions');
    }
};
