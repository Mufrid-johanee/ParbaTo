<?php

use App\Http\Controllers\Api\AssessmentApiController;
use App\Http\Controllers\Api\ClassTwinApiController;
use App\Http\Controllers\Api\FlexLearnApiController;
use App\Http\Controllers\Api\LearnQuestApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\TeacherAnalyticsApiController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'app' => 'ParbaTo',
        'status' => 'ok',
        'engines' => ['ClassTwin', 'LearnQuest', 'FlexLearn'],
    ]);
});

Route::middleware('auth')->prefix('learnquest')->group(function () {
    Route::get('/missions/{mission:slug}', [LearnQuestApiController::class, 'show']);
    Route::post('/missions/{mission:slug}/start', [LearnQuestApiController::class, 'start']);
    Route::get('/missions/{mission:slug}/enrollment', [LearnQuestApiController::class, 'enrollment']);
    Route::post('/missions/{mission:slug}/tasks/{task}/complete', [LearnQuestApiController::class, 'completeTask']);
    Route::post('/missions/{mission:slug}/submit', [LearnQuestApiController::class, 'submit']);
    Route::post('/enrollments/{enrollment}/evaluate', [LearnQuestApiController::class, 'evaluate'])
        ->middleware('role:teacher,admin');
});

Route::middleware('auth')->prefix('flexlearn')->group(function () {
    Route::get('/', [FlexLearnApiController::class, 'index']);
    Route::get('/mastery', [FlexLearnApiController::class, 'mastery']);
    Route::get('/recommendations', [FlexLearnApiController::class, 'recommendations']);
    Route::post('/recommendations/{recommendation}/start', [FlexLearnApiController::class, 'start']);
    Route::post('/recommendations/{recommendation}/complete', [FlexLearnApiController::class, 'complete']);
    Route::post('/recommendations/{recommendation}/dismiss', [FlexLearnApiController::class, 'dismiss']);
});

Route::middleware('auth')->group(function () {
    Route::get('/classrooms', [ClassTwinApiController::class, 'classrooms']);
    Route::post('/classrooms', [ClassTwinApiController::class, 'storeClassroom']);
    Route::post('/classrooms/join', [ClassTwinApiController::class, 'joinClassroom']);
    Route::get('/classrooms/{classroom}', [ClassTwinApiController::class, 'showClassroom']);
    Route::put('/classrooms/{classroom}', [ClassTwinApiController::class, 'updateClassroom']);
    Route::post('/classrooms/{classroom}/archive', [ClassTwinApiController::class, 'archiveClassroom']);
    Route::post('/classrooms/{classroom}/members/{student}/remove', [ClassTwinApiController::class, 'removeMember']);
    Route::post('/classrooms/{classroom}/sessions', [ClassTwinApiController::class, 'startSession']);
    Route::get('/classrooms/{classroom}/sessions', [ClassTwinApiController::class, 'classroomSessions']);
    Route::get('/sessions/{session}', [ClassTwinApiController::class, 'showSession']);
    Route::post('/sessions/{session}/join', [ClassTwinApiController::class, 'joinSession']);
    Route::post('/sessions/{session}/attendance', [ClassTwinApiController::class, 'attendance']);
    Route::post('/sessions/{session}/heartbeat', [ClassTwinApiController::class, 'heartbeat']);
    Route::post('/sessions/{session}/end', [ClassTwinApiController::class, 'endSession']);
    Route::post('/sessions/{session}/mark-attendance', [ClassTwinApiController::class, 'markAttendance']);

    Route::get('/assessments', [AssessmentApiController::class, 'index']);
    Route::post('/assessments', [AssessmentApiController::class, 'store']);
    Route::get('/assessments/{assessment}', [AssessmentApiController::class, 'show']);
    Route::put('/assessments/{assessment}', [AssessmentApiController::class, 'update']);
    Route::delete('/assessments/{assessment}', [AssessmentApiController::class, 'destroy']);
    Route::post('/assessments/{assessment}/questions', [AssessmentApiController::class, 'storeQuestion']);
    Route::put('/questions/{question}', [AssessmentApiController::class, 'updateQuestion']);
    Route::delete('/questions/{question}', [AssessmentApiController::class, 'destroyQuestion']);
    Route::post('/assessments/{assessment}/attempts', [AssessmentApiController::class, 'startAttempt']);
    Route::get('/attempts/{attempt}', [AssessmentApiController::class, 'showAttempt']);
    Route::put('/attempts/{attempt}/answers', [AssessmentApiController::class, 'saveAnswers']);
    Route::post('/attempts/{attempt}/submit', [AssessmentApiController::class, 'submit']);
    Route::get('/attempts/{attempt}/result', [AssessmentApiController::class, 'result']);
    Route::post('/attempts/{attempt}/grade', [AssessmentApiController::class, 'grade']);

    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationApiController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationApiController::class, 'markAllRead']);

    Route::middleware('role:teacher,admin')->prefix('analytics')->group(function () {
        Route::get('/overview', [TeacherAnalyticsApiController::class, 'overview']);
        Route::get('/classrooms', [TeacherAnalyticsApiController::class, 'classrooms']);
        Route::get('/classrooms/{classroom}', [TeacherAnalyticsApiController::class, 'classroom']);
        Route::get('/missions', [TeacherAnalyticsApiController::class, 'missions']);
        Route::get('/assessments', [TeacherAnalyticsApiController::class, 'assessments']);
        Route::get('/at-risk', [TeacherAnalyticsApiController::class, 'atRisk']);
        Route::get('/skills', [TeacherAnalyticsApiController::class, 'skills']);
    });
});
