<?php

namespace App\Services;

use App\Models\User;
use App\Models\XpLedger;
use App\Notifications\XpAwardedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class XpService
{
    protected static bool $suppressBadgeCheck = false;

    public function award(
        User $user,
        string $sourceType,
        ?int $sourceId = null,
        string $description = '',
        ?int $amount = null,
        bool $notify = false
    ): ?XpLedger {
        if (! $user->isStudent()) {
            return null;
        }

        $configured = config('xp.events.'.$sourceType);
        $xpAmount = $amount ?? (is_int($configured) ? $configured : 0);

        if ($xpAmount <= 0) {
            return null;
        }

        $sourceId = $sourceId ?? 0;

        return DB::transaction(function () use ($user, $sourceType, $sourceId, $description, $xpAmount, $notify) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existing = XpLedger::query()
                ->where('user_id', $lockedUser->id)
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            try {
                $entry = XpLedger::query()->create([
                    'user_id' => $lockedUser->id,
                    'amount' => $xpAmount,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'description' => $description !== '' ? $description : $sourceType,
                    'awarded_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $existing = XpLedger::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('source_type', $sourceType)
                    ->where('source_id', $sourceId)
                    ->first();
                if ($existing) {
                    return $existing;
                }
                throw $e;
            }

            $newTotal = (int) $lockedUser->xp + $xpAmount;
            $levelInfo = $this->getLevel($newTotal);

            $lockedUser->forceFill([
                'xp' => $newTotal,
                'level' => $levelInfo['level'],
            ])->save();

            if ($notify) {
                $already = $lockedUser->notifications()
                    ->where('type', XpAwardedNotification::class)
                    ->where('data->ledger_id', $entry->id)
                    ->exists();
                if (! $already) {
                    $lockedUser->notify(new XpAwardedNotification($entry));
                }
            }

            // Prevent badge_bonus → checkAndAward → badge_bonus recursion nesting.
            if (! self::$suppressBadgeCheck && $sourceType !== 'badge_bonus') {
                app(BadgeService::class)->checkAndAward($lockedUser->fresh());
            }

            return $entry;
        });
    }

    public function withoutBadgeCheck(callable $callback): mixed
    {
        $previous = self::$suppressBadgeCheck;
        self::$suppressBadgeCheck = true;
        try {
            return $callback();
        } finally {
            self::$suppressBadgeCheck = $previous;
        }
    }

    public function getTotalXp(User $user): int
    {
        return (int) XpLedger::query()->where('user_id', $user->id)->sum('amount');
    }

    /**
     * @return array{level:int,name:string,current_xp:int,current_level_xp:int,next_level_xp:int|null,progress_percent:float,is_max:bool}
     */
    public function getLevel(int $totalXp): array
    {
        $levels = collect(config('xp.levels', []))->sortBy('required_xp')->values();
        $totalXp = max(0, $totalXp);

        if ($levels->isEmpty()) {
            return [
                'level' => 1,
                'name' => 'Explorer',
                'current_xp' => $totalXp,
                'current_level_xp' => 0,
                'next_level_xp' => null,
                'progress_percent' => 100.0,
                'is_max' => true,
            ];
        }

        $current = $levels->first();
        $next = null;

        foreach ($levels as $index => $level) {
            if ($totalXp >= (int) $level['required_xp']) {
                $current = $level;
                $next = $levels->get($index + 1);
            }
        }

        $currentXpFloor = (int) $current['required_xp'];
        $nextXp = $next ? (int) $next['required_xp'] : null;
        $span = $nextXp !== null ? max(1, $nextXp - $currentXpFloor) : 1;
        $into = $totalXp - $currentXpFloor;
        $progress = $nextXp === null ? 100.0 : min(100, max(0, round(($into / $span) * 100, 1)));

        return [
            'level' => (int) $current['level'],
            'name' => (string) $current['name'],
            'current_xp' => $totalXp,
            'current_level_xp' => $currentXpFloor,
            'next_level_xp' => $nextXp,
            'progress_percent' => $progress,
            'is_max' => $nextXp === null,
        ];
    }

    public function getRecentXp(User $user, int $limit = 10): Collection
    {
        return XpLedger::query()
            ->where('user_id', $user->id)
            ->latest('awarded_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{updated:int,mismatches:int}
     */
    public function recalculateAll(): array
    {
        $updated = 0;
        $mismatches = 0;

        User::query()->where('role', User::ROLE_STUDENT)->orderBy('id')->chunkById(100, function ($users) use (&$updated, &$mismatches) {
            foreach ($users as $user) {
                $ledgerTotal = $this->getTotalXp($user);
                $level = $this->getLevel($ledgerTotal);
                if ((int) $user->xp !== $ledgerTotal || (int) $user->level !== $level['level']) {
                    $mismatches++;
                    Log::info('XP mismatch corrected', [
                        'user_id' => $user->id,
                        'stored_xp' => $user->xp,
                        'ledger_xp' => $ledgerTotal,
                    ]);
                }
                $user->forceFill([
                    'xp' => $ledgerTotal,
                    'level' => $level['level'],
                ])->save();
                $updated++;
            }
        });

        return compact('updated', 'mismatches');
    }
}
