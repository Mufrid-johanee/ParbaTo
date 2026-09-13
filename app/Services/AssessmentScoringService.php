<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Question;

/**
 * Server-authoritative scoring. Never trust client is_correct / points / score.
 */
class AssessmentScoringService
{
    public function scoreObjectiveAnswer(Question $question, ?string $selectedOption, ?string $answerText): array
    {
        if ($question->type === 'short_answer') {
            return [
                'is_correct' => null,
                'points_awarded' => null,
                'needs_review' => true,
            ];
        }

        $selected = $this->normalize($selectedOption ?? $answerText);
        $corrects = collect($question->correct_answer ?? [])
            ->map(fn ($v) => $this->normalize(is_bool($v) ? ($v ? 'true' : 'false') : (string) $v))
            ->filter()
            ->values();

        $isCorrect = $selected !== '' && $corrects->contains($selected);

        return [
            'is_correct' => $isCorrect,
            'points_awarded' => $isCorrect ? (float) $question->points : 0.0,
            'needs_review' => false,
        ];
    }

    public function scoreAttempt(AssessmentAttempt $attempt): array
    {
        $attempt->loadMissing(['assessment.questions', 'answerRecords']);

        $maxScore = (float) $attempt->assessment->questions->sum('points');
        $earned = 0.0;
        $needsReview = false;

        foreach ($attempt->assessment->questions as $question) {
            $answer = $attempt->answerRecords->firstWhere('question_id', $question->id);

            if ($question->type === 'short_answer') {
                if ($answer && $answer->points_awarded !== null) {
                    $earned += (float) $answer->points_awarded;
                } else {
                    $needsReview = true;
                }

                continue;
            }

            if (! $answer) {
                continue;
            }

            $result = $this->scoreObjectiveAnswer(
                $question,
                $answer->selected_option,
                $answer->answer_text
            );

            $answer->forceFill([
                'is_correct' => $result['is_correct'],
                'points_awarded' => $result['points_awarded'],
            ])->save();

            $earned += (float) $result['points_awarded'];
        }

        $accuracy = $maxScore > 0 ? round(($earned / $maxScore) * 100, 2) : 0.0;

        return [
            'score' => round($earned, 2),
            'max_score' => $maxScore,
            'accuracy' => $accuracy,
            'needs_review' => $needsReview,
        ];
    }

    public function applyTeacherGrade(AttemptAnswer $answer, float $points, ?string $feedback = null): AttemptAnswer
    {
        $question = $answer->question()->firstOrFail();
        $points = max(0, min((float) $question->points, $points));

        $answer->forceFill([
            'points_awarded' => $points,
            'is_correct' => $points >= ((float) $question->points * 0.5),
            'teacher_feedback' => $feedback,
        ])->save();

        return $answer->fresh();
    }

    protected function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $v = strtolower(trim($value));

        return match ($v) {
            '1', 'yes', 't' => 'true',
            '0', 'no', 'f' => 'false',
            default => $v,
        };
    }
}
