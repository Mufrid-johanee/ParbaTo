<?php

namespace App\Services;

use App\Models\LearningEvidence;
use App\Models\MissionEnrollment;
use App\Models\StudentSkill;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Deterministic mastery calculation from learning evidence.
 * Same evidence → same mastery. No AI / randomness.
 */
class MasteryService
{
    public const BAND_NEEDS_SUPPORT = 40;

    public const BAND_DEVELOPING = 60;

    public const BAND_PROFICIENT = 80;

    public static function bandLabel(int $mastery): string
    {
        return match (true) {
            $mastery < self::BAND_NEEDS_SUPPORT => 'Needs Support',
            $mastery < self::BAND_DEVELOPING => 'Developing',
            $mastery < self::BAND_PROFICIENT => 'Proficient',
            default => 'Advanced',
        };
    }

    public static function bandKey(int $mastery): string
    {
        return match (true) {
            $mastery < self::BAND_NEEDS_SUPPORT => 'needs_support',
            $mastery < self::BAND_DEVELOPING => 'developing',
            $mastery < self::BAND_PROFICIENT => 'proficient',
            default => 'advanced',
        };
    }

    /**
     * Record evidence and recalculate mastery for that skill.
     */
    public function record(
        User $user,
        int $skillId,
        string $sourceType,
        int $sourceId,
        string $evidenceType,
        float $score,
        int $weight = 50,
        ?array $meta = null
    ): LearningEvidence {
        return DB::transaction(function () use ($user, $skillId, $sourceType, $sourceId, $evidenceType, $score, $weight, $meta) {
            $evidence = LearningEvidence::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'skill_id' => $skillId,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                    'evidence_type' => $evidenceType,
                ],
                [
                    'score' => max(0, min(100, $score)),
                    'weight' => max(1, min(100, $weight)),
                    'meta' => $meta,
                ]
            );

            $this->recalculateSkill($user, $skillId);

            return $evidence;
        });
    }

    public function recalculateSkill(User $user, int $skillId): StudentSkill
    {
        $evidences = LearningEvidence::query()
            ->where('user_id', $user->id)
            ->where('skill_id', $skillId)
            ->get();

        $studentSkill = StudentSkill::query()->firstOrNew([
            'user_id' => $user->id,
            'skill_id' => $skillId,
        ]);

        if ($evidences->isEmpty()) {
            $studentSkill->mastery = (int) ($studentSkill->mastery ?? 0);
            $studentSkill->evidence_count = 0;
            $studentSkill->save();

            return $studentSkill;
        }

        $weightedSum = 0.0;
        $totalWeight = 0.0;

        foreach ($evidences as $evidence) {
            $w = (float) $evidence->weight;
            $weightedSum += ((float) $evidence->score) * $w;
            $totalWeight += $w;
        }

        $mastery = $totalWeight > 0 ? (int) round($weightedSum / $totalWeight) : 0;

        $studentSkill->mastery = max(0, min(100, $mastery));
        $studentSkill->evidence_count = $evidences->count();
        $studentSkill->last_assessed_at = $evidences->max('updated_at') ?? now();
        $studentSkill->save();

        return $studentSkill;
    }

    public function recalculateAllFor(User $user): Collection
    {
        $skillIds = LearningEvidence::query()
            ->where('user_id', $user->id)
            ->distinct()
            ->pluck('skill_id');

        return $skillIds->map(fn ($skillId) => $this->recalculateSkill($user, (int) $skillId));
    }

    /**
     * Apply mission evaluation as learning evidence for each mission skill.
     */
    public function applyMissionEvaluation(MissionEnrollment $enrollment, float $score): void
    {
        $enrollment->loadMissing(['mission.skills', 'user']);
        $user = $enrollment->user;

        foreach ($enrollment->mission->skills as $skill) {
            $this->record(
                $user,
                $skill->id,
                MissionEnrollment::class,
                $enrollment->id,
                'mission_evaluation',
                $score,
                70,
                [
                    'mission_id' => $enrollment->mission_id,
                    'mission_title' => $enrollment->mission->title,
                    'feedback' => $enrollment->submission?->feedback,
                ]
            );
        }
    }

    /**
     * @return array{strengths: Collection, developing: Collection, needs_support: Collection}
     */
    public function classify(User $user): array
    {
        $skills = StudentSkill::query()
            ->with('skill')
            ->where('user_id', $user->id)
            ->orderByDesc('mastery')
            ->get();

        return [
            'strengths' => $skills->filter(fn (StudentSkill $s) => $s->mastery >= self::BAND_PROFICIENT)->values(),
            'developing' => $skills->filter(fn (StudentSkill $s) => $s->mastery >= self::BAND_NEEDS_SUPPORT && $s->mastery < self::BAND_PROFICIENT)->values(),
            'needs_support' => $skills->filter(fn (StudentSkill $s) => $s->mastery < self::BAND_NEEDS_SUPPORT)->values(),
        ];
    }
}
