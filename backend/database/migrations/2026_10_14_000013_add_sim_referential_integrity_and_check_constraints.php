<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table): void {
            $table->foreign(['logo_file_id'], 'fk_institutions_logo_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign(['person_id'], 'fk_users_person_id')->references(['id'])->on('persons')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->foreign(['user_id'], 'fk_user_roles_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->foreign(['role_id'], 'fk_user_roles_role_id')->references(['id'])->on('roles')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->foreign(['granted_by'], 'fk_user_roles_granted_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->foreign(['revoked_by'], 'fk_user_roles_revoked_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->foreign(['role_id'], 'fk_role_permissions_role_id')->references(['id'])->on('roles')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->foreign(['permission_id'], 'fk_role_permissions_permission_id')->references(['id'])->on('permissions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_role_permissions_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->foreign(['user_id'], 'fk_authorization_mandates_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->foreign(['permission_id'], 'fk_authorization_mandates_permission_id')->references(['id'])->on('permissions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->foreign(['semester_id'], 'fk_authorization_mandates_semester_id')->references(['id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->foreign(['class_period_id'], 'fk_authorization_mandates_class_period_id')->references(['id'])->on('class_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_authorization_mandates_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->foreign(['granted_by'], 'fk_authorization_mandates_granted_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->foreign(['revoked_by'], 'fk_authorization_mandates_revoked_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('business_decisions', function (Blueprint $table): void {
            $table->foreign(['owner_user_id'], 'fk_business_decisions_owner_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('business_decisions', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_business_decisions_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('business_decisions', function (Blueprint $table): void {
            $table->foreign(['supersedes_id'], 'fk_business_decisions_supersedes_id')->references(['id'])->on('business_decisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('system_settings', function (Blueprint $table): void {
            $table->foreign(['updated_by'], 'fk_system_settings_updated_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('files', function (Blueprint $table): void {
            $table->foreign(['uploaded_by'], 'fk_files_uploaded_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('files', function (Blueprint $table): void {
            $table->foreign(['retention_policy_id'], 'fk_files_retention_policy_id')->references(['id'])->on('retention_policies')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->foreign(['person_id'], 'fk_students_person_id')->references(['id'])->on('persons')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->foreign(['status_id'], 'fk_students_status_id')->references(['id'])->on('student_statuses')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('guardians', function (Blueprint $table): void {
            $table->foreign(['person_id'], 'fk_guardians_person_id')->references(['id'])->on('persons')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->foreign(['student_id'], 'fk_student_guardians_student_id')->references(['id'])->on('students')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->foreign(['guardian_id'], 'fk_student_guardians_guardian_id')->references(['id'])->on('guardians')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->foreign(['verified_by'], 'fk_student_guardians_verified_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->foreign(['verification_evidence_file_id'], 'fk_student_guardians_verification_evidence_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->foreign(['revoked_by'], 'fk_student_guardians_revoked_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_student_guardians_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->foreign(['person_id'], 'fk_employees_person_id')->references(['id'])->on('persons')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_category_assignments', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_employee_category_assignments_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_category_assignments', function (Blueprint $table): void {
            $table->foreign(['category_id'], 'fk_employee_category_assignments_category_id')->references(['id'])->on('employee_categories')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_category_assignments', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_employee_category_assignments_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_positions', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_employee_positions_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_positions', function (Blueprint $table): void {
            $table->foreign(['position_id'], 'fk_employee_positions_position_id')->references(['id'])->on('positions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_positions', function (Blueprint $table): void {
            $table->foreign(['appointment_file_id'], 'fk_employee_positions_appointment_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_positions', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_employee_positions_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_homeroom_assignments_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->foreign(['class_period_id'], 'fk_homeroom_assignments_class_period_id')->references(['id'])->on('class_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->foreign(['semester_id'], 'fk_homeroom_assignments_semester_id')->references(['id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->foreign(['appointment_file_id'], 'fk_homeroom_assignments_appointment_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_homeroom_assignments_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_teaching_assignments_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->foreign(['subject_id'], 'fk_teaching_assignments_subject_id')->references(['id'])->on('subjects')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->foreign(['class_period_id'], 'fk_teaching_assignments_class_period_id')->references(['id'])->on('class_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->foreign(['semester_id'], 'fk_teaching_assignments_semester_id')->references(['id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_teaching_assignments_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('academic_years', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_academic_years_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('semesters', function (Blueprint $table): void {
            $table->foreign(['academic_year_id'], 'fk_semesters_academic_year_id')->references(['id'])->on('academic_years')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('semesters', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_semesters_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('academic_contexts', function (Blueprint $table): void {
            $table->foreign(['active_academic_year_id'], 'fk_academic_contexts_active_academic_year_id')->references(['id'])->on('academic_years')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('academic_contexts', function (Blueprint $table): void {
            $table->foreign(['changed_by'], 'fk_academic_contexts_changed_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('class_periods', function (Blueprint $table): void {
            $table->foreign(['class_id'], 'fk_class_periods_class_id')->references(['id'])->on('classes')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('class_periods', function (Blueprint $table): void {
            $table->foreign(['academic_year_id'], 'fk_class_periods_academic_year_id')->references(['id'])->on('academic_years')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->foreign(['student_id'], 'fk_student_enrollments_student_id')->references(['id'])->on('students')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->foreign(['class_period_id'], 'fk_student_enrollments_class_period_id')->references(['id'])->on('class_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_student_enrollments_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->foreign(['student_id'], 'fk_student_transitions_student_id')->references(['id'])->on('students')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->foreign(['from_status_id'], 'fk_student_transitions_from_status_id')->references(['id'])->on('student_statuses')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->foreign(['to_status_id'], 'fk_student_transitions_to_status_id')->references(['id'])->on('student_statuses')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->foreign(['confirmed_by'], 'fk_student_transitions_confirmed_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('academic_calendar_events', function (Blueprint $table): void {
            $table->foreign(['academic_year_id'], 'fk_academic_calendar_events_academic_year_id')->references(['id'])->on('academic_years')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('academic_calendar_events', function (Blueprint $table): void {
            $table->foreign(['semester_id'], 'fk_academic_calendar_events_semester_id')->references(['id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('academic_calendar_events', function (Blueprint $table): void {
            $table->foreign(['class_period_id'], 'fk_academic_calendar_events_class_period_id')->references(['id'])->on('class_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('academic_calendar_events', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_academic_calendar_events_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('lesson_slots', function (Blueprint $table): void {
            $table->foreign(['semester_id'], 'fk_lesson_slots_semester_id')->references(['id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('timetables', function (Blueprint $table): void {
            $table->foreign(['semester_id'], 'fk_timetables_semester_id')->references(['id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('timetables', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_timetables_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('timetable_versions', function (Blueprint $table): void {
            $table->foreign(['timetable_id'], 'fk_timetable_versions_timetable_id')->references(['id'])->on('timetables')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('timetable_versions', function (Blueprint $table): void {
            $table->foreign(['published_by'], 'fk_timetable_versions_published_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('timetable_versions', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_timetable_versions_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('schedule_entries', function (Blueprint $table): void {
            $table->foreign(['timetable_id'], 'fk_schedule_entries_timetable_id')->references(['id'])->on('timetables')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('schedule_entries', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_schedule_entries_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('schedule_entry_versions', function (Blueprint $table): void {
            $table->foreign(['schedule_entry_id'], 'fk_schedule_entry_versions_schedule_entry_id')->references(['id'])->on('schedule_entries')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('schedule_entry_versions', function (Blueprint $table): void {
            $table->foreign(['timetable_version_id'], 'fk_schedule_entry_versions_timetable_version_id')->references(['id'])->on('timetable_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('schedule_entry_versions', function (Blueprint $table): void {
            $table->foreign(['teaching_assignment_id'], 'fk_schedule_entry_versions_teaching_assignment_id')->references(['id'])->on('teaching_assignments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('schedule_entry_slots', function (Blueprint $table): void {
            $table->foreign(['schedule_entry_version_id'], 'fk_schedule_entry_slots_schedule_entry_version_id')->references(['id'])->on('schedule_entry_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('schedule_entry_slots', function (Blueprint $table): void {
            $table->foreign(['lesson_slot_id'], 'fk_schedule_entry_slots_lesson_slot_id')->references(['id'])->on('lesson_slots')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->foreign(['schedule_entry_id'], 'fk_meetings_schedule_entry_id')->references(['id'])->on('schedule_entries')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->foreign(['semester_id'], 'fk_meetings_semester_id')->references(['id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->foreign(['class_period_id'], 'fk_meetings_class_period_id')->references(['id'])->on('class_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->foreign(['subject_id'], 'fk_meetings_subject_id')->references(['id'])->on('subjects')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->foreign(['scheduled_employee_id'], 'fk_meetings_scheduled_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->foreign(['calendar_event_id'], 'fk_meetings_calendar_event_id')->references(['id'])->on('academic_calendar_events')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->foreign(['rescheduled_from_id'], 'fk_meetings_rescheduled_from_id')->references(['id'])->on('meetings')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_meetings_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('substitute_assignments', function (Blueprint $table): void {
            $table->foreign(['meeting_id'], 'fk_substitute_assignments_meeting_id')->references(['id'])->on('meetings')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('substitute_assignments', function (Blueprint $table): void {
            $table->foreign(['substitute_employee_id'], 'fk_substitute_assignments_substitute_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('substitute_assignments', function (Blueprint $table): void {
            $table->foreign(['assigned_by'], 'fk_substitute_assignments_assigned_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('substitute_assignments', function (Blueprint $table): void {
            $table->foreign(['revoked_by'], 'fk_substitute_assignments_revoked_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('teaching_realizations', function (Blueprint $table): void {
            $table->foreign(['meeting_id'], 'fk_teaching_realizations_meeting_id')->references(['id'])->on('meetings')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('teaching_realizations', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_teaching_realizations_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->foreign(['realization_id'], 'fk_realization_revisions_realization_id')->references(['id'])->on('teaching_realizations')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->foreign(['actual_employee_id'], 'fk_realization_revisions_actual_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->foreign(['substitute_assignment_id'], 'fk_realization_revisions_substitute_assignment_id')->references(['id'])->on('substitute_assignments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->foreign(['submitted_by'], 'fk_realization_revisions_submitted_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_realization_revisions_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('realization_verifications', function (Blueprint $table): void {
            $table->foreign(['realization_revision_id'], 'fk_realization_verifications_realization_revision_id')->references(['id'])->on('realization_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('realization_verifications', function (Blueprint $table): void {
            $table->foreign(['verifier_user_id'], 'fk_realization_verifications_verifier_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('realization_verifications', function (Blueprint $table): void {
            $table->foreign(['mandate_id'], 'fk_realization_verifications_mandate_id')->references(['id'])->on('authorization_mandates')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('work_pattern_versions', function (Blueprint $table): void {
            $table->foreign(['work_pattern_id'], 'fk_work_pattern_versions_work_pattern_id')->references(['id'])->on('work_patterns')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('work_pattern_versions', function (Blueprint $table): void {
            $table->foreign(['decision_id'], 'fk_work_pattern_versions_decision_id')->references(['id'])->on('business_decisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('work_pattern_versions', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_work_pattern_versions_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_attendance_policies', function (Blueprint $table): void {
            $table->foreign(['decision_id'], 'fk_employee_attendance_policies_decision_id')->references(['id'])->on('business_decisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_attendance_policies', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_employee_attendance_policies_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_work_assignments', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_employee_work_assignments_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_work_assignments', function (Blueprint $table): void {
            $table->foreign(['work_pattern_id'], 'fk_employee_work_assignments_work_pattern_id')->references(['id'])->on('work_patterns')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_work_assignments', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_employee_work_assignments_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_employee_work_days_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->foreign(['work_pattern_version_id'], 'fk_employee_work_days_work_pattern_version_id')->references(['id'])->on('work_pattern_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->foreign(['policy_id'], 'fk_employee_work_days_policy_id')->references(['id'])->on('employee_attendance_policies')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->foreign(['calendar_event_id'], 'fk_employee_work_days_calendar_event_id')->references(['id'])->on('academic_calendar_events')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_attendances', function (Blueprint $table): void {
            $table->foreign(['work_day_id'], 'fk_employee_attendances_work_day_id')->references(['id'])->on('employee_work_days')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_attendances', function (Blueprint $table): void {
            $table->foreign(['attendance_status_id'], 'fk_employee_attendances_attendance_status_id')->references(['id'])->on('attendance_statuses')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_attendances', function (Blueprint $table): void {
            $table->foreign(['evidence_file_id'], 'fk_employee_attendances_evidence_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_attendances', function (Blueprint $table): void {
            $table->foreign(['recorded_by'], 'fk_employee_attendances_recorded_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('attendance_policies', function (Blueprint $table): void {
            $table->foreign(['semester_id'], 'fk_attendance_policies_semester_id')->references(['id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('attendance_policies', function (Blueprint $table): void {
            $table->foreign(['decision_id'], 'fk_attendance_policies_decision_id')->references(['id'])->on('business_decisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('attendance_policies', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_attendance_policies_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->foreign(['student_id'], 'fk_student_daily_attendances_student_id')->references(['id'])->on('students')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->foreign(['class_period_id'], 'fk_student_daily_attendances_class_period_id')->references(['id'])->on('class_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->foreign(['attendance_status_id'], 'fk_student_daily_attendances_attendance_status_id')->references(['id'])->on('attendance_statuses')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->foreign(['policy_id'], 'fk_student_daily_attendances_policy_id')->references(['id'])->on('attendance_policies')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->foreign(['recorded_by'], 'fk_student_daily_attendances_recorded_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->foreign(['student_id'], 'fk_student_meeting_attendances_student_id')->references(['id'])->on('students')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->foreign(['meeting_id'], 'fk_student_meeting_attendances_meeting_id')->references(['id'])->on('meetings')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->foreign(['attendance_status_id'], 'fk_student_meeting_attendances_attendance_status_id')->references(['id'])->on('attendance_statuses')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->foreign(['policy_id'], 'fk_student_meeting_attendances_policy_id')->references(['id'])->on('attendance_policies')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->foreign(['recorded_by'], 'fk_student_meeting_attendances_recorded_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->foreign(['employee_attendance_id'], 'fk_attendance_corrections_employee_attendance_id')->references(['id'])->on('employee_attendances')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->foreign(['student_daily_attendance_id'], 'fk_attendance_corrections_student_daily_attendance_id')->references(['id'])->on('student_daily_attendances')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->foreign(['student_meeting_attendance_id'], 'fk_attendance_corrections_student_meeting_attendance_id')->references(['id'])->on('student_meeting_attendances')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->foreign(['requested_by'], 'fk_attendance_corrections_requested_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_attendance_corrections_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_policy_versions', function (Blueprint $table): void {
            $table->foreign(['decision_id'], 'fk_honor_policy_versions_decision_id')->references(['id'])->on('business_decisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_policy_versions', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_honor_policy_versions_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_policy_versions', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_honor_policy_versions_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_rate_scopes', function (Blueprint $table): void {
            $table->foreign(['component_id'], 'fk_honor_rate_scopes_component_id')->references(['id'])->on('honor_components')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_rate_scopes', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_honor_rate_scopes_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_rate_scopes', function (Blueprint $table): void {
            $table->foreign(['employee_category_id'], 'fk_honor_rate_scopes_employee_category_id')->references(['id'])->on('employee_categories')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_rate_scopes', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_honor_rate_scopes_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_rates', function (Blueprint $table): void {
            $table->foreign(['rate_scope_id'], 'fk_honor_rates_rate_scope_id')->references(['id'])->on('honor_rate_scopes')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_rates', function (Blueprint $table): void {
            $table->foreign(['policy_version_id'], 'fk_honor_rates_policy_version_id')->references(['id'])->on('honor_policy_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_rates', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_honor_rates_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_rates', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_honor_rates_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_employee_additional_duties_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->foreign(['component_id'], 'fk_employee_additional_duties_component_id')->references(['id'])->on('honor_components')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->foreign(['position_assignment_id'], 'fk_employee_additional_duties_position_assignment_id')->references(['id'])->on('employee_positions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->foreign(['homeroom_assignment_id'], 'fk_employee_additional_duties_homeroom_assignment_id')->references(['id'])->on('homeroom_assignments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->foreign(['appointment_file_id'], 'fk_employee_additional_duties_appointment_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_employee_additional_duties_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_employee_additional_duties_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_periods', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_honor_periods_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_honor_deductions_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->foreign(['honor_period_id'], 'fk_honor_deductions_honor_period_id')->references(['id'])->on('honor_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->foreign(['component_id'], 'fk_honor_deductions_component_id')->references(['id'])->on('honor_components')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->foreign(['requested_by'], 'fk_honor_deductions_requested_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_honor_deductions_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_payrolls_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->foreign(['honor_period_id'], 'fk_payrolls_honor_period_id')->references(['id'])->on('honor_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_payrolls_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->foreign(['payroll_id'], 'fk_payroll_versions_payroll_id')->references(['id'])->on('payrolls')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->foreign(['calculated_by'], 'fk_payroll_versions_calculated_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->foreign(['reviewed_by'], 'fk_payroll_versions_reviewed_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_payroll_versions_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_lines', function (Blueprint $table): void {
            $table->foreign(['payroll_version_id'], 'fk_payroll_lines_payroll_version_id')->references(['id'])->on('payroll_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_lines', function (Blueprint $table): void {
            $table->foreign(['component_id'], 'fk_payroll_lines_component_id')->references(['id'])->on('honor_components')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['payroll_version_id'], 'fk_payroll_line_sources_payroll_version_id')->references(['id'])->on('payroll_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['meeting_id'], 'fk_payroll_line_sources_meeting_id')->references(['id'])->on('meetings')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['realization_revision_id'], 'fk_payroll_line_sources_realization_revision_id')->references(['id'])->on('realization_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['verification_id'], 'fk_payroll_line_sources_verification_id')->references(['id'])->on('realization_verifications')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['additional_duty_id'], 'fk_payroll_line_sources_additional_duty_id')->references(['id'])->on('employee_additional_duties')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['deduction_id'], 'fk_payroll_line_sources_deduction_id')->references(['id'])->on('honor_deductions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['adjustment_id'], 'fk_payroll_line_sources_adjustment_id')->references(['id'])->on('payroll_adjustments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['rate_id'], 'fk_payroll_line_sources_rate_id')->references(['id'])->on('honor_rates')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['policy_version_id'], 'fk_payroll_line_sources_policy_version_id')->references(['id'])->on('honor_policy_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['scheduled_employee_id'], 'fk_payroll_line_sources_scheduled_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['actual_employee_id'], 'fk_payroll_line_sources_actual_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['beneficiary_employee_id'], 'fk_payroll_line_sources_beneficiary_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->foreign(['employee_category_id'], 'fk_payroll_line_sources_employee_category_id')->references(['id'])->on('employee_categories')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->foreign(['meeting_id'], 'fk_payroll_source_claims_meeting_id')->references(['id'])->on('meetings')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->foreign(['payroll_id'], 'fk_payroll_source_claims_payroll_id')->references(['id'])->on('payrolls')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->foreign(['beneficiary_employee_id'], 'fk_payroll_source_claims_beneficiary_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->foreign(['claimed_by'], 'fk_payroll_source_claims_claimed_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_actions', function (Blueprint $table): void {
            $table->foreign(['payroll_version_id'], 'fk_payroll_actions_payroll_version_id')->references(['id'])->on('payroll_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_actions', function (Blueprint $table): void {
            $table->foreign(['actor_user_id'], 'fk_payroll_actions_actor_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_actions', function (Blueprint $table): void {
            $table->foreign(['mandate_id'], 'fk_payroll_actions_mandate_id')->references(['id'])->on('authorization_mandates')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->foreign(['original_line_source_id'], 'fk_payroll_adjustment_batches_original_line_source_id')->references(['id'])->on('payroll_line_sources')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->foreign(['from_employee_id'], 'fk_payroll_adjustment_batches_from_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->foreign(['to_employee_id'], 'fk_payroll_adjustment_batches_to_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->foreign(['target_honor_period_id'], 'fk_payroll_adjustment_batches_target_honor_period_id')->references(['id'])->on('honor_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->foreign(['requested_by'], 'fk_payroll_adjustment_batches_requested_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->foreign(['reviewed_by'], 'fk_payroll_adjustment_batches_reviewed_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_payroll_adjustment_batches_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->foreign(['applied_by'], 'fk_payroll_adjustment_batches_applied_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->foreign(['original_payroll_version_id'], 'fk_payroll_adjustments_original_payroll_version_id')->references(['id'])->on('payroll_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->foreign(['target_honor_period_id'], 'fk_payroll_adjustments_target_honor_period_id')->references(['id'])->on('honor_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_payroll_adjustments_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->foreign(['correction_realization_revision_id'], 'fk_payroll_adjustments_correction_realization_revision_id')->references(['id'])->on('realization_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->foreign(['requested_by'], 'fk_payroll_adjustments_requested_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_payroll_adjustments_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('subject_topics', function (Blueprint $table): void {
            $table->foreign(['subject_id'], 'fk_subject_topics_subject_id')->references(['id'])->on('subjects')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->foreign(['owner_employee_id'], 'fk_questions_owner_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->foreign(['subject_id'], 'fk_questions_subject_id')->references(['id'])->on('subjects')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_questions_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_versions', function (Blueprint $table): void {
            $table->foreign(['question_id'], 'fk_question_versions_question_id')->references(['id'])->on('questions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_versions', function (Blueprint $table): void {
            $table->foreign(['question_type_id'], 'fk_question_versions_question_type_id')->references(['id'])->on('question_types')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_versions', function (Blueprint $table): void {
            $table->foreign(['topic_id'], 'fk_question_versions_topic_id')->references(['id'])->on('subject_topics')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_versions', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_question_versions_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_options', function (Blueprint $table): void {
            $table->foreign(['question_version_id'], 'fk_question_options_question_version_id')->references(['id'])->on('question_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_keys', function (Blueprint $table): void {
            $table->foreign(['question_version_id'], 'fk_question_keys_question_version_id')->references(['id'])->on('question_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_keys', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_question_keys_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_files', function (Blueprint $table): void {
            $table->foreign(['question_version_id'], 'fk_question_files_question_version_id')->references(['id'])->on('question_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_files', function (Blueprint $table): void {
            $table->foreign(['file_id'], 'fk_question_files_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_files', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_question_files_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_collaborators', function (Blueprint $table): void {
            $table->foreign(['question_id'], 'fk_question_collaborators_question_id')->references(['id'])->on('questions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_collaborators', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_question_collaborators_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_collaborators', function (Blueprint $table): void {
            $table->foreign(['granted_by'], 'fk_question_collaborators_granted_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->foreign(['subject_id'], 'fk_exam_packages_subject_id')->references(['id'])->on('subjects')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->foreign(['semester_id'], 'fk_exam_packages_semester_id')->references(['id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->foreign(['owner_employee_id'], 'fk_exam_packages_owner_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->foreign(['finalized_by'], 'fk_exam_packages_finalized_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->foreign(['supersedes_package_id'], 'fk_exam_packages_supersedes_package_id')->references(['id'])->on('exam_packages')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_exam_packages_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_package_items', function (Blueprint $table): void {
            $table->foreign(['exam_package_id'], 'fk_exam_package_items_exam_package_id')->references(['id'])->on('exam_packages')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_package_items', function (Blueprint $table): void {
            $table->foreign(['question_version_id'], 'fk_exam_package_items_question_version_id')->references(['id'])->on('question_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_package_options', function (Blueprint $table): void {
            $table->foreign(['package_item_id'], 'fk_exam_package_options_package_item_id')->references(['id'])->on('exam_package_items')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_package_options', function (Blueprint $table): void {
            $table->foreign(['source_option_id'], 'fk_exam_package_options_source_option_id')->references(['id'])->on('question_options')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_package_keys', function (Blueprint $table): void {
            $table->foreign(['package_item_id'], 'fk_exam_package_keys_package_item_id')->references(['id'])->on('exam_package_items')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_package_files', function (Blueprint $table): void {
            $table->foreign(['exam_package_id'], 'fk_exam_package_files_exam_package_id')->references(['id'])->on('exam_packages')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_package_files', function (Blueprint $table): void {
            $table->foreign(['file_id'], 'fk_exam_package_files_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('exam_package_files', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_exam_package_files_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_categories', function (Blueprint $table): void {
            $table->foreign(['retention_policy_id'], 'fk_bk_categories_retention_policy_id')->references(['id'])->on('retention_policies')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_category_grants', function (Blueprint $table): void {
            $table->foreign(['user_id'], 'fk_bk_category_grants_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_category_grants', function (Blueprint $table): void {
            $table->foreign(['category_id'], 'fk_bk_category_grants_category_id')->references(['id'])->on('bk_categories')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_category_grants', function (Blueprint $table): void {
            $table->foreign(['granted_by'], 'fk_bk_category_grants_granted_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_cases', function (Blueprint $table): void {
            $table->foreign(['student_id'], 'fk_bk_cases_student_id')->references(['id'])->on('students')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_cases', function (Blueprint $table): void {
            $table->foreign(['category_id'], 'fk_bk_cases_category_id')->references(['id'])->on('bk_categories')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_cases', function (Blueprint $table): void {
            $table->foreign(['assigned_employee_id'], 'fk_bk_cases_assigned_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_cases', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_bk_cases_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_notes', function (Blueprint $table): void {
            $table->foreign(['case_id'], 'fk_bk_notes_case_id')->references(['id'])->on('bk_cases')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_notes', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_bk_notes_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_followups', function (Blueprint $table): void {
            $table->foreign(['case_id'], 'fk_bk_followups_case_id')->references(['id'])->on('bk_cases')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_followups', function (Blueprint $table): void {
            $table->foreign(['assigned_employee_id'], 'fk_bk_followups_assigned_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_followups', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_bk_followups_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_summaries', function (Blueprint $table): void {
            $table->foreign(['case_id'], 'fk_bk_summaries_case_id')->references(['id'])->on('bk_cases')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_summaries', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_bk_summaries_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_summaries', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_bk_summaries_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->foreign(['summary_id'], 'fk_bk_summary_recipients_summary_id')->references(['id'])->on('bk_summaries')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->foreign(['recipient_user_id'], 'fk_bk_summary_recipients_recipient_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->foreign(['student_guardian_id'], 'fk_bk_summary_recipients_student_guardian_id')->references(['id'])->on('student_guardians')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->foreign(['homeroom_assignment_id'], 'fk_bk_summary_recipients_homeroom_assignment_id')->references(['id'])->on('homeroom_assignments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->foreign(['shared_by'], 'fk_bk_summary_recipients_shared_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->foreign(['case_id'], 'fk_bk_files_case_id')->references(['id'])->on('bk_cases')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->foreign(['file_id'], 'fk_bk_files_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_bk_files_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('document_categories', function (Blueprint $table): void {
            $table->foreign(['retention_policy_id'], 'fk_document_categories_retention_policy_id')->references(['id'])->on('retention_policies')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('document_category_grants', function (Blueprint $table): void {
            $table->foreign(['user_id'], 'fk_document_category_grants_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('document_category_grants', function (Blueprint $table): void {
            $table->foreign(['category_id'], 'fk_document_category_grants_category_id')->references(['id'])->on('document_categories')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('document_category_grants', function (Blueprint $table): void {
            $table->foreign(['granted_by'], 'fk_document_category_grants_granted_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->foreign(['decision_id'], 'fk_letter_number_sequences_decision_id')->references(['id'])->on('business_decisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_letter_number_sequences_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->foreign(['category_id'], 'fk_letters_category_id')->references(['id'])->on('document_categories')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->foreign(['number_sequence_id'], 'fk_letters_number_sequence_id')->references(['id'])->on('letter_number_sequences')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->foreign(['issued_by'], 'fk_letters_issued_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_letters_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letter_agendas', function (Blueprint $table): void {
            $table->foreign(['letter_id'], 'fk_letter_agendas_letter_id')->references(['id'])->on('letters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letter_agendas', function (Blueprint $table): void {
            $table->foreign(['agenda_sequence_id'], 'fk_letter_agendas_agenda_sequence_id')->references(['id'])->on('letter_number_sequences')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letter_agendas', function (Blueprint $table): void {
            $table->foreign(['recorded_by'], 'fk_letter_agendas_recorded_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreign(['category_id'], 'fk_documents_category_id')->references(['id'])->on('document_categories')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreign(['letter_id'], 'fk_documents_letter_id')->references(['id'])->on('letters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreign(['retention_policy_id'], 'fk_documents_retention_policy_id')->references(['id'])->on('retention_policies')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_documents_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('document_files', function (Blueprint $table): void {
            $table->foreign(['document_id'], 'fk_document_files_document_id')->references(['id'])->on('documents')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('document_files', function (Blueprint $table): void {
            $table->foreign(['file_id'], 'fk_document_files_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('document_files', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_document_files_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letter_dispositions', function (Blueprint $table): void {
            $table->foreign(['letter_id'], 'fk_letter_dispositions_letter_id')->references(['id'])->on('letters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letter_dispositions', function (Blueprint $table): void {
            $table->foreign(['assigned_to_user_id'], 'fk_letter_dispositions_assigned_to_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letter_dispositions', function (Blueprint $table): void {
            $table->foreign(['assigned_by'], 'fk_letter_dispositions_assigned_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('disposition_actions', function (Blueprint $table): void {
            $table->foreign(['disposition_id'], 'fk_disposition_actions_disposition_id')->references(['id'])->on('letter_dispositions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('disposition_actions', function (Blueprint $table): void {
            $table->foreign(['actor_user_id'], 'fk_disposition_actions_actor_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->foreign(['category_id'], 'fk_contents_category_id')->references(['id'])->on('content_categories')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_contents_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('content_revisions', function (Blueprint $table): void {
            $table->foreign(['content_id'], 'fk_content_revisions_content_id')->references(['id'])->on('contents')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('content_revisions', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_content_revisions_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('content_files', function (Blueprint $table): void {
            $table->foreign(['content_revision_id'], 'fk_content_files_content_revision_id')->references(['id'])->on('content_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('content_files', function (Blueprint $table): void {
            $table->foreign(['file_id'], 'fk_content_files_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('content_files', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_content_files_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_actions', function (Blueprint $table): void {
            $table->foreign(['content_id'], 'fk_publication_actions_content_id')->references(['id'])->on('contents')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_actions', function (Blueprint $table): void {
            $table->foreign(['actor_user_id'], 'fk_publication_actions_actor_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_actions', function (Blueprint $table): void {
            $table->foreign(['mandate_id'], 'fk_publication_actions_mandate_id')->references(['id'])->on('authorization_mandates')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->foreign(['content_revision_id'], 'fk_publication_assets_content_revision_id')->references(['id'])->on('content_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->foreign(['source_file_id'], 'fk_publication_assets_source_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->foreign(['public_file_id'], 'fk_publication_assets_public_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->foreign(['source_document_id'], 'fk_publication_assets_source_document_id')->references(['id'])->on('documents')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_publication_assets_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('public_staff_profiles', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_public_staff_profiles_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('public_staff_profiles', function (Blueprint $table): void {
            $table->foreign(['content_revision_id'], 'fk_public_staff_profiles_content_revision_id')->references(['id'])->on('content_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('public_staff_profiles', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_public_staff_profiles_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('portal_announcements', function (Blueprint $table): void {
            $table->foreign(['published_by'], 'fk_portal_announcements_published_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('portal_announcements', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_portal_announcements_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('portal_announcement_targets', function (Blueprint $table): void {
            $table->foreign(['announcement_id'], 'fk_portal_announcement_targets_announcement_id')->references(['id'])->on('portal_announcements')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('portal_announcement_targets', function (Blueprint $table): void {
            $table->foreign(['class_period_id'], 'fk_portal_announcement_targets_class_period_id')->references(['id'])->on('class_periods')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('portal_announcement_targets', function (Blueprint $table): void {
            $table->foreign(['student_id'], 'fk_portal_announcement_targets_student_id')->references(['id'])->on('students')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('portal_announcement_files', function (Blueprint $table): void {
            $table->foreign(['announcement_id'], 'fk_portal_announcement_files_announcement_id')->references(['id'])->on('portal_announcements')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('portal_announcement_files', function (Blueprint $table): void {
            $table->foreign(['file_id'], 'fk_portal_announcement_files_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('portal_announcement_files', function (Blueprint $table): void {
            $table->foreign(['created_by'], 'fk_portal_announcement_files_created_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('report_jobs', function (Blueprint $table): void {
            $table->foreign(['requested_by'], 'fk_report_jobs_requested_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('report_jobs', function (Blueprint $table): void {
            $table->foreign(['output_file_id'], 'fk_report_jobs_output_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('report_dataset_rows', function (Blueprint $table): void {
            $table->foreign(['report_job_id'], 'fk_report_dataset_rows_report_job_id')->references(['id'])->on('report_jobs')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->foreign(['requested_by'], 'fk_import_batches_requested_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->foreign(['source_file_id'], 'fk_import_batches_source_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->foreign(['confirmed_by'], 'fk_import_batches_confirmed_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->foreign(['error_report_file_id'], 'fk_import_batches_error_report_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->foreign(['person_id'], 'fk_source_records_person_id')->references(['id'])->on('persons')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->foreign(['student_id'], 'fk_source_records_student_id')->references(['id'])->on('students')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->foreign(['guardian_id'], 'fk_source_records_guardian_id')->references(['id'])->on('guardians')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->foreign(['employee_id'], 'fk_source_records_employee_id')->references(['id'])->on('employees')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->foreign(['subject_id'], 'fk_source_records_subject_id')->references(['id'])->on('subjects')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->foreign(['class_id'], 'fk_source_records_class_id')->references(['id'])->on('classes')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->foreign(['last_batch_id'], 'fk_source_records_last_batch_id')->references(['id'])->on('import_batches')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('import_rows', function (Blueprint $table): void {
            $table->foreign(['import_batch_id'], 'fk_import_rows_import_batch_id')->references(['id'])->on('import_batches')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('import_rows', function (Blueprint $table): void {
            $table->foreign(['source_record_id'], 'fk_import_rows_source_record_id')->references(['id'])->on('source_records')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('idempotency_requests', function (Blueprint $table): void {
            $table->foreign(['actor_user_id'], 'fk_idempotency_requests_actor_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('retention_policies', function (Blueprint $table): void {
            $table->foreign(['decision_id'], 'fk_retention_policies_decision_id')->references(['id'])->on('business_decisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('retention_policies', function (Blueprint $table): void {
            $table->foreign(['approved_by'], 'fk_retention_policies_approved_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->foreign(['actor_user_id'], 'fk_audit_logs_actor_user_id')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('backup_runs', function (Blueprint $table): void {
            $table->foreign(['retention_policy_id'], 'fk_backup_runs_retention_policy_id')->references(['id'])->on('retention_policies')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('backup_runs', function (Blueprint $table): void {
            $table->foreign(['initiated_by'], 'fk_backup_runs_initiated_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('restore_runs', function (Blueprint $table): void {
            $table->foreign(['backup_run_id'], 'fk_restore_runs_backup_run_id')->references(['id'])->on('backup_runs')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('restore_runs', function (Blueprint $table): void {
            $table->foreign(['evidence_file_id'], 'fk_restore_runs_evidence_file_id')->references(['id'])->on('files')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('restore_runs', function (Blueprint $table): void {
            $table->foreign(['performed_by'], 'fk_restore_runs_performed_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('system_events', function (Blueprint $table): void {
            $table->foreign(['acknowledged_by'], 'fk_system_events_acknowledged_by')->references(['id'])->on('users')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('academic_contexts', function (Blueprint $table): void {
            $table->index(['active_academic_year_id', 'active_semester_id'], 'ix_academic_contexts_active_academic_year_id_active_semester_id');
            $table->foreign(['active_academic_year_id', 'active_semester_id'], 'fk_academic_contexts_active_academic_year_id_active_semester_id')->references(['academic_year_id', 'id'])->on('semesters')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('teaching_realizations', function (Blueprint $table): void {
            $table->foreign(['id', 'current_revision_id'], 'fk_teaching_realizations_id_current_revision_id')->references(['realization_id', 'id'])->on('realization_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->foreign(['id', 'active_version_id'], 'fk_payrolls_id_active_version_id')->references(['payroll_id', 'id'])->on('payroll_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->foreign(['id', 'current_version_id'], 'fk_questions_id_current_version_id')->references(['question_id', 'id'])->on('question_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->foreign(['id', 'current_revision_id'], 'fk_contents_id_current_revision_id')->references(['content_id', 'id'])->on('content_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->foreign(['id', 'published_revision_id'], 'fk_contents_id_published_revision_id')->references(['content_id', 'id'])->on('content_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->index(['enrollment_id', 'student_id', 'class_period_id'], 'ix_student_daily_attendances_enrollment_id_student__a7e101b660dc');
            $table->foreign(['enrollment_id', 'student_id', 'class_period_id'], 'fk_student_daily_attendances_enrollment_id_student__8a8dd63bc62d')->references(['id', 'student_id', 'class_period_id'])->on('student_enrollments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->index(['enrollment_id', 'student_id'], 'ix_student_meeting_attendances_enrollment_id_student_id');
            $table->foreign(['enrollment_id', 'student_id'], 'fk_student_meeting_attendances_enrollment_id_student_id')->references(['id', 'student_id'])->on('student_enrollments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->index(['from_enrollment_id', 'student_id'], 'ix_student_transitions_from_enrollment_id_student_id');
            $table->foreign(['from_enrollment_id', 'student_id'], 'fk_student_transitions_from_enrollment_id_student_id')->references(['id', 'student_id'])->on('student_enrollments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->index(['to_enrollment_id', 'student_id'], 'ix_student_transitions_to_enrollment_id_student_id');
            $table->foreign(['to_enrollment_id', 'student_id'], 'fk_student_transitions_to_enrollment_id_student_id')->references(['id', 'student_id'])->on('student_enrollments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->index(['schedule_entry_id', 'schedule_entry_version_id'], 'ix_meetings_schedule_entry_id_schedule_entry_version_id');
            $table->foreign(['schedule_entry_id', 'schedule_entry_version_id'], 'fk_meetings_schedule_entry_id_schedule_entry_version_id')->references(['schedule_entry_id', 'id'])->on('schedule_entry_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->index(['work_assignment_id', 'employee_id'], 'ix_employee_work_days_work_assignment_id_employee_id');
            $table->foreign(['work_assignment_id', 'employee_id'], 'fk_employee_work_days_work_assignment_id_employee_id')->references(['id', 'employee_id'])->on('employee_work_assignments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->index(['payroll_id', 'previous_version_id'], 'ix_payroll_versions_payroll_id_previous_version_id');
            $table->foreign(['payroll_id', 'previous_version_id'], 'fk_payroll_versions_payroll_id_previous_version_id')->references(['payroll_id', 'id'])->on('payroll_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->index(['payroll_version_id', 'payroll_line_id'], 'ix_payroll_line_sources_payroll_version_id_payroll_line_id');
            $table->foreign(['payroll_version_id', 'payroll_line_id'], 'fk_payroll_line_sources_payroll_version_id_payroll_line_id')->references(['payroll_version_id', 'id'])->on('payroll_lines')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->index(['category_assignment_id', 'beneficiary_employee_id', 'employee_category_id'], 'ix_payroll_line_sources_category_assignment_id_bene_ccef166c6e36');
            $table->foreign(['category_assignment_id', 'beneficiary_employee_id', 'employee_category_id'], 'fk_payroll_line_sources_category_assignment_id_bene_e850b4d81c2a')->references(['id', 'employee_id', 'category_id'])->on('employee_category_assignments')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->index(['applied_payroll_id', 'employee_id', 'honor_period_id'], 'ix_honor_deductions_applied_payroll_id_employee_id__0092648fbe5f');
            $table->foreign(['applied_payroll_id', 'employee_id', 'honor_period_id'], 'fk_honor_deductions_applied_payroll_id_employee_id__5a8e183c126c')->references(['id', 'employee_id', 'honor_period_id'])->on('payrolls')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->index(['payroll_id', 'payroll_version_id'], 'ix_payroll_source_claims_payroll_id_payroll_version_id');
            $table->foreign(['payroll_id', 'payroll_version_id'], 'fk_payroll_source_claims_payroll_id_payroll_version_id')->references(['payroll_id', 'id'])->on('payroll_versions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->index(['payroll_version_id', 'payroll_line_source_id'], 'ix_payroll_source_claims_payroll_version_id_payroll_10ec661e7eed');
            $table->foreign(['payroll_version_id', 'payroll_line_source_id'], 'fk_payroll_source_claims_payroll_version_id_payroll_3746d23f430e')->references(['payroll_version_id', 'id'])->on('payroll_line_sources')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->index(['meeting_id', 'payroll_line_source_id'], 'ix_payroll_source_claims_meeting_id_payroll_line_source_id');
            $table->foreign(['meeting_id', 'payroll_line_source_id'], 'fk_payroll_source_claims_meeting_id_payroll_line_source_id')->references(['meeting_id', 'id'])->on('payroll_line_sources')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->index(['beneficiary_employee_id', 'payroll_line_source_id'], 'ix_payroll_source_claims_beneficiary_employee_id_pa_66301378da9d');
            $table->foreign(['beneficiary_employee_id', 'payroll_line_source_id'], 'fk_payroll_source_claims_beneficiary_employee_id_pa_832e2cd0c55d')->references(['beneficiary_employee_id', 'id'])->on('payroll_line_sources')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->index(['payroll_id', 'beneficiary_employee_id'], 'ix_payroll_source_claims_payroll_id_beneficiary_employee_id');
            $table->foreign(['payroll_id', 'beneficiary_employee_id'], 'fk_payroll_source_claims_payroll_id_beneficiary_employee_id')->references(['id', 'employee_id'])->on('payrolls')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->index(['original_payroll_version_id', 'original_line_source_id'], 'ix_payroll_adjustments_original_payroll_version_id__7218198efda9');
            $table->foreign(['original_payroll_version_id', 'original_line_source_id'], 'fk_payroll_adjustments_original_payroll_version_id__19268d521dff')->references(['payroll_version_id', 'id'])->on('payroll_line_sources')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->index(['employee_id', 'applied_line_source_id'], 'ix_payroll_adjustments_employee_id_applied_line_source_id');
            $table->foreign(['employee_id', 'applied_line_source_id'], 'fk_payroll_adjustments_employee_id_applied_line_source_id')->references(['beneficiary_employee_id', 'id'])->on('payroll_line_sources')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->index(['batch_id', 'target_honor_period_id'], 'ix_payroll_adjustments_batch_id_target_honor_period_id');
            $table->foreign(['batch_id', 'target_honor_period_id'], 'fk_payroll_adjustments_batch_id_target_honor_period_id')->references(['id', 'target_honor_period_id'])->on('payroll_adjustment_batches')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('question_files', function (Blueprint $table): void {
            $table->index(['question_version_id', 'option_id'], 'ix_question_files_question_version_id_option_id');
            $table->foreign(['question_version_id', 'option_id'], 'fk_question_files_question_version_id_option_id')->references(['question_version_id', 'id'])->on('question_options')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_notes', function (Blueprint $table): void {
            $table->index(['case_id', 'previous_note_id'], 'ix_bk_notes_case_id_previous_note_id');
            $table->foreign(['case_id', 'previous_note_id'], 'fk_bk_notes_case_id_previous_note_id')->references(['case_id', 'id'])->on('bk_notes')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_summaries', function (Blueprint $table): void {
            $table->index(['case_id', 'previous_summary_id'], 'ix_bk_summaries_case_id_previous_summary_id');
            $table->foreign(['case_id', 'previous_summary_id'], 'fk_bk_summaries_case_id_previous_summary_id')->references(['case_id', 'id'])->on('bk_summaries')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_followups', function (Blueprint $table): void {
            $table->index(['case_id', 'note_id'], 'ix_bk_followups_case_id_note_id');
            $table->foreign(['case_id', 'note_id'], 'fk_bk_followups_case_id_note_id')->references(['case_id', 'id'])->on('bk_notes')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->index(['case_id', 'note_id'], 'ix_bk_files_case_id_note_id');
            $table->foreign(['case_id', 'note_id'], 'fk_bk_files_case_id_note_id')->references(['case_id', 'id'])->on('bk_notes')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->index(['case_id', 'followup_id'], 'ix_bk_files_case_id_followup_id');
            $table->foreign(['case_id', 'followup_id'], 'fk_bk_files_case_id_followup_id')->references(['case_id', 'id'])->on('bk_followups')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->index(['case_id', 'summary_id'], 'ix_bk_files_case_id_summary_id');
            $table->foreign(['case_id', 'summary_id'], 'fk_bk_files_case_id_summary_id')->references(['case_id', 'id'])->on('bk_summaries')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('letter_dispositions', function (Blueprint $table): void {
            $table->index(['letter_id', 'parent_disposition_id'], 'ix_letter_dispositions_letter_id_parent_disposition_id');
            $table->foreign(['letter_id', 'parent_disposition_id'], 'fk_letter_dispositions_letter_id_parent_disposition_id')->references(['letter_id', 'id'])->on('letter_dispositions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_actions', function (Blueprint $table): void {
            $table->index(['content_id', 'content_revision_id'], 'ix_publication_actions_content_id_content_revision_id');
            $table->foreign(['content_id', 'content_revision_id'], 'fk_publication_actions_content_id_content_revision_id')->references(['content_id', 'id'])->on('content_revisions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->index(['content_revision_id', 'publication_action_id'], 'ix_publication_assets_content_revision_id_publication_action_id');
            $table->foreign(['content_revision_id', 'publication_action_id'], 'fk_publication_assets_content_revision_id_publication_action_id')->references(['content_revision_id', 'id'])->on('publication_actions')->restrictOnDelete()->restrictOnUpdate();
        });

        Schema::table('public_staff_profiles', function (Blueprint $table): void {
            $table->index(['content_revision_id', 'photo_public_file_id'], 'ix_public_staff_profiles_content_revision_id_photo__4bcfadc3eb9f');
            $table->foreign(['content_revision_id', 'photo_public_file_id'], 'fk_public_staff_profiles_content_revision_id_photo__effa36ac1646')->references(['content_revision_id', 'public_file_id'])->on('publication_assets')->restrictOnDelete()->restrictOnUpdate();
        });

        DB::statement('ALTER TABLE `institutions` ADD CONSTRAINT `ck_institutions_singleton` CHECK (`singleton_key` = 1)');

        DB::statement('ALTER TABLE `persons` ADD CONSTRAINT `ck_persons_sex` CHECK (`sex` IN (\'L\', \'P\'))');

        DB::statement('ALTER TABLE `persons` ADD CONSTRAINT `ck_persons_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `users` ADD CONSTRAINT `ck_users_status` CHECK (`status` IN (\'active\', \'disabled\'))');

        DB::statement('ALTER TABLE `users` ADD CONSTRAINT `ck_users_must_change_password` CHECK (`must_change_password` IN (0, 1))');

        DB::statement('ALTER TABLE `roles` ADD CONSTRAINT `ck_roles_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `permissions` ADD CONSTRAINT `ck_permissions_is_sensitive` CHECK (`is_sensitive` IN (0, 1))');

        DB::statement('ALTER TABLE `user_roles` ADD CONSTRAINT `ck_user_roles_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `authorization_mandates` ADD CONSTRAINT `ck_authorization_mandates_scope_type` CHECK (`scope_type` IN (\'institution\', \'semester\', \'class\', \'employee\'))');

        DB::statement('ALTER TABLE `authorization_mandates` ADD CONSTRAINT `ck_authorization_mandates_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `authorization_mandates` ADD CONSTRAINT `ck_authorization_mandates_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `authorization_mandates` ADD CONSTRAINT `ck_authorization_mandates_scope` CHECK ((`scope_type` = \'institution\' AND `semester_id` IS NULL AND `class_period_id` IS NULL AND `employee_id` IS NULL) OR (`scope_type` = \'semester\' AND `semester_id` IS NOT NULL AND `class_period_id` IS NULL AND `employee_id` IS NULL) OR (`scope_type` = \'class\' AND `class_period_id` IS NOT NULL AND `semester_id` IS NULL AND `employee_id` IS NULL) OR (`scope_type` = \'employee\' AND `employee_id` IS NOT NULL AND `semester_id` IS NULL AND `class_period_id` IS NULL))');

        DB::statement('ALTER TABLE `business_decisions` ADD CONSTRAINT `ck_business_decisions_status` CHECK (`status` IN (\'baseline\', \'proposed\', \'approved\', \'superseded\'))');

        DB::statement('ALTER TABLE `business_decisions` ADD CONSTRAINT `ck_business_decisions_version_positive` CHECK (`version` > 0)');

        DB::statement('ALTER TABLE `business_decisions` ADD CONSTRAINT `ck_business_decisions_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `files` ADD CONSTRAINT `ck_files_classification` CHECK (`classification` IN (\'internal\', \'bk_internal\', \'honor\', \'question\', \'key\', \'tu_restricted\', \'public_candidate\', \'public\'))');

        DB::statement('ALTER TABLE `files` ADD CONSTRAINT `ck_files_scan_status` CHECK (`scan_status` IN (\'pending\', \'clean\', \'rejected\'))');

        DB::statement('ALTER TABLE `files` ADD CONSTRAINT `ck_files_expiry` CHECK (`expires_at` IS NULL OR `expires_at` > `created_at`)');

        DB::statement('ALTER TABLE `student_statuses` ADD CONSTRAINT `ck_student_statuses_is_terminal` CHECK (`is_terminal` IN (0, 1))');

        DB::statement('ALTER TABLE `student_statuses` ADD CONSTRAINT `ck_student_statuses_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `guardians` ADD CONSTRAINT `ck_guardians_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `student_guardians` ADD CONSTRAINT `ck_student_guardians_relationship` CHECK (`relationship` IN (\'father\', \'mother\', \'legal_guardian\', \'other\'))');

        DB::statement('ALTER TABLE `student_guardians` ADD CONSTRAINT `ck_student_guardians_verification_status` CHECK (`verification_status` IN (\'pending\', \'verified\', \'rejected\', \'revoked\'))');

        DB::statement('ALTER TABLE `student_guardians` ADD CONSTRAINT `ck_student_guardians_is_primary` CHECK (`is_primary` IN (0, 1))');

        DB::statement('ALTER TABLE `student_guardians` ADD CONSTRAINT `ck_student_guardians_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `employee_categories` ADD CONSTRAINT `ck_employee_categories_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `employees` ADD CONSTRAINT `ck_employees_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `employee_category_assignments` ADD CONSTRAINT `ck_employee_category_assignments_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `employee_category_assignments` ADD CONSTRAINT `ck_employee_category_assignments_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `positions` ADD CONSTRAINT `ck_positions_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `employee_positions` ADD CONSTRAINT `ck_employee_positions_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `subjects` ADD CONSTRAINT `ck_subjects_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `homeroom_assignments` ADD CONSTRAINT `ck_homeroom_assignments_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `teaching_assignments` ADD CONSTRAINT `ck_teaching_assignments_planned_weekly_jp_nonnegative` CHECK (`planned_weekly_jp` >= 0)');

        DB::statement('ALTER TABLE `teaching_assignments` ADD CONSTRAINT `ck_teaching_assignments_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `teaching_assignments` ADD CONSTRAINT `ck_teaching_assignments_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `academic_years` ADD CONSTRAINT `ck_academic_years_status` CHECK (`status` IN (\'planned\', \'open\', \'closed\'))');

        DB::statement('ALTER TABLE `academic_years` ADD CONSTRAINT `ck_academic_years_date_range` CHECK (`end_date` > `start_date`)');

        DB::statement('ALTER TABLE `semesters` ADD CONSTRAINT `ck_semesters_status` CHECK (`status` IN (\'planned\', \'open\', \'closed\'))');

        DB::statement('ALTER TABLE `semesters` ADD CONSTRAINT `ck_semesters_date_range` CHECK (`end_date` > `start_date`)');

        DB::statement('ALTER TABLE `semesters` ADD CONSTRAINT `ck_semesters_number` CHECK (`number` IN (1, 2))');

        DB::statement('ALTER TABLE `academic_contexts` ADD CONSTRAINT `ck_academic_contexts_singleton` CHECK (`singleton_key` = 1)');

        DB::statement('ALTER TABLE `academic_contexts` ADD CONSTRAINT `ck_academic_contexts_active_pair` CHECK ((`active_academic_year_id` IS NULL) = (`active_semester_id` IS NULL))');

        DB::statement('ALTER TABLE `classes` ADD CONSTRAINT `ck_classes_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `classes` ADD CONSTRAINT `ck_classes_grade` CHECK (`grade` IN (7, 8, 9))');

        DB::statement('ALTER TABLE `class_periods` ADD CONSTRAINT `ck_class_periods_status` CHECK (`status` IN (\'planned\', \'open\', \'closed\'))');

        DB::statement('ALTER TABLE `class_periods` ADD CONSTRAINT `ck_class_periods_grade_snapshot` CHECK (`grade_snapshot` IN (7, 8, 9))');

        DB::statement('ALTER TABLE `student_enrollments` ADD CONSTRAINT `ck_student_enrollments_entry_reason` CHECK (`entry_reason` IN (\'admission\', \'promotion\', \'transfer\', \'correction\', \'graduation\', \'withdrawal\'))');

        DB::statement('ALTER TABLE `student_enrollments` ADD CONSTRAINT `ck_student_enrollments_exit_reason` CHECK (`exit_reason` IN (\'admission\', \'promotion\', \'transfer\', \'correction\', \'graduation\', \'withdrawal\'))');

        DB::statement('ALTER TABLE `student_enrollments` ADD CONSTRAINT `ck_student_enrollments_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `student_transitions` ADD CONSTRAINT `ck_student_transitions_type` CHECK (`type` IN (\'admission\', \'promotion\', \'class_transfer\', \'graduation\', \'school_transfer\', \'withdrawal\', \'correction\'))');

        DB::statement('ALTER TABLE `student_transitions` ADD CONSTRAINT `ck_student_transitions_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `academic_calendar_events` ADD CONSTRAINT `ck_academic_calendar_events_event_type` CHECK (`event_type` IN (\'holiday\', \'activity\', \'exam\', \'other\'))');

        DB::statement('ALTER TABLE `academic_calendar_events` ADD CONSTRAINT `ck_academic_calendar_events_blocks_teaching` CHECK (`blocks_teaching` IN (0, 1))');

        DB::statement('ALTER TABLE `academic_calendar_events` ADD CONSTRAINT `ck_academic_calendar_events_blocks_employee_work` CHECK (`blocks_employee_work` IN (0, 1))');

        DB::statement('ALTER TABLE `academic_calendar_events` ADD CONSTRAINT `ck_academic_calendar_events_date_range` CHECK (`end_date` > `start_date`)');

        DB::statement('ALTER TABLE `lesson_slots` ADD CONSTRAINT `ck_lesson_slots_sequence_positive` CHECK (`sequence` > 0)');

        DB::statement('ALTER TABLE `lesson_slots` ADD CONSTRAINT `ck_lesson_slots_jp_units_nonnegative` CHECK (`jp_units` >= 0)');

        DB::statement('ALTER TABLE `lesson_slots` ADD CONSTRAINT `ck_lesson_slots_is_break` CHECK (`is_break` IN (0, 1))');

        DB::statement('ALTER TABLE `lesson_slots` ADD CONSTRAINT `ck_lesson_slots_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `lesson_slots` ADD CONSTRAINT `ck_lesson_slots_weekday` CHECK (`day_of_week` BETWEEN 1 AND 7)');

        DB::statement('ALTER TABLE `lesson_slots` ADD CONSTRAINT `ck_lesson_slots_time_range` CHECK (`end_time` > `start_time`)');

        DB::statement('ALTER TABLE `lesson_slots` ADD CONSTRAINT `ck_lesson_slots_break_jp` CHECK (`is_break` = 0 OR `jp_units` = 0)');

        DB::statement('ALTER TABLE `timetable_versions` ADD CONSTRAINT `ck_timetable_versions_status` CHECK (`status` IN (\'draft\', \'published\', \'superseded\'))');

        DB::statement('ALTER TABLE `timetable_versions` ADD CONSTRAINT `ck_timetable_versions_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `timetable_versions` ADD CONSTRAINT `ck_timetable_versions_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `schedule_entry_versions` ADD CONSTRAINT `ck_schedule_entry_versions_planned_jp_nonnegative` CHECK (`planned_jp` >= 0)');

        DB::statement('ALTER TABLE `schedule_entry_versions` ADD CONSTRAINT `ck_schedule_entry_versions_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `schedule_entry_versions` ADD CONSTRAINT `ck_schedule_entry_versions_weekday` CHECK (`day_of_week` BETWEEN 1 AND 7)');

        DB::statement('ALTER TABLE `schedule_entry_versions` ADD CONSTRAINT `ck_schedule_entry_versions_time_range` CHECK (`end_time_snapshot` > `start_time_snapshot`)');

        DB::statement('ALTER TABLE `schedule_entry_versions` ADD CONSTRAINT `ck_schedule_entry_versions_planned_positive` CHECK (`planned_jp` > 0 AND `planned_minutes` > 0)');

        DB::statement('ALTER TABLE `schedule_entry_slots` ADD CONSTRAINT `ck_schedule_entry_slots_position_positive` CHECK (`position` > 0)');

        DB::statement('ALTER TABLE `schedule_entry_slots` ADD CONSTRAINT `ck_schedule_entry_slots_jp_snapshot_nonnegative` CHECK (`jp_snapshot` >= 0)');

        DB::statement('ALTER TABLE `schedule_entry_slots` ADD CONSTRAINT `ck_schedule_entry_slots_time_range` CHECK (`end_time_snapshot` > `start_time_snapshot`)');

        DB::statement('ALTER TABLE `meetings` ADD CONSTRAINT `ck_meetings_occurrence_number_positive` CHECK (`occurrence_number` > 0)');

        DB::statement('ALTER TABLE `meetings` ADD CONSTRAINT `ck_meetings_planned_jp_snapshot_nonnegative` CHECK (`planned_jp_snapshot` >= 0)');

        DB::statement('ALTER TABLE `meetings` ADD CONSTRAINT `ck_meetings_is_cancelled` CHECK (`is_cancelled` IN (0, 1))');

        DB::statement('ALTER TABLE `meetings` ADD CONSTRAINT `ck_meetings_time_range` CHECK (`end_time_snapshot` > `start_time_snapshot`)');

        DB::statement('ALTER TABLE `meetings` ADD CONSTRAINT `ck_meetings_planned_positive` CHECK (`planned_jp_snapshot` > 0 AND `planned_minutes_snapshot` > 0)');

        DB::statement('ALTER TABLE `meetings` ADD CONSTRAINT `ck_meetings_cancellation_reason` CHECK (`is_cancelled` = 0 OR (`cancellation_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`cancellation_reason`)) > 0))');

        DB::statement('ALTER TABLE `substitute_assignments` ADD CONSTRAINT `ck_substitute_assignments_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `realization_revisions` ADD CONSTRAINT `ck_realization_revisions_execution_status` CHECK (`execution_status` IN (\'not_recorded\', \'held\', \'not_held\'))');

        DB::statement('ALTER TABLE `realization_revisions` ADD CONSTRAINT `ck_realization_revisions_verification_status` CHECK (`verification_status` IN (\'not_submitted\', \'pending\', \'approved\', \'rejected\'))');

        DB::statement('ALTER TABLE `realization_revisions` ADD CONSTRAINT `ck_realization_revisions_revision_number_positive` CHECK (`revision_number` > 0)');

        DB::statement('ALTER TABLE `realization_revisions` ADD CONSTRAINT `ck_realization_revisions_actual_jp_nonnegative` CHECK (`actual_jp` >= 0)');

        DB::statement('ALTER TABLE `realization_revisions` ADD CONSTRAINT `ck_realization_revisions_nonexecution` CHECK (`execution_status` <> \'not_held\' OR (`actual_jp` = 0 AND `actual_minutes` = 0 AND `nonexecution_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`nonexecution_reason`)) > 0))');

        DB::statement('ALTER TABLE `realization_revisions` ADD CONSTRAINT `ck_realization_revisions_actual_time` CHECK (`actual_started_at` IS NULL OR `actual_ended_at` IS NULL OR `actual_ended_at` >= `actual_started_at`)');

        DB::statement('ALTER TABLE `realization_verifications` ADD CONSTRAINT `ck_realization_verifications_action` CHECK (`action` IN (\'approve\', \'reject\'))');

        DB::statement('ALTER TABLE `realization_verifications` ADD CONSTRAINT `ck_realization_verifications_reject_reason` CHECK (`action` <> \'reject\' OR (`reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`reason`)) > 0))');

        DB::statement('ALTER TABLE `attendance_statuses` ADD CONSTRAINT `ck_attendance_statuses_domain` CHECK (`domain` IN (\'employee\', \'student\'))');

        DB::statement('ALTER TABLE `attendance_statuses` ADD CONSTRAINT `ck_attendance_statuses_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `work_patterns` ADD CONSTRAINT `ck_work_patterns_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `work_pattern_versions` ADD CONSTRAINT `ck_work_pattern_versions_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `work_pattern_versions` ADD CONSTRAINT `ck_work_pattern_versions_crosses_midnight` CHECK (`crosses_midnight` IN (0, 1))');

        DB::statement('ALTER TABLE `work_pattern_versions` ADD CONSTRAINT `ck_work_pattern_versions_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `work_pattern_versions` ADD CONSTRAINT `ck_work_pattern_versions_weekday` CHECK (`day_of_week` BETWEEN 1 AND 7)');

        DB::statement('ALTER TABLE `work_pattern_versions` ADD CONSTRAINT `ck_work_pattern_versions_time_range` CHECK ((`crosses_midnight` = 0 AND `end_time` > `start_time`) OR (`crosses_midnight` = 1 AND `end_time` <= `start_time`))');

        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_employee_attendance_policies_status` CHECK (`status` IN (\'baseline\', \'approved\', \'superseded\'))');

        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_employee_attendance_policies_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_employee_attendance_policies_requires_correction_approval` CHECK (`requires_correction_approval` IN (0, 1))');

        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_employee_attendance_policies_allow_late_correcti_4a0077119d69` CHECK (`allow_late_correction_with_mandate` IN (0, 1))');

        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_employee_attendance_policies_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `employee_work_assignments` ADD CONSTRAINT `ck_employee_work_assignments_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `employee_work_days` ADD CONSTRAINT `ck_employee_work_days_shift_number_positive` CHECK (`shift_number` > 0)');

        DB::statement('ALTER TABLE `employee_work_days` ADD CONSTRAINT `ck_employee_work_days_is_work_required` CHECK (`is_work_required` IN (0, 1))');

        DB::statement('ALTER TABLE `employee_work_days` ADD CONSTRAINT `ck_employee_work_days_time_range` CHECK (`scheduled_end_at` > `scheduled_start_at`)');

        DB::statement('ALTER TABLE `employee_attendances` ADD CONSTRAINT `ck_employee_attendances_entry_mode` CHECK (`entry_mode` IN (\'self\', \'officer\', \'import\'))');

        DB::statement('ALTER TABLE `employee_attendances` ADD CONSTRAINT `ck_employee_attendances_check_out` CHECK (`check_out_at` IS NULL OR (`check_in_at` IS NOT NULL AND `check_out_at` >= `check_in_at`))');

        DB::statement('ALTER TABLE `attendance_policies` ADD CONSTRAINT `ck_attendance_policies_mode` CHECK (`mode` IN (\'daily\', \'meeting\', \'both\'))');

        DB::statement('ALTER TABLE `attendance_policies` ADD CONSTRAINT `ck_attendance_policies_status` CHECK (`status` IN (\'baseline\', \'approved\', \'superseded\'))');

        DB::statement('ALTER TABLE `attendance_policies` ADD CONSTRAINT `ck_attendance_policies_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `attendance_policies` ADD CONSTRAINT `ck_attendance_policies_requires_correction_approval` CHECK (`requires_correction_approval` IN (0, 1))');

        DB::statement('ALTER TABLE `attendance_policies` ADD CONSTRAINT `ck_attendance_policies_allow_late_correction_with_mandate` CHECK (`allow_late_correction_with_mandate` IN (0, 1))');

        DB::statement('ALTER TABLE `attendance_policies` ADD CONSTRAINT `ck_attendance_policies_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `attendance_policies` ADD CONSTRAINT `ck_attendance_policies_both_rule` CHECK (`mode` <> \'both\' OR `daily_summary_rule` IS NOT NULL)');

        DB::statement('ALTER TABLE `student_daily_attendances` ADD CONSTRAINT `ck_student_daily_attendances_source_type` CHECK (`source_type` IN (\'manual\', \'meeting_summary\', \'import\'))');

        DB::statement('ALTER TABLE `attendance_corrections` ADD CONSTRAINT `ck_attendance_corrections_status` CHECK (`status` IN (\'pending\', \'approved\', \'rejected\', \'applied\'))');

        DB::statement('ALTER TABLE `attendance_corrections` ADD CONSTRAINT `ck_attendance_corrections_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `attendance_corrections` ADD CONSTRAINT `ck_attendance_corrections_one_target` CHECK ((`employee_attendance_id` IS NOT NULL) + (`student_daily_attendance_id` IS NOT NULL) + (`student_meeting_attendance_id` IS NOT NULL) = 1)');

        DB::statement('ALTER TABLE `attendance_corrections` ADD CONSTRAINT `ck_attendance_corrections_different_approver` CHECK (`approved_by` IS NULL OR `approved_by` <> `requested_by`)');

        DB::statement('ALTER TABLE `honor_policy_versions` ADD CONSTRAINT `ck_honor_policy_versions_source_basis` CHECK (`source_basis` IN (\'scheduled\', \'realized\', \'verified\'))');

        DB::statement('ALTER TABLE `honor_policy_versions` ADD CONSTRAINT `ck_honor_policy_versions_status` CHECK (`status` IN (\'baseline\', \'draft\', \'approved\', \'superseded\'))');

        DB::statement('ALTER TABLE `honor_policy_versions` ADD CONSTRAINT `ck_honor_policy_versions_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `honor_policy_versions` ADD CONSTRAINT `ck_honor_policy_versions_jp_minutes_positive` CHECK (`jp_minutes` > 0)');

        DB::statement('ALTER TABLE `honor_policy_versions` ADD CONSTRAINT `ck_honor_policy_versions_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `honor_components` ADD CONSTRAINT `ck_honor_components_component_type` CHECK (`component_type` IN (\'teaching\', \'allowance\', \'deduction\', \'adjustment\'))');

        DB::statement('ALTER TABLE `honor_components` ADD CONSTRAINT `ck_honor_components_calculation_method` CHECK (`calculation_method` IN (\'per_jp\', \'fixed\', \'prorata\', \'manual\'))');

        DB::statement('ALTER TABLE `honor_components` ADD CONSTRAINT `ck_honor_components_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `honor_rate_scopes` ADD CONSTRAINT `ck_honor_rate_scopes_exclusive_scope` CHECK (`employee_id` IS NULL OR `employee_category_id` IS NULL)');

        DB::statement('ALTER TABLE `honor_rates` ADD CONSTRAINT `ck_honor_rates_unit` CHECK (`unit` IN (\'jp\', \'month\', \'day\', \'item\'))');

        DB::statement('ALTER TABLE `honor_rates` ADD CONSTRAINT `ck_honor_rates_amount_nonnegative` CHECK (`amount` >= 0)');

        DB::statement('ALTER TABLE `honor_rates` ADD CONSTRAINT `ck_honor_rates_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `honor_rates` ADD CONSTRAINT `ck_honor_rates_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `employee_additional_duties` ADD CONSTRAINT `ck_employee_additional_duties_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `employee_additional_duties` ADD CONSTRAINT `ck_employee_additional_duties_one_assignment` CHECK (`position_assignment_id` IS NULL OR `homeroom_assignment_id` IS NULL)');

        DB::statement('ALTER TABLE `honor_periods` ADD CONSTRAINT `ck_honor_periods_status` CHECK (`status` IN (\'open\', \'closed\'))');

        DB::statement('ALTER TABLE `honor_periods` ADD CONSTRAINT `ck_honor_periods_date_range` CHECK (`end_date` > `start_date`)');

        DB::statement('ALTER TABLE `honor_deductions` ADD CONSTRAINT `ck_honor_deductions_status` CHECK (`status` IN (\'draft\', \'approved\', \'rejected\', \'applied\'))');

        DB::statement('ALTER TABLE `honor_deductions` ADD CONSTRAINT `ck_honor_deductions_amount_nonnegative` CHECK (`amount` >= 0)');

        DB::statement('ALTER TABLE `honor_deductions` ADD CONSTRAINT `ck_honor_deductions_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `honor_deductions` ADD CONSTRAINT `ck_honor_deductions_amount_positive` CHECK (`amount` > 0)');

        DB::statement('ALTER TABLE `payrolls` ADD CONSTRAINT `ck_payrolls_payroll_type` CHECK (`payroll_type` IN (\'regular\'))');

        DB::statement('ALTER TABLE `payroll_versions` ADD CONSTRAINT `ck_payroll_versions_status` CHECK (`status` IN (\'draft\', \'submitted\', \'reviewed\', \'returned\', \'locked\', \'superseded\'))');

        DB::statement('ALTER TABLE `payroll_versions` ADD CONSTRAINT `ck_payroll_versions_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `payroll_versions` ADD CONSTRAINT `ck_payroll_versions_teaching_total_nonnegative` CHECK (`teaching_total` >= 0)');

        DB::statement('ALTER TABLE `payroll_versions` ADD CONSTRAINT `ck_payroll_versions_allowance_total_nonnegative` CHECK (`allowance_total` >= 0)');

        DB::statement('ALTER TABLE `payroll_versions` ADD CONSTRAINT `ck_payroll_versions_deduction_total_nonnegative` CHECK (`deduction_total` >= 0)');

        DB::statement('ALTER TABLE `payroll_versions` ADD CONSTRAINT `ck_payroll_versions_net` CHECK (`net_total` = `teaching_total` + `allowance_total` - `deduction_total` + `adjustment_total`)');

        DB::statement('ALTER TABLE `payroll_versions` ADD CONSTRAINT `ck_payroll_versions_lock` CHECK ((`status` = \'locked\' AND `approved_by` IS NOT NULL AND `approved_at` IS NOT NULL AND `locked_at` IS NOT NULL AND `reviewed_by` IS NOT NULL AND `reviewed_at` IS NOT NULL) OR (`status` <> \'locked\' AND `approved_by` IS NULL AND `approved_at` IS NULL AND `locked_at` IS NULL))');

        DB::statement('ALTER TABLE `payroll_versions` ADD CONSTRAINT `ck_payroll_versions_separate_duties` CHECK ((`reviewed_by` IS NULL OR `reviewed_by` <> `calculated_by`) AND (`approved_by` IS NULL OR (`approved_by` <> `calculated_by` AND `approved_by` <> `reviewed_by`)))');

        DB::statement('ALTER TABLE `payroll_lines` ADD CONSTRAINT `ck_payroll_lines_component_type_snapshot` CHECK (`component_type_snapshot` IN (\'teaching\', \'allowance\', \'deduction\', \'adjustment\'))');

        DB::statement('ALTER TABLE `payroll_lines` ADD CONSTRAINT `ck_payroll_lines_direction` CHECK (`direction` IN (\'credit\', \'debit\'))');

        DB::statement('ALTER TABLE `payroll_lines` ADD CONSTRAINT `ck_payroll_lines_quantity_nonnegative` CHECK (`quantity` >= 0)');

        DB::statement('ALTER TABLE `payroll_lines` ADD CONSTRAINT `ck_payroll_lines_rate_snapshot_nonnegative` CHECK (`rate_snapshot` >= 0)');

        DB::statement('ALTER TABLE `payroll_lines` ADD CONSTRAINT `ck_payroll_lines_amount_nonnegative` CHECK (`amount` >= 0)');

        DB::statement('ALTER TABLE `payroll_line_sources` ADD CONSTRAINT `ck_payroll_line_sources_quantity_snapshot_nonnegative` CHECK (`quantity_snapshot` >= 0)');

        DB::statement('ALTER TABLE `payroll_line_sources` ADD CONSTRAINT `ck_payroll_line_sources_rate_snapshot_nonnegative` CHECK (`rate_snapshot` >= 0)');

        DB::statement('ALTER TABLE `payroll_line_sources` ADD CONSTRAINT `ck_payroll_line_sources_amount_snapshot_nonnegative` CHECK (`amount_snapshot` >= 0)');

        DB::statement('ALTER TABLE `payroll_line_sources` ADD CONSTRAINT `ck_payroll_line_sources_one_source` CHECK ((`meeting_id` IS NOT NULL) + (`additional_duty_id` IS NOT NULL) + (`deduction_id` IS NOT NULL) + (`adjustment_id` IS NOT NULL) = 1)');

        DB::statement('ALTER TABLE `payroll_line_sources` ADD CONSTRAINT `ck_payroll_line_sources_category_pair` CHECK ((`category_assignment_id` IS NULL) = (`employee_category_id` IS NULL))');

        DB::statement('ALTER TABLE `payroll_line_sources` ADD CONSTRAINT `ck_payroll_line_sources_teaching_evidence` CHECK (`meeting_id` IS NULL OR (`realization_revision_id` IS NOT NULL AND `verification_id` IS NOT NULL AND `category_assignment_id` IS NOT NULL AND `rate_id` IS NOT NULL))');

        DB::statement('ALTER TABLE `payroll_actions` ADD CONSTRAINT `ck_payroll_actions_action` CHECK (`action` IN (\'calculate\', \'submit\', \'review\', \'return\', \'approve_lock\', \'supersede\'))');

        DB::statement('ALTER TABLE `payroll_actions` ADD CONSTRAINT `ck_payroll_actions_from_status` CHECK (`from_status` IN (\'draft\', \'submitted\', \'reviewed\', \'returned\', \'locked\', \'superseded\'))');

        DB::statement('ALTER TABLE `payroll_actions` ADD CONSTRAINT `ck_payroll_actions_to_status` CHECK (`to_status` IN (\'draft\', \'submitted\', \'reviewed\', \'returned\', \'locked\', \'superseded\'))');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` ADD CONSTRAINT `ck_payroll_adjustment_batches_batch_type` CHECK (`batch_type` IN (\'single\', \'beneficiary_transfer\'))');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` ADD CONSTRAINT `ck_payroll_adjustment_batches_status` CHECK (`status` IN (\'draft\', \'reviewed\', \'approved\', \'applied\', \'rejected\'))');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` ADD CONSTRAINT `ck_payroll_adjustment_batches_transfer_amount_nonnegative` CHECK (`transfer_amount` >= 0)');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` ADD CONSTRAINT `ck_payroll_adjustment_batches_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` ADD CONSTRAINT `ck_payroll_adjustment_batches_batch_scope` CHECK ((`batch_type` = \'single\' AND `to_employee_id` IS NULL AND `transfer_amount` IS NULL) OR (`batch_type` = \'beneficiary_transfer\' AND `original_line_source_id` IS NOT NULL AND `to_employee_id` IS NOT NULL AND `to_employee_id` <> `from_employee_id` AND `transfer_amount` IS NOT NULL AND `transfer_amount` > 0))');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` ADD CONSTRAINT `ck_payroll_adjustment_batches_separate_duties` CHECK ((`reviewed_by` IS NULL OR `reviewed_by` <> `requested_by`) AND (`approved_by` IS NULL OR (`approved_by` <> `requested_by` AND `approved_by` <> `reviewed_by`)))');

        DB::statement('ALTER TABLE `payroll_adjustments` ADD CONSTRAINT `ck_payroll_adjustments_direction` CHECK (`direction` IN (\'credit\', \'debit\'))');

        DB::statement('ALTER TABLE `payroll_adjustments` ADD CONSTRAINT `ck_payroll_adjustments_status` CHECK (`status` IN (\'draft\', \'approved\', \'applied\', \'rejected\'))');

        DB::statement('ALTER TABLE `payroll_adjustments` ADD CONSTRAINT `ck_payroll_adjustments_amount_nonnegative` CHECK (`amount` >= 0)');

        DB::statement('ALTER TABLE `payroll_adjustments` ADD CONSTRAINT `ck_payroll_adjustments_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `payroll_adjustments` ADD CONSTRAINT `ck_payroll_adjustments_amount_positive` CHECK (`amount` > 0)');

        DB::statement('ALTER TABLE `question_types` ADD CONSTRAINT `ck_question_types_has_options` CHECK (`has_options` IN (0, 1))');

        DB::statement('ALTER TABLE `question_types` ADD CONSTRAINT `ck_question_types_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `subject_topics` ADD CONSTRAINT `ck_subject_topics_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `subject_topics` ADD CONSTRAINT `ck_subject_topics_grade` CHECK (`grade` IN (7, 8, 9))');

        DB::statement('ALTER TABLE `questions` ADD CONSTRAINT `ck_questions_status` CHECK (`status` IN (\'draft\', \'ready\', \'inactive\'))');

        DB::statement('ALTER TABLE `question_versions` ADD CONSTRAINT `ck_question_versions_difficulty` CHECK (`difficulty` IN (\'easy\', \'medium\', \'hard\'))');

        DB::statement('ALTER TABLE `question_versions` ADD CONSTRAINT `ck_question_versions_body_format` CHECK (`body_format` IN (\'html\', \'markdown\', \'plain\'))');

        DB::statement('ALTER TABLE `question_versions` ADD CONSTRAINT `ck_question_versions_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `question_versions` ADD CONSTRAINT `ck_question_versions_grade` CHECK (`grade` IN (7, 8, 9))');

        DB::statement('ALTER TABLE `question_versions` ADD CONSTRAINT `ck_question_versions_cognitive_level` CHECK (`cognitive_level` BETWEEN 1 AND 6)');

        DB::statement('ALTER TABLE `question_options` ADD CONSTRAINT `ck_question_options_body_format` CHECK (`body_format` IN (\'html\', \'markdown\', \'plain\'))');

        DB::statement('ALTER TABLE `question_options` ADD CONSTRAINT `ck_question_options_position_positive` CHECK (`position` > 0)');

        DB::statement('ALTER TABLE `question_files` ADD CONSTRAINT `ck_question_files_purpose` CHECK (`purpose` IN (\'stem\', \'option\', \'explanation\', \'key\'))');

        DB::statement('ALTER TABLE `question_files` ADD CONSTRAINT `ck_question_files_position_positive` CHECK (`position` > 0)');

        DB::statement('ALTER TABLE `question_collaborators` ADD CONSTRAINT `ck_question_collaborators_access_level` CHECK (`access_level` IN (\'read\', \'edit\'))');

        DB::statement('ALTER TABLE `question_collaborators` ADD CONSTRAINT `ck_question_collaborators_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `exam_packages` ADD CONSTRAINT `ck_exam_packages_status` CHECK (`status` IN (\'draft\', \'final\', \'archived\'))');

        DB::statement('ALTER TABLE `exam_packages` ADD CONSTRAINT `ck_exam_packages_grade` CHECK (`grade` IN (7, 8, 9))');

        DB::statement('ALTER TABLE `exam_package_items` ADD CONSTRAINT `ck_exam_package_items_format_snapshot` CHECK (`format_snapshot` IN (\'html\', \'markdown\', \'plain\'))');

        DB::statement('ALTER TABLE `exam_package_items` ADD CONSTRAINT `ck_exam_package_items_position_positive` CHECK (`position` > 0)');

        DB::statement('ALTER TABLE `exam_package_items` ADD CONSTRAINT `ck_exam_package_items_points_nonnegative` CHECK (`points` >= 0)');

        DB::statement('ALTER TABLE `exam_package_options` ADD CONSTRAINT `ck_exam_package_options_position_positive` CHECK (`position` > 0)');

        DB::statement('ALTER TABLE `exam_package_files` ADD CONSTRAINT `ck_exam_package_files_purpose` CHECK (`purpose` IN (\'question_asset\', \'key_asset\'))');

        DB::statement('ALTER TABLE `exam_package_files` ADD CONSTRAINT `ck_exam_package_files_position_positive` CHECK (`position` > 0)');

        DB::statement('ALTER TABLE `bk_categories` ADD CONSTRAINT `ck_bk_categories_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `bk_category_grants` ADD CONSTRAINT `ck_bk_category_grants_can_read_internal` CHECK (`can_read_internal` IN (0, 1))');

        DB::statement('ALTER TABLE `bk_category_grants` ADD CONSTRAINT `ck_bk_category_grants_can_write` CHECK (`can_write` IN (0, 1))');

        DB::statement('ALTER TABLE `bk_category_grants` ADD CONSTRAINT `ck_bk_category_grants_can_share` CHECK (`can_share` IN (0, 1))');

        DB::statement('ALTER TABLE `bk_category_grants` ADD CONSTRAINT `ck_bk_category_grants_can_export` CHECK (`can_export` IN (0, 1))');

        DB::statement('ALTER TABLE `bk_category_grants` ADD CONSTRAINT `ck_bk_category_grants_can_download` CHECK (`can_download` IN (0, 1))');

        DB::statement('ALTER TABLE `bk_category_grants` ADD CONSTRAINT `ck_bk_category_grants_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `bk_category_grants` ADD CONSTRAINT `ck_bk_category_grants_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `bk_cases` ADD CONSTRAINT `ck_bk_cases_status` CHECK (`status` IN (\'open\', \'in_progress\', \'closed\', \'archived\'))');

        DB::statement('ALTER TABLE `bk_cases` ADD CONSTRAINT `ck_bk_cases_closed_date` CHECK (`closed_on` IS NULL OR `closed_on` >= `opened_on`)');

        DB::statement('ALTER TABLE `bk_notes` ADD CONSTRAINT `ck_bk_notes_note_type` CHECK (`note_type` IN (\'counseling\', \'violation\', \'coaching\', \'progress\', \'correction\'))');

        DB::statement('ALTER TABLE `bk_notes` ADD CONSTRAINT `ck_bk_notes_violation_points_nonnegative` CHECK (`violation_points` >= 0)');

        DB::statement('ALTER TABLE `bk_followups` ADD CONSTRAINT `ck_bk_followups_status` CHECK (`status` IN (\'planned\', \'in_progress\', \'completed\', \'cancelled\'))');

        DB::statement('ALTER TABLE `bk_summaries` ADD CONSTRAINT `ck_bk_summaries_status` CHECK (`status` IN (\'draft\', \'approved\', \'withdrawn\'))');

        DB::statement('ALTER TABLE `bk_summary_recipients` ADD CONSTRAINT `ck_bk_summary_recipients_expiry` CHECK (`expires_at` IS NULL OR `expires_at` > `created_at`)');

        DB::statement('ALTER TABLE `bk_summary_recipients` ADD CONSTRAINT `ck_bk_summary_recipients_one_relationship` CHECK ((`student_guardian_id` IS NOT NULL) + (`homeroom_assignment_id` IS NOT NULL) = 1)');

        DB::statement('ALTER TABLE `bk_files` ADD CONSTRAINT `ck_bk_files_visibility` CHECK (`visibility` IN (\'internal\', \'shared_summary\'))');

        DB::statement('ALTER TABLE `bk_files` ADD CONSTRAINT `ck_bk_files_one_attachment` CHECK ((`note_id` IS NOT NULL) + (`followup_id` IS NOT NULL) + (`summary_id` IS NOT NULL) <= 1)');

        DB::statement('ALTER TABLE `bk_files` ADD CONSTRAINT `ck_bk_files_shared_summary` CHECK (`visibility` <> \'shared_summary\' OR `summary_id` IS NOT NULL)');

        DB::statement('ALTER TABLE `document_categories` ADD CONSTRAINT `ck_document_categories_access_class` CHECK (`access_class` IN (\'internal\', \'restricted\', \'public_candidate\'))');

        DB::statement('ALTER TABLE `document_categories` ADD CONSTRAINT `ck_document_categories_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `document_category_grants` ADD CONSTRAINT `ck_document_category_grants_can_read` CHECK (`can_read` IN (0, 1))');

        DB::statement('ALTER TABLE `document_category_grants` ADD CONSTRAINT `ck_document_category_grants_can_write` CHECK (`can_write` IN (0, 1))');

        DB::statement('ALTER TABLE `document_category_grants` ADD CONSTRAINT `ck_document_category_grants_can_download` CHECK (`can_download` IN (0, 1))');

        DB::statement('ALTER TABLE `document_category_grants` ADD CONSTRAINT `ck_document_category_grants_can_export` CHECK (`can_export` IN (0, 1))');

        DB::statement('ALTER TABLE `document_category_grants` ADD CONSTRAINT `ck_document_category_grants_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `document_category_grants` ADD CONSTRAINT `ck_document_category_grants_reason_nonempty` CHECK (CHAR_LENGTH(TRIM(`reason`)) > 0)');

        DB::statement('ALTER TABLE `letter_number_sequences` ADD CONSTRAINT `ck_letter_number_sequences_scope_type` CHECK (`scope_type` IN (\'outgoing_letter\', \'incoming_agenda\', \'outgoing_agenda\'))');

        DB::statement('ALTER TABLE `letter_number_sequences` ADD CONSTRAINT `ck_letter_number_sequences_next_value_positive` CHECK (`next_value` > 0)');

        DB::statement('ALTER TABLE `letters` ADD CONSTRAINT `ck_letters_direction` CHECK (`direction` IN (\'incoming\', \'outgoing\'))');

        DB::statement('ALTER TABLE `letters` ADD CONSTRAINT `ck_letters_status` CHECK (`status` IN (\'draft\', \'registered\', \'issued\', \'cancelled\', \'archived\'))');

        DB::statement('ALTER TABLE `letters` ADD CONSTRAINT `ck_letters_issued_metadata` CHECK (`status` <> \'issued\' OR (`direction` = \'outgoing\' AND `number_sequence_id` IS NOT NULL AND `sequence_number` IS NOT NULL AND `formatted_number` IS NOT NULL AND `issued_by` IS NOT NULL AND `issued_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `letters` ADD CONSTRAINT `ck_letters_cancel_reason` CHECK (`status` <> \'cancelled\' OR (`cancel_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`cancel_reason`)) > 0))');

        DB::statement('ALTER TABLE `documents` ADD CONSTRAINT `ck_documents_status` CHECK (`status` IN (\'active\', \'archived\', \'pending_disposal\', \'disposed\'))');

        DB::statement('ALTER TABLE `documents` ADD CONSTRAINT `ck_documents_legal_hold` CHECK (`legal_hold` IN (0, 1))');

        DB::statement('ALTER TABLE `document_files` ADD CONSTRAINT `ck_document_files_purpose` CHECK (`purpose` IN (\'main\', \'attachment\'))');

        DB::statement('ALTER TABLE `document_files` ADD CONSTRAINT `ck_document_files_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `letter_dispositions` ADD CONSTRAINT `ck_letter_dispositions_status` CHECK (`status` IN (\'assigned\', \'in_progress\', \'completed\', \'cancelled\'))');

        DB::statement('ALTER TABLE `disposition_actions` ADD CONSTRAINT `ck_disposition_actions_action` CHECK (`action` IN (\'assign\', \'start\', \'complete\', \'cancel\', \'forward\'))');

        DB::statement('ALTER TABLE `disposition_actions` ADD CONSTRAINT `ck_disposition_actions_from_status` CHECK (`from_status` IN (\'assigned\', \'in_progress\', \'completed\', \'cancelled\'))');

        DB::statement('ALTER TABLE `disposition_actions` ADD CONSTRAINT `ck_disposition_actions_to_status` CHECK (`to_status` IN (\'assigned\', \'in_progress\', \'completed\', \'cancelled\'))');

        DB::statement('ALTER TABLE `content_categories` ADD CONSTRAINT `ck_content_categories_content_type` CHECK (`content_type` IN (\'page\', \'news\', \'announcement\', \'activity\', \'gallery\', \'achievement\', \'agenda\', \'download\', \'contact\'))');

        DB::statement('ALTER TABLE `content_categories` ADD CONSTRAINT `ck_content_categories_is_active` CHECK (`is_active` IN (0, 1))');

        DB::statement('ALTER TABLE `contents` ADD CONSTRAINT `ck_contents_type` CHECK (`type` IN (\'page\', \'news\', \'announcement\', \'activity\', \'gallery\', \'achievement\', \'agenda\', \'download\', \'contact\'))');

        DB::statement('ALTER TABLE `contents` ADD CONSTRAINT `ck_contents_status` CHECK (`status` IN (\'draft\', \'in_review\', \'reviewed\', \'published\', \'archived\'))');

        DB::statement('ALTER TABLE `content_revisions` ADD CONSTRAINT `ck_content_revisions_body_format` CHECK (`body_format` IN (\'html\', \'markdown\', \'plain\'))');

        DB::statement('ALTER TABLE `content_revisions` ADD CONSTRAINT `ck_content_revisions_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `content_revisions` ADD CONSTRAINT `ck_content_revisions_event_time` CHECK (`event_start_at` IS NULL OR `event_end_at` IS NULL OR `event_end_at` >= `event_start_at`)');

        DB::statement('ALTER TABLE `content_files` ADD CONSTRAINT `ck_content_files_purpose` CHECK (`purpose` IN (\'cover\', \'gallery\', \'download\', \'inline\'))');

        DB::statement('ALTER TABLE `content_files` ADD CONSTRAINT `ck_content_files_position_positive` CHECK (`position` > 0)');

        DB::statement('ALTER TABLE `publication_actions` ADD CONSTRAINT `ck_publication_actions_action` CHECK (`action` IN (\'submit\', \'review\', \'return\', \'publish\', \'archive\', \'withdraw\'))');

        DB::statement('ALTER TABLE `publication_assets` ADD CONSTRAINT `ck_publication_assets_distinct_file` CHECK (`source_file_id` IS NULL OR `source_file_id` <> `public_file_id`)');

        DB::statement('ALTER TABLE `portal_announcements` ADD CONSTRAINT `ck_portal_announcements_status` CHECK (`status` IN (\'draft\', \'published\', \'archived\'))');

        DB::statement('ALTER TABLE `portal_announcements` ADD CONSTRAINT `ck_portal_announcements_time_range` CHECK (`ends_at` IS NULL OR `ends_at` > `starts_at`)');

        DB::statement('ALTER TABLE `portal_announcement_targets` ADD CONSTRAINT `ck_portal_announcement_targets_scope_type` CHECK (`scope_type` IN (\'all_verified_guardians\', \'class\', \'student\'))');

        DB::statement('ALTER TABLE `portal_announcement_targets` ADD CONSTRAINT `ck_portal_announcement_targets_scope` CHECK ((`scope_type` = \'all_verified_guardians\' AND `class_period_id` IS NULL AND `student_id` IS NULL) OR (`scope_type` = \'class\' AND `class_period_id` IS NOT NULL AND `student_id` IS NULL) OR (`scope_type` = \'student\' AND `student_id` IS NOT NULL AND `class_period_id` IS NULL))');

        DB::statement('ALTER TABLE `portal_announcement_files` ADD CONSTRAINT `ck_portal_announcement_files_position_positive` CHECK (`position` > 0)');

        DB::statement('ALTER TABLE `report_jobs` ADD CONSTRAINT `ck_report_jobs_output_format` CHECK (`output_format` IN (\'pdf\', \'xlsx\', \'csv\', \'docx\'))');

        DB::statement('ALTER TABLE `report_jobs` ADD CONSTRAINT `ck_report_jobs_dataset_status` CHECK (`dataset_status` IN (\'pending\', \'extracting\', \'ready\', \'purged\'))');

        DB::statement('ALTER TABLE `report_jobs` ADD CONSTRAINT `ck_report_jobs_status` CHECK (`status` IN (\'queued\', \'running\', \'completed\', \'failed\', \'expired\', \'cancelled\'))');

        DB::statement('ALTER TABLE `report_jobs` ADD CONSTRAINT `ck_report_jobs_expiry` CHECK (`expires_at` IS NULL OR `expires_at` > `created_at`)');

        DB::statement('ALTER TABLE `report_dataset_rows` ADD CONSTRAINT `ck_report_dataset_rows_row_number_positive` CHECK (`row_number` > 0)');

        DB::statement('ALTER TABLE `import_batches` ADD CONSTRAINT `ck_import_batches_atomicity_mode` CHECK (`atomicity_mode` IN (\'file\', \'group\'))');

        DB::statement('ALTER TABLE `import_batches` ADD CONSTRAINT `ck_import_batches_status` CHECK (`status` IN (\'uploaded\', \'validating\', \'preview_ready\', \'importing\', \'completed\', \'failed\', \'cancelled\'))');

        DB::statement('ALTER TABLE `source_records` ADD CONSTRAINT `ck_source_records_entity_type` CHECK (`entity_type` IN (\'person\', \'student\', \'guardian\', \'employee\', \'subject\', \'class\'))');

        DB::statement('ALTER TABLE `source_records` ADD CONSTRAINT `ck_source_records_one_target` CHECK ((`person_id` IS NOT NULL) + (`student_id` IS NOT NULL) + (`guardian_id` IS NOT NULL) + (`employee_id` IS NOT NULL) + (`subject_id` IS NOT NULL) + (`class_id` IS NOT NULL) = 1)');

        DB::statement('ALTER TABLE `source_records` ADD CONSTRAINT `ck_source_records_target_type` CHECK ((`entity_type` = \'person\' AND `person_id` IS NOT NULL) OR (`entity_type` = \'student\' AND `student_id` IS NOT NULL) OR (`entity_type` = \'guardian\' AND `guardian_id` IS NOT NULL) OR (`entity_type` = \'employee\' AND `employee_id` IS NOT NULL) OR (`entity_type` = \'subject\' AND `subject_id` IS NOT NULL) OR (`entity_type` = \'class\' AND `class_id` IS NOT NULL))');

        DB::statement('ALTER TABLE `import_rows` ADD CONSTRAINT `ck_import_rows_result_action` CHECK (`result_action` IN (\'insert\', \'update\', \'unchanged\'))');

        DB::statement('ALTER TABLE `import_rows` ADD CONSTRAINT `ck_import_rows_status` CHECK (`status` IN (\'pending\', \'valid\', \'invalid\', \'imported\', \'skipped\', \'failed\'))');

        DB::statement('ALTER TABLE `import_rows` ADD CONSTRAINT `ck_import_rows_row_number_positive` CHECK (`row_number` > 0)');

        DB::statement('ALTER TABLE `idempotency_requests` ADD CONSTRAINT `ck_idempotency_requests_status` CHECK (`status` IN (\'processing\', \'completed\', \'failed\'))');

        DB::statement('ALTER TABLE `idempotency_requests` ADD CONSTRAINT `ck_idempotency_requests_expiry` CHECK (`expires_at` IS NULL OR `expires_at` > `created_at`)');

        DB::statement('ALTER TABLE `retention_policies` ADD CONSTRAINT `ck_retention_policies_disposal_method` CHECK (`disposal_method` IN (\'delete\', \'anonymize\', \'archive\'))');

        DB::statement('ALTER TABLE `retention_policies` ADD CONSTRAINT `ck_retention_policies_status` CHECK (`status` IN (\'baseline\', \'draft\', \'approved\', \'superseded\'))');

        DB::statement('ALTER TABLE `retention_policies` ADD CONSTRAINT `ck_retention_policies_version_number_positive` CHECK (`version_number` > 0)');

        DB::statement('ALTER TABLE `retention_policies` ADD CONSTRAINT `ck_retention_policies_effective_range` CHECK (`effective_to` IS NULL OR `effective_to` > `effective_from`)');

        DB::statement('ALTER TABLE `retention_policies` ADD CONSTRAINT `ck_retention_policies_active_rule` CHECK (`status` NOT IN (\'baseline\', \'approved\') OR `retention_days` IS NOT NULL OR `retention_rule` IS NOT NULL)');

        DB::statement('ALTER TABLE `audit_logs` ADD CONSTRAINT `ck_audit_logs_classification` CHECK (`classification` IN (\'internal\', \'sensitive_metadata\'))');

        DB::statement('ALTER TABLE `backup_runs` ADD CONSTRAINT `ck_backup_runs_status` CHECK (`status` IN (\'running\', \'completed\', \'failed\', \'expired\'))');

        DB::statement('ALTER TABLE `backup_runs` ADD CONSTRAINT `ck_backup_runs_is_encrypted` CHECK (`is_encrypted` IN (0, 1))');

        DB::statement('ALTER TABLE `backup_runs` ADD CONSTRAINT `ck_backup_runs_expiry` CHECK (`expires_at` IS NULL OR `expires_at` > `created_at`)');

        DB::statement('ALTER TABLE `restore_runs` ADD CONSTRAINT `ck_restore_runs_status` CHECK (`status` IN (\'running\', \'completed\', \'failed\'))');

        DB::statement('ALTER TABLE `system_events` ADD CONSTRAINT `ck_system_events_severity` CHECK (`severity` IN (\'info\', \'warning\', \'error\', \'critical\'))');

        DB::statement('ALTER TABLE `system_events` ADD CONSTRAINT `ck_system_events_status` CHECK (`status` IN (\'open\', \'acknowledged\', \'resolved\'))');
        DB::statement('ALTER TABLE `business_decisions` ADD CONSTRAINT `ck_business_decisions_approval_metadata` CHECK ((`status` = \'approved\' AND `approved_by` IS NOT NULL AND `approved_at` IS NOT NULL) OR (`status` IN (\'baseline\', \'draft\', \'proposed\') AND `approved_by` IS NULL AND `approved_at` IS NULL) OR (`status` = \'superseded\' AND ((`approved_by` IS NULL) = (`approved_at` IS NULL))))');

        DB::statement('ALTER TABLE `employee_attendance_policies` ADD CONSTRAINT `ck_employee_attendance_policies_approval_metadata` CHECK ((`status` = \'approved\' AND `approved_by` IS NOT NULL AND `approved_at` IS NOT NULL) OR (`status` IN (\'baseline\', \'draft\', \'proposed\') AND `approved_by` IS NULL AND `approved_at` IS NULL) OR (`status` = \'superseded\' AND ((`approved_by` IS NULL) = (`approved_at` IS NULL))))');

        DB::statement('ALTER TABLE `attendance_policies` ADD CONSTRAINT `ck_attendance_policies_approval_metadata` CHECK ((`status` = \'approved\' AND `approved_by` IS NOT NULL AND `approved_at` IS NOT NULL) OR (`status` IN (\'baseline\', \'draft\', \'proposed\') AND `approved_by` IS NULL AND `approved_at` IS NULL) OR (`status` = \'superseded\' AND ((`approved_by` IS NULL) = (`approved_at` IS NULL))))');

        DB::statement('ALTER TABLE `honor_policy_versions` ADD CONSTRAINT `ck_honor_policy_versions_approval_metadata` CHECK ((`status` = \'approved\' AND `approved_by` IS NOT NULL AND `approved_at` IS NOT NULL) OR (`status` IN (\'baseline\', \'draft\', \'proposed\') AND `approved_by` IS NULL AND `approved_at` IS NULL) OR (`status` = \'superseded\' AND ((`approved_by` IS NULL) = (`approved_at` IS NULL))))');

        DB::statement('ALTER TABLE `retention_policies` ADD CONSTRAINT `ck_retention_policies_approval_metadata` CHECK ((`status` = \'approved\' AND `approved_by` IS NOT NULL AND `approved_at` IS NOT NULL) OR (`status` IN (\'baseline\', \'draft\', \'proposed\') AND `approved_by` IS NULL AND `approved_at` IS NULL) OR (`status` = \'superseded\' AND ((`approved_by` IS NULL) = (`approved_at` IS NULL))))');

        DB::statement('ALTER TABLE `honor_rates` ADD CONSTRAINT `ck_honor_rates_approval_pair` CHECK ((`approved_by` IS NULL) = (`approved_at` IS NULL))');

        DB::statement('ALTER TABLE `employee_additional_duties` ADD CONSTRAINT `ck_employee_additional_duties_approval_pair` CHECK ((`approved_by` IS NULL) = (`approved_at` IS NULL))');

        DB::statement('ALTER TABLE `bk_summaries` ADD CONSTRAINT `ck_bk_summaries_approval_pair` CHECK ((`approved_by` IS NULL) = (`approved_at` IS NULL))');

        DB::statement('ALTER TABLE `honor_deductions` ADD CONSTRAINT `ck_honor_deductions_approval_metadata` CHECK (`status` NOT IN (\'approved\', \'applied\') OR (`approved_by` IS NOT NULL AND `approved_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `payroll_adjustments` ADD CONSTRAINT `ck_payroll_adjustments_approval_metadata` CHECK (`status` NOT IN (\'approved\', \'applied\') OR (`approved_by` IS NOT NULL AND `approved_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `attendance_corrections` ADD CONSTRAINT `ck_attendance_corrections_approval_metadata` CHECK (`status` NOT IN (\'approved\', \'applied\') OR (`approved_by` IS NOT NULL AND `approved_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `honor_deductions` ADD CONSTRAINT `ck_honor_deductions_applied_reference` CHECK ((`status` = \'applied\') = (`applied_payroll_id` IS NOT NULL))');

        DB::statement('ALTER TABLE `honor_deductions` ADD CONSTRAINT `ck_honor_deductions_rejection_reason` CHECK (`status` <> \'rejected\' OR (`rejection_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`rejection_reason`)) > 0))');

        DB::statement('ALTER TABLE `payroll_adjustments` ADD CONSTRAINT `ck_payroll_adjustments_applied_reference` CHECK ((`status` = \'applied\') = (`applied_line_source_id` IS NOT NULL))');

        DB::statement('ALTER TABLE `attendance_corrections` ADD CONSTRAINT `ck_attendance_corrections_applied_metadata` CHECK ((`status` = \'applied\') = (`applied_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `attendance_corrections` ADD CONSTRAINT `ck_attendance_corrections_rejection_reason` CHECK (`status` <> \'rejected\' OR (`rejection_reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`rejection_reason`)) > 0))');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` ADD CONSTRAINT `ck_payroll_adjustment_batches_review_metadata` CHECK (`status` NOT IN (\'reviewed\', \'approved\', \'applied\') OR (`reviewed_by` IS NOT NULL AND `reviewed_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` ADD CONSTRAINT `ck_payroll_adjustment_batches_approval_metadata` CHECK (`status` NOT IN (\'approved\', \'applied\') OR (`approved_by` IS NOT NULL AND `approved_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` ADD CONSTRAINT `ck_payroll_adjustment_batches_applied_metadata` CHECK ((`status` = \'applied\' AND `applied_by` IS NOT NULL AND `applied_at` IS NOT NULL) OR (`status` <> \'applied\' AND `applied_by` IS NULL AND `applied_at` IS NULL))');

        DB::statement('ALTER TABLE `student_guardians` ADD CONSTRAINT `ck_student_guardians_verified_metadata` CHECK (`verification_status` <> \'verified\' OR (`verified_by` IS NOT NULL AND `verified_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `student_guardians` ADD CONSTRAINT `ck_student_guardians_revoked_metadata` CHECK (`verification_status` <> \'revoked\' OR (`revoked_by` IS NOT NULL AND `revoked_at` IS NOT NULL AND (`reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`reason`)) > 0)))');

        DB::statement('ALTER TABLE `timetable_versions` ADD CONSTRAINT `ck_timetable_versions_published_metadata` CHECK (`status` <> \'published\' OR (`published_by` IS NOT NULL AND `published_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `exam_packages` ADD CONSTRAINT `ck_exam_packages_final_metadata` CHECK (`status` <> \'final\' OR (`finalized_by` IS NOT NULL AND `finalized_at` IS NOT NULL AND `content_hash` IS NOT NULL))');

        DB::statement('ALTER TABLE `bk_summaries` ADD CONSTRAINT `ck_bk_summaries_approved_metadata` CHECK (`status` <> \'approved\' OR (`approved_by` IS NOT NULL AND `approved_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `contents` ADD CONSTRAINT `ck_contents_published_metadata` CHECK (`status` <> \'published\' OR (`published_revision_id` IS NOT NULL AND `published_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `publication_actions` ADD CONSTRAINT `ck_publication_actions_publish_mandate` CHECK (`action` <> \'publish\' OR `mandate_id` IS NOT NULL)');

        DB::statement('ALTER TABLE `publication_actions` ADD CONSTRAINT `ck_publication_actions_reason` CHECK (`action` NOT IN (\'return\', \'withdraw\') OR (`reason` IS NOT NULL AND CHAR_LENGTH(TRIM(`reason`)) > 0))');

        DB::statement('ALTER TABLE `portal_announcements` ADD CONSTRAINT `ck_portal_announcements_published_metadata` CHECK (`status` <> \'published\' OR (`published_by` IS NOT NULL AND `published_at` IS NOT NULL))');

        DB::statement('ALTER TABLE `report_jobs` ADD CONSTRAINT `ck_report_jobs_completed_metadata` CHECK (`status` <> \'completed\' OR (`output_file_id` IS NOT NULL AND `finished_at` IS NOT NULL AND `dataset_status` = \'ready\'))');

        DB::statement('ALTER TABLE `report_jobs` ADD CONSTRAINT `ck_report_jobs_ready_dataset` CHECK (`dataset_status` <> \'ready\' OR (`dataset_hash` IS NOT NULL AND `dataset_extracted_at` IS NOT NULL))');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `report_jobs` DROP CHECK `ck_report_jobs_ready_dataset`');

        DB::statement('ALTER TABLE `report_jobs` DROP CHECK `ck_report_jobs_completed_metadata`');

        DB::statement('ALTER TABLE `portal_announcements` DROP CHECK `ck_portal_announcements_published_metadata`');

        DB::statement('ALTER TABLE `publication_actions` DROP CHECK `ck_publication_actions_reason`');

        DB::statement('ALTER TABLE `publication_actions` DROP CHECK `ck_publication_actions_publish_mandate`');

        DB::statement('ALTER TABLE `contents` DROP CHECK `ck_contents_published_metadata`');

        DB::statement('ALTER TABLE `bk_summaries` DROP CHECK `ck_bk_summaries_approved_metadata`');

        DB::statement('ALTER TABLE `exam_packages` DROP CHECK `ck_exam_packages_final_metadata`');

        DB::statement('ALTER TABLE `timetable_versions` DROP CHECK `ck_timetable_versions_published_metadata`');

        DB::statement('ALTER TABLE `student_guardians` DROP CHECK `ck_student_guardians_revoked_metadata`');

        DB::statement('ALTER TABLE `student_guardians` DROP CHECK `ck_student_guardians_verified_metadata`');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` DROP CHECK `ck_payroll_adjustment_batches_applied_metadata`');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` DROP CHECK `ck_payroll_adjustment_batches_approval_metadata`');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` DROP CHECK `ck_payroll_adjustment_batches_review_metadata`');

        DB::statement('ALTER TABLE `attendance_corrections` DROP CHECK `ck_attendance_corrections_rejection_reason`');

        DB::statement('ALTER TABLE `attendance_corrections` DROP CHECK `ck_attendance_corrections_applied_metadata`');

        DB::statement('ALTER TABLE `payroll_adjustments` DROP CHECK `ck_payroll_adjustments_applied_reference`');

        DB::statement('ALTER TABLE `honor_deductions` DROP CHECK `ck_honor_deductions_rejection_reason`');

        DB::statement('ALTER TABLE `honor_deductions` DROP CHECK `ck_honor_deductions_applied_reference`');

        DB::statement('ALTER TABLE `attendance_corrections` DROP CHECK `ck_attendance_corrections_approval_metadata`');

        DB::statement('ALTER TABLE `payroll_adjustments` DROP CHECK `ck_payroll_adjustments_approval_metadata`');

        DB::statement('ALTER TABLE `honor_deductions` DROP CHECK `ck_honor_deductions_approval_metadata`');

        DB::statement('ALTER TABLE `bk_summaries` DROP CHECK `ck_bk_summaries_approval_pair`');

        DB::statement('ALTER TABLE `employee_additional_duties` DROP CHECK `ck_employee_additional_duties_approval_pair`');

        DB::statement('ALTER TABLE `honor_rates` DROP CHECK `ck_honor_rates_approval_pair`');

        DB::statement('ALTER TABLE `retention_policies` DROP CHECK `ck_retention_policies_approval_metadata`');

        DB::statement('ALTER TABLE `honor_policy_versions` DROP CHECK `ck_honor_policy_versions_approval_metadata`');

        DB::statement('ALTER TABLE `attendance_policies` DROP CHECK `ck_attendance_policies_approval_metadata`');

        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_employee_attendance_policies_approval_metadata`');

        DB::statement('ALTER TABLE `business_decisions` DROP CHECK `ck_business_decisions_approval_metadata`');
        DB::statement('ALTER TABLE `system_events` DROP CHECK `ck_system_events_status`');

        DB::statement('ALTER TABLE `system_events` DROP CHECK `ck_system_events_severity`');

        DB::statement('ALTER TABLE `restore_runs` DROP CHECK `ck_restore_runs_status`');

        DB::statement('ALTER TABLE `backup_runs` DROP CHECK `ck_backup_runs_expiry`');

        DB::statement('ALTER TABLE `backup_runs` DROP CHECK `ck_backup_runs_is_encrypted`');

        DB::statement('ALTER TABLE `backup_runs` DROP CHECK `ck_backup_runs_status`');

        DB::statement('ALTER TABLE `audit_logs` DROP CHECK `ck_audit_logs_classification`');

        DB::statement('ALTER TABLE `retention_policies` DROP CHECK `ck_retention_policies_active_rule`');

        DB::statement('ALTER TABLE `retention_policies` DROP CHECK `ck_retention_policies_effective_range`');

        DB::statement('ALTER TABLE `retention_policies` DROP CHECK `ck_retention_policies_version_number_positive`');

        DB::statement('ALTER TABLE `retention_policies` DROP CHECK `ck_retention_policies_status`');

        DB::statement('ALTER TABLE `retention_policies` DROP CHECK `ck_retention_policies_disposal_method`');

        DB::statement('ALTER TABLE `idempotency_requests` DROP CHECK `ck_idempotency_requests_expiry`');

        DB::statement('ALTER TABLE `idempotency_requests` DROP CHECK `ck_idempotency_requests_status`');

        DB::statement('ALTER TABLE `import_rows` DROP CHECK `ck_import_rows_row_number_positive`');

        DB::statement('ALTER TABLE `import_rows` DROP CHECK `ck_import_rows_status`');

        DB::statement('ALTER TABLE `import_rows` DROP CHECK `ck_import_rows_result_action`');

        DB::statement('ALTER TABLE `source_records` DROP CHECK `ck_source_records_target_type`');

        DB::statement('ALTER TABLE `source_records` DROP CHECK `ck_source_records_one_target`');

        DB::statement('ALTER TABLE `source_records` DROP CHECK `ck_source_records_entity_type`');

        DB::statement('ALTER TABLE `import_batches` DROP CHECK `ck_import_batches_status`');

        DB::statement('ALTER TABLE `import_batches` DROP CHECK `ck_import_batches_atomicity_mode`');

        DB::statement('ALTER TABLE `report_dataset_rows` DROP CHECK `ck_report_dataset_rows_row_number_positive`');

        DB::statement('ALTER TABLE `report_jobs` DROP CHECK `ck_report_jobs_expiry`');

        DB::statement('ALTER TABLE `report_jobs` DROP CHECK `ck_report_jobs_status`');

        DB::statement('ALTER TABLE `report_jobs` DROP CHECK `ck_report_jobs_dataset_status`');

        DB::statement('ALTER TABLE `report_jobs` DROP CHECK `ck_report_jobs_output_format`');

        DB::statement('ALTER TABLE `portal_announcement_files` DROP CHECK `ck_portal_announcement_files_position_positive`');

        DB::statement('ALTER TABLE `portal_announcement_targets` DROP CHECK `ck_portal_announcement_targets_scope`');

        DB::statement('ALTER TABLE `portal_announcement_targets` DROP CHECK `ck_portal_announcement_targets_scope_type`');

        DB::statement('ALTER TABLE `portal_announcements` DROP CHECK `ck_portal_announcements_time_range`');

        DB::statement('ALTER TABLE `portal_announcements` DROP CHECK `ck_portal_announcements_status`');

        DB::statement('ALTER TABLE `publication_assets` DROP CHECK `ck_publication_assets_distinct_file`');

        DB::statement('ALTER TABLE `publication_actions` DROP CHECK `ck_publication_actions_action`');

        DB::statement('ALTER TABLE `content_files` DROP CHECK `ck_content_files_position_positive`');

        DB::statement('ALTER TABLE `content_files` DROP CHECK `ck_content_files_purpose`');

        DB::statement('ALTER TABLE `content_revisions` DROP CHECK `ck_content_revisions_event_time`');

        DB::statement('ALTER TABLE `content_revisions` DROP CHECK `ck_content_revisions_version_number_positive`');

        DB::statement('ALTER TABLE `content_revisions` DROP CHECK `ck_content_revisions_body_format`');

        DB::statement('ALTER TABLE `contents` DROP CHECK `ck_contents_status`');

        DB::statement('ALTER TABLE `contents` DROP CHECK `ck_contents_type`');

        DB::statement('ALTER TABLE `content_categories` DROP CHECK `ck_content_categories_is_active`');

        DB::statement('ALTER TABLE `content_categories` DROP CHECK `ck_content_categories_content_type`');

        DB::statement('ALTER TABLE `disposition_actions` DROP CHECK `ck_disposition_actions_to_status`');

        DB::statement('ALTER TABLE `disposition_actions` DROP CHECK `ck_disposition_actions_from_status`');

        DB::statement('ALTER TABLE `disposition_actions` DROP CHECK `ck_disposition_actions_action`');

        DB::statement('ALTER TABLE `letter_dispositions` DROP CHECK `ck_letter_dispositions_status`');

        DB::statement('ALTER TABLE `document_files` DROP CHECK `ck_document_files_version_number_positive`');

        DB::statement('ALTER TABLE `document_files` DROP CHECK `ck_document_files_purpose`');

        DB::statement('ALTER TABLE `documents` DROP CHECK `ck_documents_legal_hold`');

        DB::statement('ALTER TABLE `documents` DROP CHECK `ck_documents_status`');

        DB::statement('ALTER TABLE `letters` DROP CHECK `ck_letters_cancel_reason`');

        DB::statement('ALTER TABLE `letters` DROP CHECK `ck_letters_issued_metadata`');

        DB::statement('ALTER TABLE `letters` DROP CHECK `ck_letters_status`');

        DB::statement('ALTER TABLE `letters` DROP CHECK `ck_letters_direction`');

        DB::statement('ALTER TABLE `letter_number_sequences` DROP CHECK `ck_letter_number_sequences_next_value_positive`');

        DB::statement('ALTER TABLE `letter_number_sequences` DROP CHECK `ck_letter_number_sequences_scope_type`');

        DB::statement('ALTER TABLE `document_category_grants` DROP CHECK `ck_document_category_grants_reason_nonempty`');

        DB::statement('ALTER TABLE `document_category_grants` DROP CHECK `ck_document_category_grants_effective_range`');

        DB::statement('ALTER TABLE `document_category_grants` DROP CHECK `ck_document_category_grants_can_export`');

        DB::statement('ALTER TABLE `document_category_grants` DROP CHECK `ck_document_category_grants_can_download`');

        DB::statement('ALTER TABLE `document_category_grants` DROP CHECK `ck_document_category_grants_can_write`');

        DB::statement('ALTER TABLE `document_category_grants` DROP CHECK `ck_document_category_grants_can_read`');

        DB::statement('ALTER TABLE `document_categories` DROP CHECK `ck_document_categories_is_active`');

        DB::statement('ALTER TABLE `document_categories` DROP CHECK `ck_document_categories_access_class`');

        DB::statement('ALTER TABLE `bk_files` DROP CHECK `ck_bk_files_shared_summary`');

        DB::statement('ALTER TABLE `bk_files` DROP CHECK `ck_bk_files_one_attachment`');

        DB::statement('ALTER TABLE `bk_files` DROP CHECK `ck_bk_files_visibility`');

        DB::statement('ALTER TABLE `bk_summary_recipients` DROP CHECK `ck_bk_summary_recipients_one_relationship`');

        DB::statement('ALTER TABLE `bk_summary_recipients` DROP CHECK `ck_bk_summary_recipients_expiry`');

        DB::statement('ALTER TABLE `bk_summaries` DROP CHECK `ck_bk_summaries_status`');

        DB::statement('ALTER TABLE `bk_followups` DROP CHECK `ck_bk_followups_status`');

        DB::statement('ALTER TABLE `bk_notes` DROP CHECK `ck_bk_notes_violation_points_nonnegative`');

        DB::statement('ALTER TABLE `bk_notes` DROP CHECK `ck_bk_notes_note_type`');

        DB::statement('ALTER TABLE `bk_cases` DROP CHECK `ck_bk_cases_closed_date`');

        DB::statement('ALTER TABLE `bk_cases` DROP CHECK `ck_bk_cases_status`');

        DB::statement('ALTER TABLE `bk_category_grants` DROP CHECK `ck_bk_category_grants_reason_nonempty`');

        DB::statement('ALTER TABLE `bk_category_grants` DROP CHECK `ck_bk_category_grants_effective_range`');

        DB::statement('ALTER TABLE `bk_category_grants` DROP CHECK `ck_bk_category_grants_can_download`');

        DB::statement('ALTER TABLE `bk_category_grants` DROP CHECK `ck_bk_category_grants_can_export`');

        DB::statement('ALTER TABLE `bk_category_grants` DROP CHECK `ck_bk_category_grants_can_share`');

        DB::statement('ALTER TABLE `bk_category_grants` DROP CHECK `ck_bk_category_grants_can_write`');

        DB::statement('ALTER TABLE `bk_category_grants` DROP CHECK `ck_bk_category_grants_can_read_internal`');

        DB::statement('ALTER TABLE `bk_categories` DROP CHECK `ck_bk_categories_is_active`');

        DB::statement('ALTER TABLE `exam_package_files` DROP CHECK `ck_exam_package_files_position_positive`');

        DB::statement('ALTER TABLE `exam_package_files` DROP CHECK `ck_exam_package_files_purpose`');

        DB::statement('ALTER TABLE `exam_package_options` DROP CHECK `ck_exam_package_options_position_positive`');

        DB::statement('ALTER TABLE `exam_package_items` DROP CHECK `ck_exam_package_items_points_nonnegative`');

        DB::statement('ALTER TABLE `exam_package_items` DROP CHECK `ck_exam_package_items_position_positive`');

        DB::statement('ALTER TABLE `exam_package_items` DROP CHECK `ck_exam_package_items_format_snapshot`');

        DB::statement('ALTER TABLE `exam_packages` DROP CHECK `ck_exam_packages_grade`');

        DB::statement('ALTER TABLE `exam_packages` DROP CHECK `ck_exam_packages_status`');

        DB::statement('ALTER TABLE `question_collaborators` DROP CHECK `ck_question_collaborators_effective_range`');

        DB::statement('ALTER TABLE `question_collaborators` DROP CHECK `ck_question_collaborators_access_level`');

        DB::statement('ALTER TABLE `question_files` DROP CHECK `ck_question_files_position_positive`');

        DB::statement('ALTER TABLE `question_files` DROP CHECK `ck_question_files_purpose`');

        DB::statement('ALTER TABLE `question_options` DROP CHECK `ck_question_options_position_positive`');

        DB::statement('ALTER TABLE `question_options` DROP CHECK `ck_question_options_body_format`');

        DB::statement('ALTER TABLE `question_versions` DROP CHECK `ck_question_versions_cognitive_level`');

        DB::statement('ALTER TABLE `question_versions` DROP CHECK `ck_question_versions_grade`');

        DB::statement('ALTER TABLE `question_versions` DROP CHECK `ck_question_versions_version_number_positive`');

        DB::statement('ALTER TABLE `question_versions` DROP CHECK `ck_question_versions_body_format`');

        DB::statement('ALTER TABLE `question_versions` DROP CHECK `ck_question_versions_difficulty`');

        DB::statement('ALTER TABLE `questions` DROP CHECK `ck_questions_status`');

        DB::statement('ALTER TABLE `subject_topics` DROP CHECK `ck_subject_topics_grade`');

        DB::statement('ALTER TABLE `subject_topics` DROP CHECK `ck_subject_topics_is_active`');

        DB::statement('ALTER TABLE `question_types` DROP CHECK `ck_question_types_is_active`');

        DB::statement('ALTER TABLE `question_types` DROP CHECK `ck_question_types_has_options`');

        DB::statement('ALTER TABLE `payroll_adjustments` DROP CHECK `ck_payroll_adjustments_amount_positive`');

        DB::statement('ALTER TABLE `payroll_adjustments` DROP CHECK `ck_payroll_adjustments_reason_nonempty`');

        DB::statement('ALTER TABLE `payroll_adjustments` DROP CHECK `ck_payroll_adjustments_amount_nonnegative`');

        DB::statement('ALTER TABLE `payroll_adjustments` DROP CHECK `ck_payroll_adjustments_status`');

        DB::statement('ALTER TABLE `payroll_adjustments` DROP CHECK `ck_payroll_adjustments_direction`');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` DROP CHECK `ck_payroll_adjustment_batches_separate_duties`');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` DROP CHECK `ck_payroll_adjustment_batches_batch_scope`');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` DROP CHECK `ck_payroll_adjustment_batches_reason_nonempty`');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` DROP CHECK `ck_payroll_adjustment_batches_transfer_amount_nonnegative`');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` DROP CHECK `ck_payroll_adjustment_batches_status`');

        DB::statement('ALTER TABLE `payroll_adjustment_batches` DROP CHECK `ck_payroll_adjustment_batches_batch_type`');

        DB::statement('ALTER TABLE `payroll_actions` DROP CHECK `ck_payroll_actions_to_status`');

        DB::statement('ALTER TABLE `payroll_actions` DROP CHECK `ck_payroll_actions_from_status`');

        DB::statement('ALTER TABLE `payroll_actions` DROP CHECK `ck_payroll_actions_action`');

        DB::statement('ALTER TABLE `payroll_line_sources` DROP CHECK `ck_payroll_line_sources_teaching_evidence`');

        DB::statement('ALTER TABLE `payroll_line_sources` DROP CHECK `ck_payroll_line_sources_category_pair`');

        DB::statement('ALTER TABLE `payroll_line_sources` DROP CHECK `ck_payroll_line_sources_one_source`');

        DB::statement('ALTER TABLE `payroll_line_sources` DROP CHECK `ck_payroll_line_sources_amount_snapshot_nonnegative`');

        DB::statement('ALTER TABLE `payroll_line_sources` DROP CHECK `ck_payroll_line_sources_rate_snapshot_nonnegative`');

        DB::statement('ALTER TABLE `payroll_line_sources` DROP CHECK `ck_payroll_line_sources_quantity_snapshot_nonnegative`');

        DB::statement('ALTER TABLE `payroll_lines` DROP CHECK `ck_payroll_lines_amount_nonnegative`');

        DB::statement('ALTER TABLE `payroll_lines` DROP CHECK `ck_payroll_lines_rate_snapshot_nonnegative`');

        DB::statement('ALTER TABLE `payroll_lines` DROP CHECK `ck_payroll_lines_quantity_nonnegative`');

        DB::statement('ALTER TABLE `payroll_lines` DROP CHECK `ck_payroll_lines_direction`');

        DB::statement('ALTER TABLE `payroll_lines` DROP CHECK `ck_payroll_lines_component_type_snapshot`');

        DB::statement('ALTER TABLE `payroll_versions` DROP CHECK `ck_payroll_versions_separate_duties`');

        DB::statement('ALTER TABLE `payroll_versions` DROP CHECK `ck_payroll_versions_lock`');

        DB::statement('ALTER TABLE `payroll_versions` DROP CHECK `ck_payroll_versions_net`');

        DB::statement('ALTER TABLE `payroll_versions` DROP CHECK `ck_payroll_versions_deduction_total_nonnegative`');

        DB::statement('ALTER TABLE `payroll_versions` DROP CHECK `ck_payroll_versions_allowance_total_nonnegative`');

        DB::statement('ALTER TABLE `payroll_versions` DROP CHECK `ck_payroll_versions_teaching_total_nonnegative`');

        DB::statement('ALTER TABLE `payroll_versions` DROP CHECK `ck_payroll_versions_version_number_positive`');

        DB::statement('ALTER TABLE `payroll_versions` DROP CHECK `ck_payroll_versions_status`');

        DB::statement('ALTER TABLE `payrolls` DROP CHECK `ck_payrolls_payroll_type`');

        DB::statement('ALTER TABLE `honor_deductions` DROP CHECK `ck_honor_deductions_amount_positive`');

        DB::statement('ALTER TABLE `honor_deductions` DROP CHECK `ck_honor_deductions_reason_nonempty`');

        DB::statement('ALTER TABLE `honor_deductions` DROP CHECK `ck_honor_deductions_amount_nonnegative`');

        DB::statement('ALTER TABLE `honor_deductions` DROP CHECK `ck_honor_deductions_status`');

        DB::statement('ALTER TABLE `honor_periods` DROP CHECK `ck_honor_periods_date_range`');

        DB::statement('ALTER TABLE `honor_periods` DROP CHECK `ck_honor_periods_status`');

        DB::statement('ALTER TABLE `employee_additional_duties` DROP CHECK `ck_employee_additional_duties_one_assignment`');

        DB::statement('ALTER TABLE `employee_additional_duties` DROP CHECK `ck_employee_additional_duties_effective_range`');

        DB::statement('ALTER TABLE `honor_rates` DROP CHECK `ck_honor_rates_reason_nonempty`');

        DB::statement('ALTER TABLE `honor_rates` DROP CHECK `ck_honor_rates_effective_range`');

        DB::statement('ALTER TABLE `honor_rates` DROP CHECK `ck_honor_rates_amount_nonnegative`');

        DB::statement('ALTER TABLE `honor_rates` DROP CHECK `ck_honor_rates_unit`');

        DB::statement('ALTER TABLE `honor_rate_scopes` DROP CHECK `ck_honor_rate_scopes_exclusive_scope`');

        DB::statement('ALTER TABLE `honor_components` DROP CHECK `ck_honor_components_is_active`');

        DB::statement('ALTER TABLE `honor_components` DROP CHECK `ck_honor_components_calculation_method`');

        DB::statement('ALTER TABLE `honor_components` DROP CHECK `ck_honor_components_component_type`');

        DB::statement('ALTER TABLE `honor_policy_versions` DROP CHECK `ck_honor_policy_versions_effective_range`');

        DB::statement('ALTER TABLE `honor_policy_versions` DROP CHECK `ck_honor_policy_versions_jp_minutes_positive`');

        DB::statement('ALTER TABLE `honor_policy_versions` DROP CHECK `ck_honor_policy_versions_version_number_positive`');

        DB::statement('ALTER TABLE `honor_policy_versions` DROP CHECK `ck_honor_policy_versions_status`');

        DB::statement('ALTER TABLE `honor_policy_versions` DROP CHECK `ck_honor_policy_versions_source_basis`');

        DB::statement('ALTER TABLE `attendance_corrections` DROP CHECK `ck_attendance_corrections_different_approver`');

        DB::statement('ALTER TABLE `attendance_corrections` DROP CHECK `ck_attendance_corrections_one_target`');

        DB::statement('ALTER TABLE `attendance_corrections` DROP CHECK `ck_attendance_corrections_reason_nonempty`');

        DB::statement('ALTER TABLE `attendance_corrections` DROP CHECK `ck_attendance_corrections_status`');

        DB::statement('ALTER TABLE `student_daily_attendances` DROP CHECK `ck_student_daily_attendances_source_type`');

        DB::statement('ALTER TABLE `attendance_policies` DROP CHECK `ck_attendance_policies_both_rule`');

        DB::statement('ALTER TABLE `attendance_policies` DROP CHECK `ck_attendance_policies_effective_range`');

        DB::statement('ALTER TABLE `attendance_policies` DROP CHECK `ck_attendance_policies_allow_late_correction_with_mandate`');

        DB::statement('ALTER TABLE `attendance_policies` DROP CHECK `ck_attendance_policies_requires_correction_approval`');

        DB::statement('ALTER TABLE `attendance_policies` DROP CHECK `ck_attendance_policies_version_number_positive`');

        DB::statement('ALTER TABLE `attendance_policies` DROP CHECK `ck_attendance_policies_status`');

        DB::statement('ALTER TABLE `attendance_policies` DROP CHECK `ck_attendance_policies_mode`');

        DB::statement('ALTER TABLE `employee_attendances` DROP CHECK `ck_employee_attendances_check_out`');

        DB::statement('ALTER TABLE `employee_attendances` DROP CHECK `ck_employee_attendances_entry_mode`');

        DB::statement('ALTER TABLE `employee_work_days` DROP CHECK `ck_employee_work_days_time_range`');

        DB::statement('ALTER TABLE `employee_work_days` DROP CHECK `ck_employee_work_days_is_work_required`');

        DB::statement('ALTER TABLE `employee_work_days` DROP CHECK `ck_employee_work_days_shift_number_positive`');

        DB::statement('ALTER TABLE `employee_work_assignments` DROP CHECK `ck_employee_work_assignments_effective_range`');

        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_employee_attendance_policies_effective_range`');

        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_employee_attendance_policies_allow_late_correcti_4a0077119d69`');

        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_employee_attendance_policies_requires_correction_approval`');

        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_employee_attendance_policies_version_number_positive`');

        DB::statement('ALTER TABLE `employee_attendance_policies` DROP CHECK `ck_employee_attendance_policies_status`');

        DB::statement('ALTER TABLE `work_pattern_versions` DROP CHECK `ck_work_pattern_versions_time_range`');

        DB::statement('ALTER TABLE `work_pattern_versions` DROP CHECK `ck_work_pattern_versions_weekday`');

        DB::statement('ALTER TABLE `work_pattern_versions` DROP CHECK `ck_work_pattern_versions_effective_range`');

        DB::statement('ALTER TABLE `work_pattern_versions` DROP CHECK `ck_work_pattern_versions_crosses_midnight`');

        DB::statement('ALTER TABLE `work_pattern_versions` DROP CHECK `ck_work_pattern_versions_version_number_positive`');

        DB::statement('ALTER TABLE `work_patterns` DROP CHECK `ck_work_patterns_is_active`');

        DB::statement('ALTER TABLE `attendance_statuses` DROP CHECK `ck_attendance_statuses_is_active`');

        DB::statement('ALTER TABLE `attendance_statuses` DROP CHECK `ck_attendance_statuses_domain`');

        DB::statement('ALTER TABLE `realization_verifications` DROP CHECK `ck_realization_verifications_reject_reason`');

        DB::statement('ALTER TABLE `realization_verifications` DROP CHECK `ck_realization_verifications_action`');

        DB::statement('ALTER TABLE `realization_revisions` DROP CHECK `ck_realization_revisions_actual_time`');

        DB::statement('ALTER TABLE `realization_revisions` DROP CHECK `ck_realization_revisions_nonexecution`');

        DB::statement('ALTER TABLE `realization_revisions` DROP CHECK `ck_realization_revisions_actual_jp_nonnegative`');

        DB::statement('ALTER TABLE `realization_revisions` DROP CHECK `ck_realization_revisions_revision_number_positive`');

        DB::statement('ALTER TABLE `realization_revisions` DROP CHECK `ck_realization_revisions_verification_status`');

        DB::statement('ALTER TABLE `realization_revisions` DROP CHECK `ck_realization_revisions_execution_status`');

        DB::statement('ALTER TABLE `substitute_assignments` DROP CHECK `ck_substitute_assignments_reason_nonempty`');

        DB::statement('ALTER TABLE `meetings` DROP CHECK `ck_meetings_cancellation_reason`');

        DB::statement('ALTER TABLE `meetings` DROP CHECK `ck_meetings_planned_positive`');

        DB::statement('ALTER TABLE `meetings` DROP CHECK `ck_meetings_time_range`');

        DB::statement('ALTER TABLE `meetings` DROP CHECK `ck_meetings_is_cancelled`');

        DB::statement('ALTER TABLE `meetings` DROP CHECK `ck_meetings_planned_jp_snapshot_nonnegative`');

        DB::statement('ALTER TABLE `meetings` DROP CHECK `ck_meetings_occurrence_number_positive`');

        DB::statement('ALTER TABLE `schedule_entry_slots` DROP CHECK `ck_schedule_entry_slots_time_range`');

        DB::statement('ALTER TABLE `schedule_entry_slots` DROP CHECK `ck_schedule_entry_slots_jp_snapshot_nonnegative`');

        DB::statement('ALTER TABLE `schedule_entry_slots` DROP CHECK `ck_schedule_entry_slots_position_positive`');

        DB::statement('ALTER TABLE `schedule_entry_versions` DROP CHECK `ck_schedule_entry_versions_planned_positive`');

        DB::statement('ALTER TABLE `schedule_entry_versions` DROP CHECK `ck_schedule_entry_versions_time_range`');

        DB::statement('ALTER TABLE `schedule_entry_versions` DROP CHECK `ck_schedule_entry_versions_weekday`');

        DB::statement('ALTER TABLE `schedule_entry_versions` DROP CHECK `ck_schedule_entry_versions_effective_range`');

        DB::statement('ALTER TABLE `schedule_entry_versions` DROP CHECK `ck_schedule_entry_versions_planned_jp_nonnegative`');

        DB::statement('ALTER TABLE `timetable_versions` DROP CHECK `ck_timetable_versions_effective_range`');

        DB::statement('ALTER TABLE `timetable_versions` DROP CHECK `ck_timetable_versions_version_number_positive`');

        DB::statement('ALTER TABLE `timetable_versions` DROP CHECK `ck_timetable_versions_status`');

        DB::statement('ALTER TABLE `lesson_slots` DROP CHECK `ck_lesson_slots_break_jp`');

        DB::statement('ALTER TABLE `lesson_slots` DROP CHECK `ck_lesson_slots_time_range`');

        DB::statement('ALTER TABLE `lesson_slots` DROP CHECK `ck_lesson_slots_weekday`');

        DB::statement('ALTER TABLE `lesson_slots` DROP CHECK `ck_lesson_slots_is_active`');

        DB::statement('ALTER TABLE `lesson_slots` DROP CHECK `ck_lesson_slots_is_break`');

        DB::statement('ALTER TABLE `lesson_slots` DROP CHECK `ck_lesson_slots_jp_units_nonnegative`');

        DB::statement('ALTER TABLE `lesson_slots` DROP CHECK `ck_lesson_slots_sequence_positive`');

        DB::statement('ALTER TABLE `academic_calendar_events` DROP CHECK `ck_academic_calendar_events_date_range`');

        DB::statement('ALTER TABLE `academic_calendar_events` DROP CHECK `ck_academic_calendar_events_blocks_employee_work`');

        DB::statement('ALTER TABLE `academic_calendar_events` DROP CHECK `ck_academic_calendar_events_blocks_teaching`');

        DB::statement('ALTER TABLE `academic_calendar_events` DROP CHECK `ck_academic_calendar_events_event_type`');

        DB::statement('ALTER TABLE `student_transitions` DROP CHECK `ck_student_transitions_reason_nonempty`');

        DB::statement('ALTER TABLE `student_transitions` DROP CHECK `ck_student_transitions_type`');

        DB::statement('ALTER TABLE `student_enrollments` DROP CHECK `ck_student_enrollments_effective_range`');

        DB::statement('ALTER TABLE `student_enrollments` DROP CHECK `ck_student_enrollments_exit_reason`');

        DB::statement('ALTER TABLE `student_enrollments` DROP CHECK `ck_student_enrollments_entry_reason`');

        DB::statement('ALTER TABLE `class_periods` DROP CHECK `ck_class_periods_grade_snapshot`');

        DB::statement('ALTER TABLE `class_periods` DROP CHECK `ck_class_periods_status`');

        DB::statement('ALTER TABLE `classes` DROP CHECK `ck_classes_grade`');

        DB::statement('ALTER TABLE `classes` DROP CHECK `ck_classes_is_active`');

        DB::statement('ALTER TABLE `academic_contexts` DROP CHECK `ck_academic_contexts_active_pair`');

        DB::statement('ALTER TABLE `academic_contexts` DROP CHECK `ck_academic_contexts_singleton`');

        DB::statement('ALTER TABLE `semesters` DROP CHECK `ck_semesters_number`');

        DB::statement('ALTER TABLE `semesters` DROP CHECK `ck_semesters_date_range`');

        DB::statement('ALTER TABLE `semesters` DROP CHECK `ck_semesters_status`');

        DB::statement('ALTER TABLE `academic_years` DROP CHECK `ck_academic_years_date_range`');

        DB::statement('ALTER TABLE `academic_years` DROP CHECK `ck_academic_years_status`');

        DB::statement('ALTER TABLE `teaching_assignments` DROP CHECK `ck_teaching_assignments_effective_range`');

        DB::statement('ALTER TABLE `teaching_assignments` DROP CHECK `ck_teaching_assignments_is_active`');

        DB::statement('ALTER TABLE `teaching_assignments` DROP CHECK `ck_teaching_assignments_planned_weekly_jp_nonnegative`');

        DB::statement('ALTER TABLE `homeroom_assignments` DROP CHECK `ck_homeroom_assignments_effective_range`');

        DB::statement('ALTER TABLE `subjects` DROP CHECK `ck_subjects_is_active`');

        DB::statement('ALTER TABLE `employee_positions` DROP CHECK `ck_employee_positions_effective_range`');

        DB::statement('ALTER TABLE `positions` DROP CHECK `ck_positions_is_active`');

        DB::statement('ALTER TABLE `employee_category_assignments` DROP CHECK `ck_employee_category_assignments_reason_nonempty`');

        DB::statement('ALTER TABLE `employee_category_assignments` DROP CHECK `ck_employee_category_assignments_effective_range`');

        DB::statement('ALTER TABLE `employees` DROP CHECK `ck_employees_is_active`');

        DB::statement('ALTER TABLE `employee_categories` DROP CHECK `ck_employee_categories_is_active`');

        DB::statement('ALTER TABLE `student_guardians` DROP CHECK `ck_student_guardians_effective_range`');

        DB::statement('ALTER TABLE `student_guardians` DROP CHECK `ck_student_guardians_is_primary`');

        DB::statement('ALTER TABLE `student_guardians` DROP CHECK `ck_student_guardians_verification_status`');

        DB::statement('ALTER TABLE `student_guardians` DROP CHECK `ck_student_guardians_relationship`');

        DB::statement('ALTER TABLE `guardians` DROP CHECK `ck_guardians_is_active`');

        DB::statement('ALTER TABLE `student_statuses` DROP CHECK `ck_student_statuses_is_active`');

        DB::statement('ALTER TABLE `student_statuses` DROP CHECK `ck_student_statuses_is_terminal`');

        DB::statement('ALTER TABLE `files` DROP CHECK `ck_files_expiry`');

        DB::statement('ALTER TABLE `files` DROP CHECK `ck_files_scan_status`');

        DB::statement('ALTER TABLE `files` DROP CHECK `ck_files_classification`');

        DB::statement('ALTER TABLE `business_decisions` DROP CHECK `ck_business_decisions_effective_range`');

        DB::statement('ALTER TABLE `business_decisions` DROP CHECK `ck_business_decisions_version_positive`');

        DB::statement('ALTER TABLE `business_decisions` DROP CHECK `ck_business_decisions_status`');

        DB::statement('ALTER TABLE `authorization_mandates` DROP CHECK `ck_authorization_mandates_scope`');

        DB::statement('ALTER TABLE `authorization_mandates` DROP CHECK `ck_authorization_mandates_reason_nonempty`');

        DB::statement('ALTER TABLE `authorization_mandates` DROP CHECK `ck_authorization_mandates_effective_range`');

        DB::statement('ALTER TABLE `authorization_mandates` DROP CHECK `ck_authorization_mandates_scope_type`');

        DB::statement('ALTER TABLE `user_roles` DROP CHECK `ck_user_roles_effective_range`');

        DB::statement('ALTER TABLE `permissions` DROP CHECK `ck_permissions_is_sensitive`');

        DB::statement('ALTER TABLE `roles` DROP CHECK `ck_roles_is_active`');

        DB::statement('ALTER TABLE `users` DROP CHECK `ck_users_must_change_password`');

        DB::statement('ALTER TABLE `users` DROP CHECK `ck_users_status`');

        DB::statement('ALTER TABLE `persons` DROP CHECK `ck_persons_is_active`');

        DB::statement('ALTER TABLE `persons` DROP CHECK `ck_persons_sex`');

        DB::statement('ALTER TABLE `institutions` DROP CHECK `ck_institutions_singleton`');

        Schema::table('public_staff_profiles', function (Blueprint $table): void {
            $table->dropForeign('fk_public_staff_profiles_content_revision_id_photo__effa36ac1646');
            $table->dropIndex('ix_public_staff_profiles_content_revision_id_photo__4bcfadc3eb9f');
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_assets_content_revision_id_publication_action_id');
            $table->dropIndex('ix_publication_assets_content_revision_id_publication_action_id');
        });

        Schema::table('publication_actions', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_actions_content_id_content_revision_id');
            $table->dropIndex('ix_publication_actions_content_id_content_revision_id');
        });

        Schema::table('letter_dispositions', function (Blueprint $table): void {
            $table->dropForeign('fk_letter_dispositions_letter_id_parent_disposition_id');
            $table->dropIndex('ix_letter_dispositions_letter_id_parent_disposition_id');
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_files_case_id_summary_id');
            $table->dropIndex('ix_bk_files_case_id_summary_id');
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_files_case_id_followup_id');
            $table->dropIndex('ix_bk_files_case_id_followup_id');
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_files_case_id_note_id');
            $table->dropIndex('ix_bk_files_case_id_note_id');
        });

        Schema::table('bk_followups', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_followups_case_id_note_id');
            $table->dropIndex('ix_bk_followups_case_id_note_id');
        });

        Schema::table('bk_summaries', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_summaries_case_id_previous_summary_id');
            $table->dropIndex('ix_bk_summaries_case_id_previous_summary_id');
        });

        Schema::table('bk_notes', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_notes_case_id_previous_note_id');
            $table->dropIndex('ix_bk_notes_case_id_previous_note_id');
        });

        Schema::table('question_files', function (Blueprint $table): void {
            $table->dropForeign('fk_question_files_question_version_id_option_id');
            $table->dropIndex('ix_question_files_question_version_id_option_id');
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustments_batch_id_target_honor_period_id');
            $table->dropIndex('ix_payroll_adjustments_batch_id_target_honor_period_id');
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustments_employee_id_applied_line_source_id');
            $table->dropIndex('ix_payroll_adjustments_employee_id_applied_line_source_id');
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustments_original_payroll_version_id__19268d521dff');
            $table->dropIndex('ix_payroll_adjustments_original_payroll_version_id__7218198efda9');
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_source_claims_payroll_id_beneficiary_employee_id');
            $table->dropIndex('ix_payroll_source_claims_payroll_id_beneficiary_employee_id');
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_source_claims_beneficiary_employee_id_pa_832e2cd0c55d');
            $table->dropIndex('ix_payroll_source_claims_beneficiary_employee_id_pa_66301378da9d');
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_source_claims_meeting_id_payroll_line_source_id');
            $table->dropIndex('ix_payroll_source_claims_meeting_id_payroll_line_source_id');
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_source_claims_payroll_version_id_payroll_3746d23f430e');
            $table->dropIndex('ix_payroll_source_claims_payroll_version_id_payroll_10ec661e7eed');
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_source_claims_payroll_id_payroll_version_id');
            $table->dropIndex('ix_payroll_source_claims_payroll_id_payroll_version_id');
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_deductions_applied_payroll_id_employee_id__5a8e183c126c');
            $table->dropIndex('ix_honor_deductions_applied_payroll_id_employee_id__0092648fbe5f');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_category_assignment_id_bene_e850b4d81c2a');
            $table->dropIndex('ix_payroll_line_sources_category_assignment_id_bene_ccef166c6e36');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_payroll_version_id_payroll_line_id');
            $table->dropIndex('ix_payroll_line_sources_payroll_version_id_payroll_line_id');
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_versions_payroll_id_previous_version_id');
            $table->dropIndex('ix_payroll_versions_payroll_id_previous_version_id');
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_work_days_work_assignment_id_employee_id');
            $table->dropIndex('ix_employee_work_days_work_assignment_id_employee_id');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropForeign('fk_meetings_schedule_entry_id_schedule_entry_version_id');
            $table->dropIndex('ix_meetings_schedule_entry_id_schedule_entry_version_id');
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->dropForeign('fk_student_transitions_to_enrollment_id_student_id');
            $table->dropIndex('ix_student_transitions_to_enrollment_id_student_id');
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->dropForeign('fk_student_transitions_from_enrollment_id_student_id');
            $table->dropIndex('ix_student_transitions_from_enrollment_id_student_id');
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_meeting_attendances_enrollment_id_student_id');
            $table->dropIndex('ix_student_meeting_attendances_enrollment_id_student_id');
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_daily_attendances_enrollment_id_student__8a8dd63bc62d');
            $table->dropIndex('ix_student_daily_attendances_enrollment_id_student__a7e101b660dc');
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropForeign('fk_contents_id_published_revision_id');
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropForeign('fk_contents_id_current_revision_id');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign('fk_questions_id_current_version_id');
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropForeign('fk_payrolls_id_active_version_id');
        });

        Schema::table('teaching_realizations', function (Blueprint $table): void {
            $table->dropForeign('fk_teaching_realizations_id_current_revision_id');
        });

        Schema::table('academic_contexts', function (Blueprint $table): void {
            $table->dropForeign('fk_academic_contexts_active_academic_year_id_active_semester_id');
            $table->dropIndex('ix_academic_contexts_active_academic_year_id_active_semester_id');
        });

        Schema::table('system_events', function (Blueprint $table): void {
            $table->dropForeign('fk_system_events_acknowledged_by');
        });

        Schema::table('restore_runs', function (Blueprint $table): void {
            $table->dropForeign('fk_restore_runs_performed_by');
        });

        Schema::table('restore_runs', function (Blueprint $table): void {
            $table->dropForeign('fk_restore_runs_evidence_file_id');
        });

        Schema::table('restore_runs', function (Blueprint $table): void {
            $table->dropForeign('fk_restore_runs_backup_run_id');
        });

        Schema::table('backup_runs', function (Blueprint $table): void {
            $table->dropForeign('fk_backup_runs_initiated_by');
        });

        Schema::table('backup_runs', function (Blueprint $table): void {
            $table->dropForeign('fk_backup_runs_retention_policy_id');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropForeign('fk_audit_logs_actor_user_id');
        });

        Schema::table('retention_policies', function (Blueprint $table): void {
            $table->dropForeign('fk_retention_policies_approved_by');
        });

        Schema::table('retention_policies', function (Blueprint $table): void {
            $table->dropForeign('fk_retention_policies_decision_id');
        });

        Schema::table('idempotency_requests', function (Blueprint $table): void {
            $table->dropForeign('fk_idempotency_requests_actor_user_id');
        });

        Schema::table('import_rows', function (Blueprint $table): void {
            $table->dropForeign('fk_import_rows_source_record_id');
        });

        Schema::table('import_rows', function (Blueprint $table): void {
            $table->dropForeign('fk_import_rows_import_batch_id');
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->dropForeign('fk_source_records_last_batch_id');
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->dropForeign('fk_source_records_class_id');
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->dropForeign('fk_source_records_subject_id');
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->dropForeign('fk_source_records_employee_id');
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->dropForeign('fk_source_records_guardian_id');
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->dropForeign('fk_source_records_student_id');
        });

        Schema::table('source_records', function (Blueprint $table): void {
            $table->dropForeign('fk_source_records_person_id');
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_import_batches_error_report_file_id');
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_import_batches_confirmed_by');
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_import_batches_source_file_id');
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_import_batches_requested_by');
        });

        Schema::table('report_dataset_rows', function (Blueprint $table): void {
            $table->dropForeign('fk_report_dataset_rows_report_job_id');
        });

        Schema::table('report_jobs', function (Blueprint $table): void {
            $table->dropForeign('fk_report_jobs_output_file_id');
        });

        Schema::table('report_jobs', function (Blueprint $table): void {
            $table->dropForeign('fk_report_jobs_requested_by');
        });

        Schema::table('portal_announcement_files', function (Blueprint $table): void {
            $table->dropForeign('fk_portal_announcement_files_created_by');
        });

        Schema::table('portal_announcement_files', function (Blueprint $table): void {
            $table->dropForeign('fk_portal_announcement_files_file_id');
        });

        Schema::table('portal_announcement_files', function (Blueprint $table): void {
            $table->dropForeign('fk_portal_announcement_files_announcement_id');
        });

        Schema::table('portal_announcement_targets', function (Blueprint $table): void {
            $table->dropForeign('fk_portal_announcement_targets_student_id');
        });

        Schema::table('portal_announcement_targets', function (Blueprint $table): void {
            $table->dropForeign('fk_portal_announcement_targets_class_period_id');
        });

        Schema::table('portal_announcement_targets', function (Blueprint $table): void {
            $table->dropForeign('fk_portal_announcement_targets_announcement_id');
        });

        Schema::table('portal_announcements', function (Blueprint $table): void {
            $table->dropForeign('fk_portal_announcements_created_by');
        });

        Schema::table('portal_announcements', function (Blueprint $table): void {
            $table->dropForeign('fk_portal_announcements_published_by');
        });

        Schema::table('public_staff_profiles', function (Blueprint $table): void {
            $table->dropForeign('fk_public_staff_profiles_approved_by');
        });

        Schema::table('public_staff_profiles', function (Blueprint $table): void {
            $table->dropForeign('fk_public_staff_profiles_content_revision_id');
        });

        Schema::table('public_staff_profiles', function (Blueprint $table): void {
            $table->dropForeign('fk_public_staff_profiles_employee_id');
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_assets_approved_by');
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_assets_source_document_id');
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_assets_public_file_id');
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_assets_source_file_id');
        });

        Schema::table('publication_assets', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_assets_content_revision_id');
        });

        Schema::table('publication_actions', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_actions_mandate_id');
        });

        Schema::table('publication_actions', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_actions_actor_user_id');
        });

        Schema::table('publication_actions', function (Blueprint $table): void {
            $table->dropForeign('fk_publication_actions_content_id');
        });

        Schema::table('content_files', function (Blueprint $table): void {
            $table->dropForeign('fk_content_files_created_by');
        });

        Schema::table('content_files', function (Blueprint $table): void {
            $table->dropForeign('fk_content_files_file_id');
        });

        Schema::table('content_files', function (Blueprint $table): void {
            $table->dropForeign('fk_content_files_content_revision_id');
        });

        Schema::table('content_revisions', function (Blueprint $table): void {
            $table->dropForeign('fk_content_revisions_created_by');
        });

        Schema::table('content_revisions', function (Blueprint $table): void {
            $table->dropForeign('fk_content_revisions_content_id');
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropForeign('fk_contents_created_by');
        });

        Schema::table('contents', function (Blueprint $table): void {
            $table->dropForeign('fk_contents_category_id');
        });

        Schema::table('disposition_actions', function (Blueprint $table): void {
            $table->dropForeign('fk_disposition_actions_actor_user_id');
        });

        Schema::table('disposition_actions', function (Blueprint $table): void {
            $table->dropForeign('fk_disposition_actions_disposition_id');
        });

        Schema::table('letter_dispositions', function (Blueprint $table): void {
            $table->dropForeign('fk_letter_dispositions_assigned_by');
        });

        Schema::table('letter_dispositions', function (Blueprint $table): void {
            $table->dropForeign('fk_letter_dispositions_assigned_to_user_id');
        });

        Schema::table('letter_dispositions', function (Blueprint $table): void {
            $table->dropForeign('fk_letter_dispositions_letter_id');
        });

        Schema::table('document_files', function (Blueprint $table): void {
            $table->dropForeign('fk_document_files_created_by');
        });

        Schema::table('document_files', function (Blueprint $table): void {
            $table->dropForeign('fk_document_files_file_id');
        });

        Schema::table('document_files', function (Blueprint $table): void {
            $table->dropForeign('fk_document_files_document_id');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign('fk_documents_created_by');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign('fk_documents_retention_policy_id');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign('fk_documents_letter_id');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign('fk_documents_category_id');
        });

        Schema::table('letter_agendas', function (Blueprint $table): void {
            $table->dropForeign('fk_letter_agendas_recorded_by');
        });

        Schema::table('letter_agendas', function (Blueprint $table): void {
            $table->dropForeign('fk_letter_agendas_agenda_sequence_id');
        });

        Schema::table('letter_agendas', function (Blueprint $table): void {
            $table->dropForeign('fk_letter_agendas_letter_id');
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->dropForeign('fk_letters_created_by');
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->dropForeign('fk_letters_issued_by');
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->dropForeign('fk_letters_number_sequence_id');
        });

        Schema::table('letters', function (Blueprint $table): void {
            $table->dropForeign('fk_letters_category_id');
        });

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->dropForeign('fk_letter_number_sequences_created_by');
        });

        Schema::table('letter_number_sequences', function (Blueprint $table): void {
            $table->dropForeign('fk_letter_number_sequences_decision_id');
        });

        Schema::table('document_category_grants', function (Blueprint $table): void {
            $table->dropForeign('fk_document_category_grants_granted_by');
        });

        Schema::table('document_category_grants', function (Blueprint $table): void {
            $table->dropForeign('fk_document_category_grants_category_id');
        });

        Schema::table('document_category_grants', function (Blueprint $table): void {
            $table->dropForeign('fk_document_category_grants_user_id');
        });

        Schema::table('document_categories', function (Blueprint $table): void {
            $table->dropForeign('fk_document_categories_retention_policy_id');
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_files_created_by');
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_files_file_id');
        });

        Schema::table('bk_files', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_files_case_id');
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_summary_recipients_shared_by');
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_summary_recipients_homeroom_assignment_id');
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_summary_recipients_student_guardian_id');
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_summary_recipients_recipient_user_id');
        });

        Schema::table('bk_summary_recipients', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_summary_recipients_summary_id');
        });

        Schema::table('bk_summaries', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_summaries_created_by');
        });

        Schema::table('bk_summaries', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_summaries_approved_by');
        });

        Schema::table('bk_summaries', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_summaries_case_id');
        });

        Schema::table('bk_followups', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_followups_created_by');
        });

        Schema::table('bk_followups', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_followups_assigned_employee_id');
        });

        Schema::table('bk_followups', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_followups_case_id');
        });

        Schema::table('bk_notes', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_notes_created_by');
        });

        Schema::table('bk_notes', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_notes_case_id');
        });

        Schema::table('bk_cases', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_cases_created_by');
        });

        Schema::table('bk_cases', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_cases_assigned_employee_id');
        });

        Schema::table('bk_cases', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_cases_category_id');
        });

        Schema::table('bk_cases', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_cases_student_id');
        });

        Schema::table('bk_category_grants', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_category_grants_granted_by');
        });

        Schema::table('bk_category_grants', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_category_grants_category_id');
        });

        Schema::table('bk_category_grants', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_category_grants_user_id');
        });

        Schema::table('bk_categories', function (Blueprint $table): void {
            $table->dropForeign('fk_bk_categories_retention_policy_id');
        });

        Schema::table('exam_package_files', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_package_files_created_by');
        });

        Schema::table('exam_package_files', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_package_files_file_id');
        });

        Schema::table('exam_package_files', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_package_files_exam_package_id');
        });

        Schema::table('exam_package_keys', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_package_keys_package_item_id');
        });

        Schema::table('exam_package_options', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_package_options_source_option_id');
        });

        Schema::table('exam_package_options', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_package_options_package_item_id');
        });

        Schema::table('exam_package_items', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_package_items_question_version_id');
        });

        Schema::table('exam_package_items', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_package_items_exam_package_id');
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_packages_created_by');
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_packages_supersedes_package_id');
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_packages_finalized_by');
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_packages_owner_employee_id');
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_packages_semester_id');
        });

        Schema::table('exam_packages', function (Blueprint $table): void {
            $table->dropForeign('fk_exam_packages_subject_id');
        });

        Schema::table('question_collaborators', function (Blueprint $table): void {
            $table->dropForeign('fk_question_collaborators_granted_by');
        });

        Schema::table('question_collaborators', function (Blueprint $table): void {
            $table->dropForeign('fk_question_collaborators_employee_id');
        });

        Schema::table('question_collaborators', function (Blueprint $table): void {
            $table->dropForeign('fk_question_collaborators_question_id');
        });

        Schema::table('question_files', function (Blueprint $table): void {
            $table->dropForeign('fk_question_files_created_by');
        });

        Schema::table('question_files', function (Blueprint $table): void {
            $table->dropForeign('fk_question_files_file_id');
        });

        Schema::table('question_files', function (Blueprint $table): void {
            $table->dropForeign('fk_question_files_question_version_id');
        });

        Schema::table('question_keys', function (Blueprint $table): void {
            $table->dropForeign('fk_question_keys_created_by');
        });

        Schema::table('question_keys', function (Blueprint $table): void {
            $table->dropForeign('fk_question_keys_question_version_id');
        });

        Schema::table('question_options', function (Blueprint $table): void {
            $table->dropForeign('fk_question_options_question_version_id');
        });

        Schema::table('question_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_question_versions_created_by');
        });

        Schema::table('question_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_question_versions_topic_id');
        });

        Schema::table('question_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_question_versions_question_type_id');
        });

        Schema::table('question_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_question_versions_question_id');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign('fk_questions_created_by');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign('fk_questions_subject_id');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign('fk_questions_owner_employee_id');
        });

        Schema::table('subject_topics', function (Blueprint $table): void {
            $table->dropForeign('fk_subject_topics_subject_id');
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustments_approved_by');
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustments_requested_by');
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustments_correction_realization_revision_id');
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustments_employee_id');
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustments_target_honor_period_id');
        });

        Schema::table('payroll_adjustments', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustments_original_payroll_version_id');
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustment_batches_applied_by');
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustment_batches_approved_by');
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustment_batches_reviewed_by');
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustment_batches_requested_by');
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustment_batches_target_honor_period_id');
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustment_batches_to_employee_id');
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustment_batches_from_employee_id');
        });

        Schema::table('payroll_adjustment_batches', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_adjustment_batches_original_line_source_id');
        });

        Schema::table('payroll_actions', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_actions_mandate_id');
        });

        Schema::table('payroll_actions', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_actions_actor_user_id');
        });

        Schema::table('payroll_actions', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_actions_payroll_version_id');
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_source_claims_claimed_by');
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_source_claims_beneficiary_employee_id');
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_source_claims_payroll_id');
        });

        Schema::table('payroll_source_claims', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_source_claims_meeting_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_employee_category_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_beneficiary_employee_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_actual_employee_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_scheduled_employee_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_policy_version_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_rate_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_adjustment_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_deduction_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_additional_duty_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_verification_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_realization_revision_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_meeting_id');
        });

        Schema::table('payroll_line_sources', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_line_sources_payroll_version_id');
        });

        Schema::table('payroll_lines', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_lines_component_id');
        });

        Schema::table('payroll_lines', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_lines_payroll_version_id');
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_versions_approved_by');
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_versions_reviewed_by');
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_versions_calculated_by');
        });

        Schema::table('payroll_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_payroll_versions_payroll_id');
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropForeign('fk_payrolls_created_by');
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropForeign('fk_payrolls_honor_period_id');
        });

        Schema::table('payrolls', function (Blueprint $table): void {
            $table->dropForeign('fk_payrolls_employee_id');
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_deductions_approved_by');
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_deductions_requested_by');
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_deductions_component_id');
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_deductions_honor_period_id');
        });

        Schema::table('honor_deductions', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_deductions_employee_id');
        });

        Schema::table('honor_periods', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_periods_created_by');
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_additional_duties_created_by');
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_additional_duties_approved_by');
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_additional_duties_appointment_file_id');
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_additional_duties_homeroom_assignment_id');
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_additional_duties_position_assignment_id');
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_additional_duties_component_id');
        });

        Schema::table('employee_additional_duties', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_additional_duties_employee_id');
        });

        Schema::table('honor_rates', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_rates_created_by');
        });

        Schema::table('honor_rates', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_rates_approved_by');
        });

        Schema::table('honor_rates', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_rates_policy_version_id');
        });

        Schema::table('honor_rates', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_rates_rate_scope_id');
        });

        Schema::table('honor_rate_scopes', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_rate_scopes_created_by');
        });

        Schema::table('honor_rate_scopes', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_rate_scopes_employee_category_id');
        });

        Schema::table('honor_rate_scopes', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_rate_scopes_employee_id');
        });

        Schema::table('honor_rate_scopes', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_rate_scopes_component_id');
        });

        Schema::table('honor_policy_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_policy_versions_created_by');
        });

        Schema::table('honor_policy_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_policy_versions_approved_by');
        });

        Schema::table('honor_policy_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_honor_policy_versions_decision_id');
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->dropForeign('fk_attendance_corrections_approved_by');
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->dropForeign('fk_attendance_corrections_requested_by');
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->dropForeign('fk_attendance_corrections_student_meeting_attendance_id');
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->dropForeign('fk_attendance_corrections_student_daily_attendance_id');
        });

        Schema::table('attendance_corrections', function (Blueprint $table): void {
            $table->dropForeign('fk_attendance_corrections_employee_attendance_id');
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_meeting_attendances_recorded_by');
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_meeting_attendances_policy_id');
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_meeting_attendances_attendance_status_id');
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_meeting_attendances_meeting_id');
        });

        Schema::table('student_meeting_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_meeting_attendances_student_id');
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_daily_attendances_recorded_by');
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_daily_attendances_policy_id');
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_daily_attendances_attendance_status_id');
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_daily_attendances_class_period_id');
        });

        Schema::table('student_daily_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_student_daily_attendances_student_id');
        });

        Schema::table('attendance_policies', function (Blueprint $table): void {
            $table->dropForeign('fk_attendance_policies_approved_by');
        });

        Schema::table('attendance_policies', function (Blueprint $table): void {
            $table->dropForeign('fk_attendance_policies_decision_id');
        });

        Schema::table('attendance_policies', function (Blueprint $table): void {
            $table->dropForeign('fk_attendance_policies_semester_id');
        });

        Schema::table('employee_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_attendances_recorded_by');
        });

        Schema::table('employee_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_attendances_evidence_file_id');
        });

        Schema::table('employee_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_attendances_attendance_status_id');
        });

        Schema::table('employee_attendances', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_attendances_work_day_id');
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_work_days_calendar_event_id');
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_work_days_policy_id');
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_work_days_work_pattern_version_id');
        });

        Schema::table('employee_work_days', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_work_days_employee_id');
        });

        Schema::table('employee_work_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_work_assignments_created_by');
        });

        Schema::table('employee_work_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_work_assignments_work_pattern_id');
        });

        Schema::table('employee_work_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_work_assignments_employee_id');
        });

        Schema::table('employee_attendance_policies', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_attendance_policies_approved_by');
        });

        Schema::table('employee_attendance_policies', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_attendance_policies_decision_id');
        });

        Schema::table('work_pattern_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_work_pattern_versions_created_by');
        });

        Schema::table('work_pattern_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_work_pattern_versions_decision_id');
        });

        Schema::table('work_pattern_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_work_pattern_versions_work_pattern_id');
        });

        Schema::table('realization_verifications', function (Blueprint $table): void {
            $table->dropForeign('fk_realization_verifications_mandate_id');
        });

        Schema::table('realization_verifications', function (Blueprint $table): void {
            $table->dropForeign('fk_realization_verifications_verifier_user_id');
        });

        Schema::table('realization_verifications', function (Blueprint $table): void {
            $table->dropForeign('fk_realization_verifications_realization_revision_id');
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->dropForeign('fk_realization_revisions_created_by');
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->dropForeign('fk_realization_revisions_submitted_by');
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->dropForeign('fk_realization_revisions_substitute_assignment_id');
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->dropForeign('fk_realization_revisions_actual_employee_id');
        });

        Schema::table('realization_revisions', function (Blueprint $table): void {
            $table->dropForeign('fk_realization_revisions_realization_id');
        });

        Schema::table('teaching_realizations', function (Blueprint $table): void {
            $table->dropForeign('fk_teaching_realizations_created_by');
        });

        Schema::table('teaching_realizations', function (Blueprint $table): void {
            $table->dropForeign('fk_teaching_realizations_meeting_id');
        });

        Schema::table('substitute_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_substitute_assignments_revoked_by');
        });

        Schema::table('substitute_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_substitute_assignments_assigned_by');
        });

        Schema::table('substitute_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_substitute_assignments_substitute_employee_id');
        });

        Schema::table('substitute_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_substitute_assignments_meeting_id');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropForeign('fk_meetings_created_by');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropForeign('fk_meetings_rescheduled_from_id');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropForeign('fk_meetings_calendar_event_id');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropForeign('fk_meetings_scheduled_employee_id');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropForeign('fk_meetings_subject_id');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropForeign('fk_meetings_class_period_id');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropForeign('fk_meetings_semester_id');
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropForeign('fk_meetings_schedule_entry_id');
        });

        Schema::table('schedule_entry_slots', function (Blueprint $table): void {
            $table->dropForeign('fk_schedule_entry_slots_lesson_slot_id');
        });

        Schema::table('schedule_entry_slots', function (Blueprint $table): void {
            $table->dropForeign('fk_schedule_entry_slots_schedule_entry_version_id');
        });

        Schema::table('schedule_entry_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_schedule_entry_versions_teaching_assignment_id');
        });

        Schema::table('schedule_entry_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_schedule_entry_versions_timetable_version_id');
        });

        Schema::table('schedule_entry_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_schedule_entry_versions_schedule_entry_id');
        });

        Schema::table('schedule_entries', function (Blueprint $table): void {
            $table->dropForeign('fk_schedule_entries_created_by');
        });

        Schema::table('schedule_entries', function (Blueprint $table): void {
            $table->dropForeign('fk_schedule_entries_timetable_id');
        });

        Schema::table('timetable_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_timetable_versions_created_by');
        });

        Schema::table('timetable_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_timetable_versions_published_by');
        });

        Schema::table('timetable_versions', function (Blueprint $table): void {
            $table->dropForeign('fk_timetable_versions_timetable_id');
        });

        Schema::table('timetables', function (Blueprint $table): void {
            $table->dropForeign('fk_timetables_created_by');
        });

        Schema::table('timetables', function (Blueprint $table): void {
            $table->dropForeign('fk_timetables_semester_id');
        });

        Schema::table('lesson_slots', function (Blueprint $table): void {
            $table->dropForeign('fk_lesson_slots_semester_id');
        });

        Schema::table('academic_calendar_events', function (Blueprint $table): void {
            $table->dropForeign('fk_academic_calendar_events_created_by');
        });

        Schema::table('academic_calendar_events', function (Blueprint $table): void {
            $table->dropForeign('fk_academic_calendar_events_class_period_id');
        });

        Schema::table('academic_calendar_events', function (Blueprint $table): void {
            $table->dropForeign('fk_academic_calendar_events_semester_id');
        });

        Schema::table('academic_calendar_events', function (Blueprint $table): void {
            $table->dropForeign('fk_academic_calendar_events_academic_year_id');
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->dropForeign('fk_student_transitions_confirmed_by');
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->dropForeign('fk_student_transitions_to_status_id');
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->dropForeign('fk_student_transitions_from_status_id');
        });

        Schema::table('student_transitions', function (Blueprint $table): void {
            $table->dropForeign('fk_student_transitions_student_id');
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropForeign('fk_student_enrollments_created_by');
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropForeign('fk_student_enrollments_class_period_id');
        });

        Schema::table('student_enrollments', function (Blueprint $table): void {
            $table->dropForeign('fk_student_enrollments_student_id');
        });

        Schema::table('class_periods', function (Blueprint $table): void {
            $table->dropForeign('fk_class_periods_academic_year_id');
        });

        Schema::table('class_periods', function (Blueprint $table): void {
            $table->dropForeign('fk_class_periods_class_id');
        });

        Schema::table('academic_contexts', function (Blueprint $table): void {
            $table->dropForeign('fk_academic_contexts_changed_by');
        });

        Schema::table('academic_contexts', function (Blueprint $table): void {
            $table->dropForeign('fk_academic_contexts_active_academic_year_id');
        });

        Schema::table('semesters', function (Blueprint $table): void {
            $table->dropForeign('fk_semesters_created_by');
        });

        Schema::table('semesters', function (Blueprint $table): void {
            $table->dropForeign('fk_semesters_academic_year_id');
        });

        Schema::table('academic_years', function (Blueprint $table): void {
            $table->dropForeign('fk_academic_years_created_by');
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_teaching_assignments_created_by');
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_teaching_assignments_semester_id');
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_teaching_assignments_class_period_id');
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_teaching_assignments_subject_id');
        });

        Schema::table('teaching_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_teaching_assignments_employee_id');
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_homeroom_assignments_created_by');
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_homeroom_assignments_appointment_file_id');
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_homeroom_assignments_semester_id');
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_homeroom_assignments_class_period_id');
        });

        Schema::table('homeroom_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_homeroom_assignments_employee_id');
        });

        Schema::table('employee_positions', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_positions_created_by');
        });

        Schema::table('employee_positions', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_positions_appointment_file_id');
        });

        Schema::table('employee_positions', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_positions_position_id');
        });

        Schema::table('employee_positions', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_positions_employee_id');
        });

        Schema::table('employee_category_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_category_assignments_created_by');
        });

        Schema::table('employee_category_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_category_assignments_category_id');
        });

        Schema::table('employee_category_assignments', function (Blueprint $table): void {
            $table->dropForeign('fk_employee_category_assignments_employee_id');
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->dropForeign('fk_employees_person_id');
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->dropForeign('fk_student_guardians_created_by');
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->dropForeign('fk_student_guardians_revoked_by');
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->dropForeign('fk_student_guardians_verification_evidence_file_id');
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->dropForeign('fk_student_guardians_verified_by');
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->dropForeign('fk_student_guardians_guardian_id');
        });

        Schema::table('student_guardians', function (Blueprint $table): void {
            $table->dropForeign('fk_student_guardians_student_id');
        });

        Schema::table('guardians', function (Blueprint $table): void {
            $table->dropForeign('fk_guardians_person_id');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropForeign('fk_students_status_id');
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropForeign('fk_students_person_id');
        });

        Schema::table('files', function (Blueprint $table): void {
            $table->dropForeign('fk_files_retention_policy_id');
        });

        Schema::table('files', function (Blueprint $table): void {
            $table->dropForeign('fk_files_uploaded_by');
        });

        Schema::table('system_settings', function (Blueprint $table): void {
            $table->dropForeign('fk_system_settings_updated_by');
        });

        Schema::table('business_decisions', function (Blueprint $table): void {
            $table->dropForeign('fk_business_decisions_supersedes_id');
        });

        Schema::table('business_decisions', function (Blueprint $table): void {
            $table->dropForeign('fk_business_decisions_approved_by');
        });

        Schema::table('business_decisions', function (Blueprint $table): void {
            $table->dropForeign('fk_business_decisions_owner_user_id');
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->dropForeign('fk_authorization_mandates_revoked_by');
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->dropForeign('fk_authorization_mandates_granted_by');
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->dropForeign('fk_authorization_mandates_employee_id');
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->dropForeign('fk_authorization_mandates_class_period_id');
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->dropForeign('fk_authorization_mandates_semester_id');
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->dropForeign('fk_authorization_mandates_permission_id');
        });

        Schema::table('authorization_mandates', function (Blueprint $table): void {
            $table->dropForeign('fk_authorization_mandates_user_id');
        });

        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->dropForeign('fk_role_permissions_created_by');
        });

        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->dropForeign('fk_role_permissions_permission_id');
        });

        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->dropForeign('fk_role_permissions_role_id');
        });

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropForeign('fk_user_roles_revoked_by');
        });

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropForeign('fk_user_roles_granted_by');
        });

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropForeign('fk_user_roles_role_id');
        });

        Schema::table('user_roles', function (Blueprint $table): void {
            $table->dropForeign('fk_user_roles_user_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign('fk_users_person_id');
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropForeign('fk_institutions_logo_file_id');
        });
    }
};
