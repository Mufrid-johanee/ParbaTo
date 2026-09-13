<?php

use App\Http\Controllers\Api\FlexLearnApiController;
use App\Http\Controllers\Api\LearnQuestApiController;
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
