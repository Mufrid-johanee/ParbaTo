<?php

namespace App\Providers;

use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\MissionEnrollment;
use App\Policies\ClassroomPolicy;
use App\Policies\ClassSessionPolicy;
use App\Policies\MissionEnrollmentPolicy;
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
    }
}
