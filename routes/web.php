<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassTwinController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlexLearnController;
use App\Http\Controllers\LearnQuestController;
use App\Http\Controllers\MissionEvaluationController;
use App\Http\Controllers\TeacherAnalyticsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
    Route::get('/classrooms/join', [ClassroomController::class, 'joinForm'])->name('classrooms.join');
    Route::post('/classrooms/join', [ClassroomController::class, 'join'])->name('classrooms.join.store');
    Route::get('/classrooms/create', [ClassroomController::class, 'create'])->name('classrooms.create');
    Route::post('/classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
    Route::get('/classrooms/{classroom}', [ClassroomController::class, 'show'])->name('classrooms.show');
    Route::get('/classrooms/{classroom}/edit', [ClassroomController::class, 'edit'])->name('classrooms.edit');
    Route::put('/classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
    Route::post('/classrooms/{classroom}/archive', [ClassroomController::class, 'archive'])->name('classrooms.archive');
    Route::post('/classrooms/{classroom}/members/{student}/remove', [ClassroomController::class, 'removeMember'])->name('classrooms.members.remove');
    Route::post('/classrooms/{classroom}/sessions', [ClassroomController::class, 'startSession'])->name('classrooms.sessions.start');

    Route::get('/classtwin/sessions/{session}', [ClassTwinController::class, 'show'])->name('classtwin.show');
    Route::post('/classtwin/sessions/{session}/attendance', [ClassTwinController::class, 'checkIn'])->name('classtwin.attendance');
    Route::post('/classtwin/sessions/{session}/join', [ClassTwinController::class, 'joinSession'])->name('classtwin.join');
    Route::post('/classtwin/sessions/{session}/heartbeat', [ClassTwinController::class, 'heartbeat'])->name('classtwin.heartbeat');
    Route::get('/classtwin/sessions/{session}/presence', [ClassTwinController::class, 'presence'])->name('classtwin.presence');
    Route::post('/classtwin/sessions/{session}/rotate-code', [ClassTwinController::class, 'rotateCode'])->name('classtwin.rotate');
    Route::post('/classtwin/sessions/{session}/end', [ClassTwinController::class, 'end'])->name('classtwin.end');
    Route::post('/classtwin/sessions/{session}/mark-attendance', [ClassTwinController::class, 'markAttendance'])->name('classtwin.mark');
    Route::post('/classtwin/sessions/{session}/help', [ClassTwinController::class, 'requestHelp'])->name('classtwin.help');
    Route::post('/classtwin/sessions/{session}/help/{help}/resolve', [ClassTwinController::class, 'resolveHelp'])->name('classtwin.help.resolve');

    Route::get('/learnquest', [LearnQuestController::class, 'index'])->name('learnquest.index');
    Route::get('/learnquest/missions/{mission:slug}', [LearnQuestController::class, 'show'])->name('learnquest.show');
    Route::post('/learnquest/missions/{mission:slug}/start', [LearnQuestController::class, 'start'])->name('learnquest.start');
    Route::post('/learnquest/missions/{mission:slug}/tasks/{task}/complete', [LearnQuestController::class, 'completeTask'])->name('learnquest.tasks.complete');
    Route::post('/learnquest/missions/{mission:slug}/submit', [LearnQuestController::class, 'submit'])->name('learnquest.submit');

    Route::middleware('role:teacher,admin')->prefix('learnquest/evaluations')->name('learnquest.evaluations.')->group(function () {
        Route::get('/', [MissionEvaluationController::class, 'index'])->name('index');
        Route::get('/{enrollment}', [MissionEvaluationController::class, 'show'])->name('show');
        Route::post('/{enrollment}', [MissionEvaluationController::class, 'store'])->name('store');
    });

    Route::get('/flexlearn', [FlexLearnController::class, 'index'])->name('flexlearn.index');
    Route::post('/flexlearn/recommendations/{recommendation}/start', [FlexLearnController::class, 'startRecommendation'])
        ->name('flexlearn.recommendations.start');
    Route::post('/flexlearn/recommendations/{recommendation}/dismiss', [FlexLearnController::class, 'dismissRecommendation'])
        ->name('flexlearn.recommendations.dismiss');

    Route::middleware('role:teacher,admin')->prefix('flexlearn/students')->name('flexlearn.teacher.')->group(function () {
        Route::get('/', [FlexLearnController::class, 'teacherIndex'])->name('index');
        Route::get('/{student}', [FlexLearnController::class, 'teacherShow'])->name('show');
    });

    Route::get('/analytics', TeacherAnalyticsController::class)
        ->middleware('role:teacher,admin')
        ->name('analytics.teacher');
});
