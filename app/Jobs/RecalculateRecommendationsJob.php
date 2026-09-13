<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\BadgeService;
use App\Services\FlexLearnRecommendationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecalculateRecommendationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $userId) {}

    public function handle(FlexLearnRecommendationService $flex): void
    {
        $user = User::query()->find($this->userId);
        if ($user) {
            $flex->refreshFor($user);
        }
    }
}
