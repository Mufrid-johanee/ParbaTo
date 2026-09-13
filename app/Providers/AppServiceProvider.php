<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\MissionEnrollment;
use App\Models\PortfolioItem;
use App\Policies\AssessmentAttemptPolicy;
use App\Policies\AssessmentPolicy;
use App\Policies\ClassroomPolicy;
use App\Policies\ClassSessionPolicy;
use App\Policies\MissionEnrollmentPolicy;
use App\Policies\PortfolioItemPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(MissionEnrollment::class, MissionEnrollmentPolicy::class);
        Gate::policy(Classroom::class, ClassroomPolicy::class);
        Gate::policy(ClassSession::class, ClassSessionPolicy::class);
        Gate::policy(Assessment::class, AssessmentPolicy::class);
        Gate::policy(AssessmentAttempt::class, AssessmentAttemptPolicy::class);
        Gate::policy(PortfolioItem::class, PortfolioItemPolicy::class);
    }
}
