<?php

use App\Http\Controllers\Api\V1\AcademicAssignmentController;
use App\Http\Controllers\Api\V1\AcademicCalendarController;
use App\Http\Controllers\Api\V1\AcademicClassController;
use App\Http\Controllers\Api\V1\AcademicContextController;
use App\Http\Controllers\Api\V1\AcademicScheduleController;
use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AccountRoleController;
use App\Http\Controllers\Api\V1\AccountUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\GoogleAuthController;
use App\Http\Controllers\Api\V1\MasterDataController;
use App\Http\Controllers\Api\V1\MasterImportController;
use App\Http\Controllers\Api\V1\MasterRelationController;
use App\Http\Middleware\RequirePasswordChanged;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::prefix('auth')->name('auth.')->group(function (): void {
        Route::get('options', [AuthController::class, 'options'])->middleware('throttle:60,1')->name('options');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth.login')->name('login');
        Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:auth.refresh')->name('refresh');

        Route::middleware(['web', 'throttle:auth.google'])->group(function (): void {
            Route::get('google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
            Route::get('google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
        });

        Route::middleware(['auth:api', RequirePasswordChanged::class])->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
            Route::put('password', [AuthController::class, 'changePassword'])->middleware('throttle:auth.password')->name('password.update');
        });
    });
});

