<?php

namespace App\Services;

use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\Recommendation;
use App\Models\StudentSkill;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Explainable rule-based FlexLearn recommendation engine.
 * Links recommendations to real published missions when possible.
 * No AI / external APIs.
 */
class FlexLearnRecommendationService
{
    public function __construct(
        protected MasteryService $mastery
    ) {}

    public function refreshFor(User $user): Collection
    {
        return DB::transaction(function () use ($user) {
            $this->mastery->recalculateAllFor($user);

            $keepKeys = [];
            $created = collect();

            $skills = StudentSkill::query()
                ->with('skill')
                ->where('user_id', $user->id)
                ->orderBy('mastery')
                ->get();

            $completedMissionIds = MissionEnrollment::query()
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->pluck('mission_id');

            $activeMissionIds = MissionEnrollment::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['discovered', 'in_progress', 'submitted'])
                ->pluck('mission_id');

            foreach ($skills as $index => $studentSkill) {
                $mastery = (int) $studentSkill->mastery;
                $band = MasteryService::bandKey($mastery);
                $skillName = $studentSkill->skill->name;

                $mission = $this->findMissionForSkill(
                    $studentSkill->skill_id,
                    $mastery,
                    $completedMissionIds,
                    $activeMissionIds
                );

                if ($mastery < MasteryService::BAND_NEEDS_SUPPORT) {
                    $rec = $this->upsert($user, [
                        'skill_id' => $studentSkill->skill_id,
                        'mission_id' => $mission?->id,
                        'title' => $mission
                            ? "Support path: {$mission->title}"
                            : "Beginner practice: {$skillName}",
                        'reason' => "Recommended because your {$skillName} mastery is {$mastery}% (Needs Support). Strengthen fundamentals before advancing.",
                        'action_label' => $mission ? 'Start mission' : 'Browse missions',
                        'action_url' => $mission
                            ? route('learnquest.show', $mission)
                            : route('learnquest.index'),
                        'priority' => 'high',
                        'sort_order' => 10 + $index,
                        'rule_key' => 'needs_support:'.$studentSkill->skill_id,
                    ]);
                    $keepKeys[] = $rec->rule_key;
                    $created->push($rec);
                } elseif ($mastery < MasteryService::BAND_DEVELOPING) {
                    $rec = $this->upsert($user, [
                        'skill_id' => $studentSkill->skill_id,
                        'mission_id' => $mission?->id,
                        'title' => $mission
                            ? "Targeted practice: {$mission->title}"
                            : "Targeted practice: {$skillName}",
                        'reason' => "Recommended because your recent evidence shows {$skillName} is still Developing ({$mastery}%). Focused practice will raise mastery.",
                        'action_label' => $mission ? 'Continue mission' : 'Open LearnQuest',
                        'action_url' => $mission
                            ? route('learnquest.show', $mission)
                            : route('learnquest.index'),
                        'priority' => 'high',
                        'sort_order' => 20 + $index,
                        'rule_key' => 'developing:'.$studentSkill->skill_id,
                    ]);
                    $keepKeys[] = $rec->rule_key;
                    $created->push($rec);
                } elseif ($mastery < MasteryService::BAND_PROFICIENT) {
                    $rec = $this->upsert($user, [
                        'skill_id' => $studentSkill->skill_id,
                        'mission_id' => $mission?->id,
                        'title' => $mission
                            ? "Applied mission: {$mission->title}"
                            : "Applied practice: {$skillName}",
                        'reason' => "Recommended because your {$skillName} mastery is {$mastery}% (Proficient). An applied mission will consolidate skills.",
                        'action_label' => $mission ? 'Open mission' : 'Browse missions',
                        'action_url' => $mission
                            ? route('learnquest.show', $mission)
                            : route('learnquest.index'),
                        'priority' => 'medium',
                        'sort_order' => 40 + $index,
                        'rule_key' => 'proficient:'.$studentSkill->skill_id,
                    ]);
                    $keepKeys[] = $rec->rule_key;
                    $created->push($rec);
                } else {
                    $advanced = $this->findMissionForSkill(
                        $studentSkill->skill_id,
                        $mastery,
                        $completedMissionIds,
                        $activeMissionIds,
                        preferAdvanced: true
                    );
                    $rec = $this->upsert($user, [
                        'skill_id' => $studentSkill->skill_id,
                        'mission_id' => $advanced?->id ?? $mission?->id,
                        'title' => ($advanced ?? $mission)
                            ? 'Advanced challenge: '.($advanced ?? $mission)->title
                            : "Advanced challenge: {$skillName}",
                        'reason' => "Recommended because your {$skillName} mastery is {$mastery}% (Advanced). Try a harder challenge to stretch further.",
                        'action_label' => 'Open challenge',
                        'action_url' => ($advanced ?? $mission)
                            ? route('learnquest.show', $advanced ?? $mission)
                            : route('learnquest.index', ['difficulty' => 'advanced']),
                        'priority' => 'low',
                        'sort_order' => 80 + $index,
                        'rule_key' => 'advanced:'.$studentSkill->skill_id,
                    ]);
                    $keepKeys[] = $rec->rule_key;
                    $created->push($rec);
                }
            }

            // Incomplete active missions get a nudge
            $incomplete = MissionEnrollment::query()
                ->with('mission')
                ->where('user_id', $user->id)
                ->whereIn('status', ['in_progress', 'submitted'])
                ->latest('updated_at')
                ->first();

            if ($incomplete?->mission) {
                $rec = $this->upsert($user, [
                    'mission_id' => $incomplete->mission_id,
                    'title' => 'Continue: '.$incomplete->mission->title,
                    'reason' => 'Recommended because you have an unfinished LearnQuest mission ('.$incomplete->progress_percent.'% · '.$incomplete->lifecycle_phase.'). Completing it generates stronger learning evidence.',
                    'action_label' => 'Resume mission',
                    'action_url' => route('learnquest.show', $incomplete->mission),
                    'priority' => 'high',
                    'sort_order' => 5,
                    'rule_key' => 'resume_mission:'.$incomplete->mission_id,
                ]);
                $keepKeys[] = $rec->rule_key;
                $created->push($rec);
            } elseif ($skills->isEmpty()) {
                $starter = Mission::query()->where('status', 'published')->orderBy('id')->first();
                $rec = $this->upsert($user, [
                    'mission_id' => $starter?->id,
                    'title' => $starter ? 'Start: '.$starter->title : 'Start a LearnQuest mission',
                    'reason' => 'Complete your first mission to unlock your personalized learning path. FlexLearn needs real learning evidence before it can recommend next steps.',
                    'action_label' => $starter ? 'Open mission' : 'Open Mission Hub',
                    'action_url' => $starter
                        ? route('learnquest.show', $starter)
                        : route('learnquest.index'),
                    'priority' => 'high',
                    'sort_order' => 1,
                    'rule_key' => 'no_evidence_yet',
                ]);
                $keepKeys[] = $rec->rule_key;
                $created->push($rec);
            }

            // Dismiss stale active recommendations that no longer apply
            Recommendation::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['active', 'started'])
                ->when($keepKeys !== [], fn ($q) => $q->whereNotIn('rule_key', $keepKeys))
                ->when($keepKeys === [], fn ($q) => $q)
                ->update(['status' => 'dismissed']);

            return $created->filter()->values();
        });
    }

    /** Backward-compatible alias used by older callers. */
    public function generateFor(User $user): Collection
    {
        return $this->refreshFor($user);
    }

    public function start(Recommendation $recommendation, User $user): Recommendation
    {
        abort_unless($recommendation->user_id === $user->id, 403);

        if (in_array($recommendation->status, ['completed', 'dismissed'], true)) {
            return $recommendation;
        }

        $recommendation->forceFill(['status' => 'started'])->save();

        return $recommendation->fresh();
    }

    public function dismiss(Recommendation $recommendation, User $user): Recommendation
    {
        abort_unless($recommendation->user_id === $user->id, 403);

        $recommendation->forceFill(['status' => 'dismissed'])->save();

        return $recommendation->fresh();
    }

    public function markCompletedForMission(User $user, int $missionId): void
    {
        Recommendation::query()
            ->where('user_id', $user->id)
            ->where('mission_id', $missionId)
            ->whereIn('status', ['active', 'started'])
            ->update(['status' => 'completed']);
    }

    protected function findMissionForSkill(
        int $skillId,
        int $mastery,
        Collection $completedMissionIds,
        Collection $activeMissionIds,
        bool $preferAdvanced = false
    ): ?Mission {
        $query = Mission::query()
            ->where('status', 'published')
            ->whereHas('skills', fn ($q) => $q->where('skills.id', $skillId))
            ->whereNotIn('id', $completedMissionIds);

        if ($preferAdvanced || $mastery >= MasteryService::BAND_PROFICIENT) {
            $query->orderByRaw("CASE difficulty WHEN 'advanced' THEN 1 WHEN 'intermediate' THEN 2 ELSE 3 END");
        } elseif ($mastery < MasteryService::BAND_NEEDS_SUPPORT) {
            $query->orderByRaw("CASE difficulty WHEN 'beginner' THEN 1 WHEN 'intermediate' THEN 2 ELSE 3 END");
        } else {
            $query->orderByRaw("CASE difficulty WHEN 'intermediate' THEN 1 WHEN 'beginner' THEN 2 ELSE 3 END");
        }

        // Prefer not-yet-started, then active
        $missions = $query->get();

        return $missions->first(fn (Mission $m) => ! $activeMissionIds->contains($m->id))
            ?? $missions->first();
    }

    protected function upsert(User $user, array $data): Recommendation
    {
        $existing = Recommendation::query()
            ->where('user_id', $user->id)
            ->where('rule_key', $data['rule_key'])
            ->whereIn('status', ['active', 'started'])
            ->first();

        if ($existing) {
            // Preserve "started" if student already acted
            $status = $existing->status === 'started' ? 'started' : 'active';
            $existing->fill([
                'skill_id' => $data['skill_id'] ?? null,
                'mission_id' => $data['mission_id'] ?? null,
                'assessment_id' => $data['assessment_id'] ?? null,
                'title' => $data['title'],
                'reason' => $data['reason'],
                'action_label' => $data['action_label'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'sort_order' => $data['sort_order'] ?? 50,
                'status' => $status,
            ])->save();

            return $existing;
        }

        return Recommendation::query()->create([
            'user_id' => $user->id,
            'skill_id' => $data['skill_id'] ?? null,
            'mission_id' => $data['mission_id'] ?? null,
            'assessment_id' => $data['assessment_id'] ?? null,
            'title' => $data['title'],
            'reason' => $data['reason'],
            'action_label' => $data['action_label'] ?? null,
            'action_url' => $data['action_url'] ?? null,
            'priority' => $data['priority'] ?? 'medium',
            'sort_order' => $data['sort_order'] ?? 50,
            'status' => 'active',
            'rule_key' => $data['rule_key'],
        ]);
    }
}
