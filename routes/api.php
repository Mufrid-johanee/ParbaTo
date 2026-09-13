<?php

use Illuminate\Support\Facades\Route;

/*
| ParbaTo API surface will expand per PROJECT_REPORT.md.
| Web UI is primary for MVP; authenticated JSON endpoints added as features harden.
*/

Route::get('/health', function () {
    return response()->json([
        'app' => 'ParbaTo',
        'status' => 'ok',
        'engines' => ['ClassTwin', 'LearnQuest', 'FlexLearn'],
    ]);
});
