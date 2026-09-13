<?php

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
