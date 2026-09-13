<?php

use App\Jobs\CheckAndAwardBadgesJob;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('parbato:backup --keep=14')->dailyAt('02:30');

Schedule::call(function () {
    User::query()->where('role', User::ROLE_STUDENT)->orderBy('id')->chunkById(50, function ($users) {
        foreach ($users as $user) {
            CheckAndAwardBadgesJob::dispatch($user->id);
        }
    });
})->dailyAt('03:00');
