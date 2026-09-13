<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckAndAwardBadgesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId) {}

    public function handle(BadgeService $badges): void
    {
        $user = User::query()->find($this->userId);
        if ($user) {
            $badges->checkAndAward($user);
        }
    }
}