Route::prefix('v1/academic')->name('api.v1.academic.')->middleware(['auth:api', RequirePasswordChanged::class, 'throttle:120,1'])->group(function (): void {
    Route::get('context', [AcademicContextController::class, 'show'])->name('context.show');
    Route::put('context', [AcademicContextController::class, 'update'])->name('context.update');

    Route::get('years', [AcademicYearController::class, 'indexYears'])->name('years.index');
    Route::post('years', [AcademicYearController::class, 'storeYear'])->name('years.store');
    Route::get('years/{year}', [AcademicYearController::class, 'showYear'])->whereNumber('year')->name('years.show');
    Route::put('years/{year}', [AcademicYearController::class, 'updateYear'])->whereNumber('year')->name('years.update');
    Route::get('semesters', [AcademicYearController::class, 'indexSemesters'])->name('semesters.index');
    Route::post('semesters', [AcademicYearController::class, 'storeSemester'])->name('semesters.store');
    Route::get('semesters/{semester}', [AcademicYearController::class, 'showSemester'])->whereNumber('semester')->name('semesters.show');
    Route::put('semesters/{semester}', [AcademicYearController::class, 'updateSemester'])->whereNumber('semester')->name('semesters.update');

    Route::get('classes', [AcademicClassController::class, 'indexClasses'])->name('classes.index');
    Route::post('classes', [AcademicClassController::class, 'storeClass'])->name('classes.store');
    Route::get('classes/{class}', [AcademicClassController::class, 'showClass'])->whereNumber('class')->name('classes.show');
    Route::put('classes/{class}', [AcademicClassController::class, 'updateClass'])->whereNumber('class')->name('classes.update');
    Route::get('class-periods', [AcademicClassController::class, 'indexPeriods'])->name('class-periods.index');
    Route::post('class-periods', [AcademicClassController::class, 'storePeriod'])->name('class-periods.store');
    Route::get('class-periods/{period}', [AcademicClassController::class, 'showPeriod'])->whereNumber('period')->name('class-periods.show');
    Route::put('class-periods/{period}', [AcademicClassController::class, 'updatePeriod'])->whereNumber('period')->name('class-periods.update');
    Route::get('enrollments', [AcademicClassController::class, 'indexEnrollments'])->name('enrollments.index');
    Route::post('enrollments', [AcademicClassController::class, 'storeEnrollment'])->name('enrollments.store');
    Route::get('enrollments/{enrollment}', [AcademicClassController::class, 'showEnrollment'])->whereNumber('enrollment')->name('enrollments.show');
    Route::put('enrollments/{enrollment}', [AcademicClassController::class, 'updateEnrollment'])->whereNumber('enrollment')->name('enrollments.update');
    Route::post('enrollments/move', [AcademicClassController::class, 'moveEnrollment'])->name('enrollments.move');
    Route::post('transitions/preview', [AcademicClassController::class, 'transitionPreview'])->name('transitions.preview');
    Route::post('transitions/confirm', [AcademicClassController::class, 'transitionConfirm'])->name('transitions.confirm');

    Route::get('calendar', [AcademicCalendarController::class, 'index'])->name('calendar.index');
    Route::post('calendar', [AcademicCalendarController::class, 'store'])->name('calendar.store');
    Route::get('calendar/{event}', [AcademicCalendarController::class, 'show'])->whereNumber('event')->name('calendar.show');
    Route::put('calendar/{event}', [AcademicCalendarController::class, 'update'])->whereNumber('event')->name('calendar.update');

    Route::get('teaching-assignments', [AcademicAssignmentController::class, 'indexTeaching'])->name('teaching.index');
    Route::post('teaching-assignments', [AcademicAssignmentController::class, 'storeTeaching'])->name('teaching.store');
    Route::get('teaching-assignments/{assignment}', [AcademicAssignmentController::class, 'showTeaching'])->whereNumber('assignment')->name('teaching.show');
    Route::put('teaching-assignments/{assignment}', [AcademicAssignmentController::class, 'updateTeaching'])->whereNumber('assignment')->name('teaching.update');
    Route::get('homeroom-assignments', [AcademicAssignmentController::class, 'indexHomeroom'])->name('homeroom.index');
    Route::post('homeroom-assignments', [AcademicAssignmentController::class, 'storeHomeroom'])->name('homeroom.store');
    Route::get('homeroom-assignments/{assignment}', [AcademicAssignmentController::class, 'showHomeroom'])->whereNumber('assignment')->name('homeroom.show');
    Route::put('homeroom-assignments/{assignment}', [AcademicAssignmentController::class, 'updateHomeroom'])->whereNumber('assignment')->name('homeroom.update');

    Route::get('timetables', [AcademicScheduleController::class, 'indexTimetables'])->name('timetables.index');
    Route::post('timetables', [AcademicScheduleController::class, 'storeTimetable'])->name('timetables.store');
    Route::get('timetables/{timetable}', [AcademicScheduleController::class, 'showTimetable'])->whereNumber('timetable')->name('timetables.show');
    Route::put('timetables/{timetable}', [AcademicScheduleController::class, 'updateTimetable'])->whereNumber('timetable')->name('timetables.update');
    Route::get('timetables/{timetable}/versions', [AcademicScheduleController::class, 'indexVersions'])->whereNumber('timetable')->name('timetable-versions.index');
    Route::post('timetables/{timetable}/versions', [AcademicScheduleController::class, 'storeVersion'])->whereNumber('timetable')->name('timetable-versions.store');
    Route::get('timetable-versions/{version}', [AcademicScheduleController::class, 'showVersion'])->whereNumber('version')->name('timetable-versions.show');
    Route::put('timetable-versions/{version}', [AcademicScheduleController::class, 'updateVersion'])->whereNumber('version')->name('timetable-versions.update');
    Route::post('timetable-versions/{version}/publish', [AcademicScheduleController::class, 'publishVersion'])->whereNumber('version')->name('timetable-versions.publish');
    Route::post('timetable-versions/{version}/generate-meetings', [AcademicScheduleController::class, 'generateMeetings'])->whereNumber('version')->name('timetable-versions.generate');
    Route::get('timetable-versions/{version}/entries', [AcademicScheduleController::class, 'indexEntries'])->whereNumber('version')->name('schedule-entries.index');
    Route::post('timetable-versions/{version}/entries', [AcademicScheduleController::class, 'storeEntry'])->whereNumber('version')->name('schedule-entries.store');
    Route::put('schedule-entry-versions/{entry}', [AcademicScheduleController::class, 'updateEntry'])->whereNumber('entry')->name('schedule-entries.update');
    Route::delete('schedule-entry-versions/{entry}', [AcademicScheduleController::class, 'removeEntry'])->whereNumber('entry')->name('schedule-entries.destroy');
    Route::get('lesson-slots', [AcademicScheduleController::class, 'indexLessonSlots'])->name('lesson-slots.index');
    Route::post('lesson-slots', [AcademicScheduleController::class, 'storeLessonSlot'])->name('lesson-slots.store');
    Route::put('lesson-slots/{slot}', [AcademicScheduleController::class, 'updateLessonSlot'])->whereNumber('slot')->name('lesson-slots.update');

    Route::get('meetings', [AcademicScheduleController::class, 'indexMeetings'])->name('meetings.index');
    Route::get('meetings/{meeting}', [AcademicScheduleController::class, 'showMeeting'])->whereNumber('meeting')->name('meetings.show');
    Route::post('meetings/{meeting}/cancel', [AcademicScheduleController::class, 'cancelMeeting'])->whereNumber('meeting')->name('meetings.cancel');
    Route::post('meetings/{meeting}/substitute', [AcademicScheduleController::class, 'assignSubstitute'])->whereNumber('meeting')->name('meetings.substitute');
    Route::get('substitute-assignments', [AcademicScheduleController::class, 'indexSubstitutes'])->name('substitutes.index');
    Route::post('substitute-assignments/{substitute}/revoke', [AcademicScheduleController::class, 'revokeSubstitute'])->whereNumber('substitute')->name('substitutes.revoke');
});

