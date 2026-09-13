<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\Question;
use App\Models\Skill;
use App\Services\AssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherAssessmentController extends Controller
{
    public function __construct(protected AssessmentService $assessments) {}

    public function index(Request $request): View
    {
        $this->authorize('create', Assessment::class);

        $items = Assessment::query()
            ->withCount(['questions', 'attempts'])
            ->with(['classroom', 'course'])
            ->where('created_by', $request->user()->id)
            ->latest()
            ->paginate(12);

        return view('assessments.teacher.index', compact('items'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Assessment::class);

        $classrooms = Classroom::query()->where('teacher_id', $request->user()->id)->orderBy('name')->get();
        $courses = Course::query()->where('teacher_id', $request->user()->id)->orderBy('title')->get();

        return view('assessments.teacher.create', compact('classrooms', 'courses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Assessment::class);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:quiz,assignment,pre_assessment,post_assessment,practical,project'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'instructions' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'pass_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        if (! empty($data['classroom_id'])) {
            $classroom = Classroom::query()->findOrFail($data['classroom_id']);
            abort_unless($classroom->isOwnedBy($request->user()), 403);
        }

        $assessment = $this->assessments->create($request->user(), $data);

        return redirect()
            ->route('teacher.assessments.edit', $assessment)
            ->with('status', 'Assessment draft created. Add questions to publish.');
    }

    public function edit(Request $request, Assessment $assessment): View
    {
        $this->authorize('update', $assessment);

        $assessment->load(['questions.skill', 'classroom', 'course']);
        $classrooms = Classroom::query()->where('teacher_id', $request->user()->id)->orderBy('name')->get();
        $courses = Course::query()->where('teacher_id', $request->user()->id)->orderBy('title')->get();
        $skills = Skill::query()->orderBy('name')->get();

        $attemptStats = [
            'total' => $assessment->attempts()->count(),
            'submitted' => $assessment->attempts()->where('status', 'submitted')->count(),
            'graded' => $assessment->attempts()->where('status', 'graded')->count(),
            'in_progress' => $assessment->attempts()->where('status', 'in_progress')->count(),
        ];

        return view('assessments.teacher.edit', compact('assessment', 'classrooms', 'courses', 'skills', 'attemptStats'));
    }

    public function update(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:quiz,assignment,pre_assessment,post_assessment,practical,project'],
            'classroom_id' => ['nullable', 'exists:classrooms,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'instructions' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'pass_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $this->assessments->update($assessment, $data);

        return back()->with('status', 'Assessment updated.');
    }

    public function storeQuestion(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);

        $data = $request->validate([
            'type' => ['required', 'in:mcq,true_false,short_answer'],
            'prompt' => ['required', 'string'],
            'skill_id' => ['nullable', 'exists:skills,id'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'explanation' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.*' => ['nullable', 'string', 'max:500'],
            'correct_option' => ['nullable', 'string'],
            'correct_true_false' => ['nullable', 'in:true,false'],
        ]);

        if ($data['type'] === 'mcq') {
            $options = array_values(array_filter($data['options'] ?? []));
            abort_if(count($options) < 2, 422, 'MCQ needs at least 2 options.');
            $correct = $data['correct_option'] ?? $options[0];
            $data['options'] = $options;
            $data['correct_answer'] = [$correct];
        } elseif ($data['type'] === 'true_false') {
            $data['options'] = ['true', 'false'];
            $data['correct_answer'] = [$data['correct_true_false'] ?? 'true'];
        } else {
            $data['options'] = null;
            $data['correct_answer'] = null;
        }

        $this->assessments->addQuestion($assessment, $data);

        return back()->with('status', 'Question added.');
    }

    public function destroyQuestion(Request $request, Assessment $assessment, Question $question): RedirectResponse
    {
        $this->authorize('update', $assessment);
        abort_unless((int) $question->assessment_id === (int) $assessment->id, 404);
        $question->delete();

        return back()->with('status', 'Question removed.');
    }

    public function publish(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('publish', $assessment);
        $this->assessments->publish($assessment);

        return back()->with('status', 'Assessment published.');
    }

    public function unpublish(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('publish', $assessment);
        $this->assessments->unpublish($assessment);

        return back()->with('status', 'Assessment unpublished.');
    }

    public function archive(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorize('update', $assessment);
        $this->assessments->archive($assessment);

        return redirect()->route('teacher.assessments.index')->with('status', 'Assessment archived.');
    }

    public function attempts(Request $request, Assessment $assessment): View
    {
        $this->authorize('grade', $assessment);

        $attempts = $assessment->attempts()
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('assessments.teacher.attempts', compact('assessment', 'attempts'));
    }

    public function showAttempt(Request $request, Assessment $assessment, AssessmentAttempt $attempt): View
    {
        $this->authorize('grade', $attempt);
        abort_unless((int) $attempt->assessment_id === (int) $assessment->id, 404);

        $attempt->load(['user', 'answerRecords.question.skill', 'assessment.questions']);

        return view('assessments.teacher.grade', compact('assessment', 'attempt'));
    }

    public function gradeAttempt(Request $request, Assessment $assessment, AssessmentAttempt $attempt): RedirectResponse
    {
        $this->authorize('grade', $attempt);
        abort_unless((int) $attempt->assessment_id === (int) $assessment->id, 404);

        $data = $request->validate([
            'grades' => ['required', 'array'],
            'grades.*.question_id' => ['required', 'integer'],
            'grades.*.points_awarded' => ['required', 'numeric', 'min:0'],
            'grades.*.teacher_feedback' => ['nullable', 'string'],
        ]);

        $this->assessments->gradeShortAnswers($attempt, $request->user(), $data['grades']);

        return back()->with('status', 'Grading saved.');
    }
}
