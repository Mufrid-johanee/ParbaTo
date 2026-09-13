<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Policies\PortfolioItemPolicy;
use App\Services\BadgeService;
use App\Services\PortfolioService;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __construct(
        protected PortfolioService $portfolio,
        protected XpService $xp,
        protected BadgeService $badges,
    ) {}

    public function profile(Request $request): View
    {
        $student = $request->user();
        abort_unless($student->isStudent() || $student->isAdmin(), 403);

        return $this->renderPortfolio($student, false);
    }

    public function skills(Request $request): View
    {
        $student = $request->user();
        abort_unless($student->isStudent() || $student->isAdmin(), 403);

        $mastery = $this->portfolio->masteryProfile($student);

        return view('portfolio.skills', [
            'student' => $student,
            'mastery' => $mastery,
        ]);
    }

    public function teacherShow(Request $request, User $student): View
    {
        abort_unless(
            app(PortfolioItemPolicy::class)->viewStudent($request->user(), $student),
            403
        );

        return $this->renderPortfolio($student, true);
    }

    protected function renderPortfolio(User $student, bool $teacherView): View
    {
        return view('portfolio.show', [
            'student' => $student,
            'stats' => $this->portfolio->headerStats($student),
            'mastery' => $this->portfolio->masteryProfile($student),
            'timeline' => $this->portfolio->timeline($student),
            'evidence' => $this->portfolio->evidence($student),
            'recommendations' => $this->portfolio->recommendations($student),
            'teacherView' => $teacherView,
            'level' => $this->xp->getLevel((int) $student->xp),
            'recentXp' => $this->xp->getRecentXp($student, 10),
            'badgeCatalog' => $this->badges->catalogFor($student),
        ]);
    }
}