Route::prefix('v1/master')->name('api.v1.master.')->middleware(['auth:api', RequirePasswordChanged::class, 'throttle:120,1'])->group(function (): void {
    Route::get('options', [MasterDataController::class, 'options'])->name('options');
    Route::get('people', [MasterDataController::class, 'people'])->name('people');
    Route::get('institution', [MasterDataController::class, 'institution'])->name('institution.show');
    Route::put('institution', [MasterDataController::class, 'saveInstitution'])->name('institution.update');
    Route::get('imports/templates/{resource}', [MasterImportController::class, 'template'])->name('imports.template');
    Route::get('imports', [MasterImportController::class, 'index'])->name('imports.index');
    Route::post('imports', [MasterImportController::class, 'store'])->middleware('throttle:10,1,master-import:')->name('imports.store');
    Route::get('imports/{batch}', [MasterImportController::class, 'show'])->whereNumber('batch')->name('imports.show');
    Route::get('imports/{batch}/rows', [MasterImportController::class, 'rows'])->whereNumber('batch')->name('imports.rows');
    Route::get('imports/{batch}/errors', [MasterImportController::class, 'errors'])->whereNumber('batch')->name('imports.errors');
    Route::post('imports/{batch}/confirm', [MasterImportController::class, 'confirm'])->whereNumber('batch')->name('imports.confirm');

    Route::prefix('students/{owner}')->whereNumber('owner')->group(function (): void {
        Route::get('{relation}', [MasterRelationController::class, 'index'])->where('relation', 'guardian-links')->name('guardian-links.index');
        Route::post('{relation}', [MasterRelationController::class, 'store'])->where('relation', 'guardian-links')->name('guardian-links.store');
        Route::post('{relation}/{link}/{action}', [MasterRelationController::class, 'action'])->where(['relation' => 'guardian-links', 'link' => '[0-9]+', 'action' => 'verify|revoke|end'])->name('guardian-links.action');
    });
    Route::prefix('employees/{owner}')->whereNumber('owner')->group(function (): void {
        Route::get('{relation}', [MasterRelationController::class, 'index'])->where('relation', 'categories|positions')->name('employee-relations.index');
        Route::post('{relation}', [MasterRelationController::class, 'store'])->where('relation', 'categories|positions')->name('employee-relations.store');
        Route::post('{relation}/{link}/{action}', [MasterRelationController::class, 'action'])->where(['relation' => 'categories|positions', 'link' => '[0-9]+', 'action' => 'end'])->name('employee-relations.action');
    });

    Route::group(['where' => ['resource' => 'students|guardians|employees|positions|employee-categories|subjects|student-statuses|attendance-statuses']], function (): void {
        Route::get('{resource}', [MasterDataController::class, 'index'])->name('index');
        Route::post('{resource}', [MasterDataController::class, 'store'])->name('store');
        Route::get('{resource}/{record}', [MasterDataController::class, 'show'])->whereNumber('record')->name('show');
        Route::put('{resource}/{record}', [MasterDataController::class, 'update'])->whereNumber('record')->name('update');
        Route::post('{resource}/{record}/status', [MasterDataController::class, 'status'])->whereNumber('record')->name('status');
    });
});

Route::prefix('v1/accounts')->name('api.v1.accounts.')->middleware(['auth:api', RequirePasswordChanged::class, 'throttle:120,1'])->group(function (): void {
    Route::get('people', [AccountUserController::class, 'people']);
    Route::get('audit', [AccountUserController::class, 'audit']);
    Route::get('users', [AccountUserController::class, 'index']);
    Route::post('users', [AccountUserController::class, 'store']);
    Route::get('users/{user}', [AccountUserController::class, 'show'])->whereNumber('user');
    Route::put('users/{user}', [AccountUserController::class, 'update'])->whereNumber('user');
    Route::post('users/{user}/{action}', [AccountUserController::class, 'action'])->whereNumber('user');
    Route::get('roles', [AccountRoleController::class, 'index']);
    Route::post('roles', [AccountRoleController::class, 'store']);
    Route::put('roles/{role}', [AccountRoleController::class, 'update'])->whereNumber('role');
});
