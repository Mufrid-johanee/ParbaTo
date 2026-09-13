<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\AssessmentAttempt;
use App\Models\AttendanceRecord;
use App\Models\MissionEnrollment;
use App\Models\PortfolioItem;
use App\Models\User;
use App\Models\XpLedger;
use App\Notifications\BadgeEarnedNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Badge conditions (deterministic product rules):
 *
 * - first-mission: at least one MissionEnrollment with status completed
 * - mission-streak-3 / mission-streak-10: cumulative completed missions (not calendar-consecutive days)
 * - perfect-score: any graded AssessmentAttempt with accuracy >= 100
 * - consistent-learner: at least 5 distinct daily_login XP ledger entries
 * - classroom-champion: present/late attendance in at least 5 distinct ClassTwin sessions
 * - explorer: total XP >= 150
 * - quick-starter: has any mission enrollment AND any attendance record
 * - skill-builder: mastery >= 60 on at least 2 skills
 * - advanced-learner: mastery >= 80 on at least one skill
 * - portfolio-builder: at least 2 portfolio items
 * - level-up-*: users.level threshold
 */
class BadgeService
{
    public function hasBadge(User $user, string $slug): bool
    {
        return $user->achievements()->where('slug', $slug)->exists();
    }

    public function awardBadge(User $user, string $slug): bool
    {
        if (! $user->isStudent()) {
            return false;
        }

        $badge = Achievement::query()->where('slug', $slug)->where('is_active', true)->first();
        if (! $badge) {
            return false;
        }

        return DB::transaction(function () use ($user, $badge) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->achievements()->where('achievement_id', $badge->id)->exists()) {
                return false;
            }

            try {
                $lockedUser->achievements()->attach($badge->id, ['earned_at' => now()]);
            } catch (QueryException $e) {
                // Unique (user_id, achievement_id) — concurrent award race.
                return false;
            }

            $already = $lockedUser->notifications()
                ->where('type', BadgeEarnedNotification::class)
                ->where('data->badge_id', $badge->id)
                ->exists();

            if (! $already) {
                $lockedUser->notify(new BadgeEarnedNotification($badge));
            }

            if ((int) $badge->xp_reward > 0) {
                $xp = app(XpService::class);
                $xp->withoutBadgeCheck(function () use ($xp, $lockedUser, $badge) {
                    $xp->award(
                        $lockedUser,
                        'badge_bonus',
                        $badge->id,
                        'Badge bonus: '.$badge->name,
                        (int) $badge->xp_reward,
                        false
                    );
                });
            }

            return true;
        });
    }

    /**
     * @return array<int, Achievement>
     */
    public function checkAndAward(User $user): array
    {
        if (! $user->isStudent()) {
            return [];
        }

        $awarded = [];

        $checks = [
            'first-mission' => fn () => MissionEnrollment::query()->where('user_id', $user->id)->where('status', 'completed')->exists(),
            // "Streak" = cumulative completed missions (product rule), not calendar-day consecutive.
            'mission-streak-3' => fn () => MissionEnrollment::query()->where('user_id', $user->id)->where('status', 'completed')->count() >= 3,
            'mission-streak-10' => fn () => MissionEnrollment::query()->where('user_id', $user->id)->where('status', 'completed')->count() >= 10,
            'perfect-score' => fn () => AssessmentAttempt::query()->where('user_id', $user->id)->where('status', 'graded')->where('accuracy', '>=', 100)->exists(),
            'consistent-learner' => fn () => XpLedger::query()->where('user_id', $user->id)->where('source_type', 'daily_login')->count() >= 5,
            'classroom-champion' => fn () => (int) AttendanceRecord::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['present', 'late'])
                ->selectRaw('count(distinct class_session_id) as aggregate')
                ->value('aggregate') >= 5,
            'explorer' => fn () => (int) $user->fresh()->xp >= 150,
            'quick-starter' => fn () => MissionEnrollment::query()->where('user_id', $user->id)->exists()
                && AttendanceRecord::query()->where('user_id', $user->id)->exists(),
            'skill-builder' => fn () => $user->studentSkills()->where('mastery', '>=', 60)->count() >= 2,
            'advanced-learner' => fn () => $user->studentSkills()->where('mastery', '>=', 80)->exists(),
            'portfolio-builder' => fn () => PortfolioItem::query()->where('user_id', $user->id)->count() >= 2,
            'level-up-practitioner' => fn () => (int) $user->fresh()->level >= 3,
            'level-up-builder' => fn () => (int) $user->fresh()->level >= 4,
        ];

        foreach ($checks as $slug => $condition) {
            if ($this->hasBadge($user, $slug)) {
                continue;
            }
            if ($condition()) {
                if ($this->awardBadge($user, $slug)) {
                    $awarded[] = Achievement::query()->where('slug', $slug)->first();
                }
            }
        }

        return array_filter($awarded);
    }

    public function catalogFor(User $user): array
    {
        $all = Achievement::query()->where('is_active', true)->orderBy('name')->get();
        $earnedIds = $user->achievements()->pluck('achievements.id')->all();

        return [
            'earned' => $all->whereIn('id', $earnedIds)->values(),
            'locked' => $all->whereNotIn('id', $earnedIds)->values(),
            'count' => count($earnedIds),
            'total' => $all->count(),
        ];
    }
}
