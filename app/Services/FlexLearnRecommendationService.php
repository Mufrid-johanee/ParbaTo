<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\StudentSkill;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Explainable rule/score-based recommendation engine (FlexLearn MVP).
 * No AI dependency — every recommendation includes a human-readable reason.
 */
class FlexLearnRecommendationService
{
    public const MASTERY_LOW = 55;

    public const MASTERY_HIGH = 80;

    public function generateFor(User $user): Collection
    {
        $created = collect();

        $skills = StudentSkill::query()
            ->with('skill')
            ->where('user_id', $user->id)
            ->get();

        foreach ($skills as $studentSkill) {
            if ($studentSkill->mastery < self::MASTERY_LOW) {
                $created->push($this->upsertRecommendation($user, [
                    'skill_id' => $studentSkill->skill_id,
                    'title' => "Practice: {$studentSkill->skill->name}",
                    'reason' => "Recommended because your mastery in {$studentSkill->skill->name} is {$studentSkill->mastery}% (below the ".self::MASTERY_LOW.'% support threshold).',
                    'action_label' => 'Open practice path',
                    'action_url' => route('flexlearn.index'),
                    'priority' => 'high',
                    'rule_key' => 'low_mastery:'.$studentSkill->skill_id,
                ]));
            } elseif ($studentSkill->mastery >= self::MASTERY_HIGH) {
                $created->push($this->upsertRecommendation($user, [
                    'skill_id' => $studentSkill->skill_id,
                    'title' => "Advanced challenge: {$studentSkill->skill->name}",
                    'reason' => "Recommended because your mastery in {$studentSkill->skill->name} is {$studentSkill->mastery}% — ready for an advanced challenge.",
                    'action_label' => 'Browse advanced missions',
                    'action_url' => route('learnquest.index', ['difficulty' => 'advanced']),
                    'priority' => 'medium',
                    'rule_key' => 'high_mastery:'.$studentSkill->skill_id,
                ]));
            }
        }

        $activeMissions = $user->missionEnrollments()
            ->whereIn('status', ['discovered', 'in_progress'])
            ->count();

        if ($activeMissions === 0) {
            $created->push($this->upsertRecommendation($user, [
                'title' => 'Start a LearnQuest mission',
                'reason' => 'Recommended because you have no active missions — project-based practice builds transferable evidence.',
                'action_label' => 'Open Mission Hub',
                'action_url' => route('learnquest.index'),
                'priority' => 'medium',
                'rule_key' => 'no_active_missions',
            ]));
        }

        return $created->filter();
    }

    protected function upsertRecommendation(User $user, array $data): Recommendation
    {
        return Recommendation::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'rule_key' => $data['rule_key'],
                'status' => 'active',
            ],
            [
                'skill_id' => $data['skill_id'] ?? null,
                'mission_id' => $data['mission_id'] ?? null,
                'assessment_id' => $data['assessment_id'] ?? null,
                'title' => $data['title'],
                'reason' => $data['reason'],
                'action_label' => $data['action_label'] ?? null,
                'action_url' => $data['action_url'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
            ]
        );
    }
}
