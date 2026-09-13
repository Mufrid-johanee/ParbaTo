<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinClassroomRequest;
use App\Http\Requests\StartClassSessionRequest;
use App\Http\Requests\StoreClassroomRequest;
use App\Http\Requests\UpdateClassroomRequest;
use App\Models\Classroom;
use App\Models\ClassroomMember;
use App\Models\Mission;
use App\Models\User;
use App\Services\ClassroomService;
use App\Services\ClassSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole('teacher', 'admin')) {
            $owned = Classroom::query()
                ->with(['course', 'teacher', 'sessions' => fn ($q) => $q->where('status', 'live')])
                ->withCount(['members' => fn ($q) => $q->where('status', 'active')])
                ->where('teacher_id', $user->id)
                ->where('status', 'active')
                ->latest()
                ->get();
        } else {
            $owned = collect();
        }

        $joined = Classroom::query()
            ->with(['course', 'teacher', 'sessions' => fn ($q) => $q->where('status', 'live')])
            ->withCount(['members' => fn ($q) => $q->where('status', 'active')])
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'))
            ->where('status', 'active')
            ->latest()
            ->get();

        return view('classrooms.index', compact('owned', 'joined'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Classroom::class);

        $courses = $request->user()->hasRole('teacher', 'admin')
            ? \App\Models\Course::query()
                ->where('teacher_id', $request->user()->id)
                ->orderBy('title')
                ->get()
            : collect();

        return view('classrooms.create', compact('courses'));
    }

    public function store(StoreClassroomRequest $request, ClassroomService $classrooms): RedirectResponse
    {
        $classroom = $classrooms->create($request->user(), $request->validated());

        return redirect()
            ->route('classrooms.show', $classroom)
            ->with('status', 'Classroom created. Share join code '.$classroom->join_code.' with students.');
    }

    public function show(Request $request, Classroom $classroom, ClassSessionService $sessions): View
    {
        $this->authorize('view', $classroom);

        $classroom->load([
            'course',
            'teacher',
            'members' => fn ($q) => $q->where('status', 'active')->with('user'),
        ]);

        $live = $classroom->liveSession();
        $history = $classroom->sessions()
            ->where('status', 'ended')
            ->latest('ended_at')
            ->take(12)
            ->get()
            ->map(fn ($session) => [
                'session' => $session,
                'stats' => $sessions->historyStats($session),
            ]);

        $missions = Mission::query()
            ->where('status', 'published')
            ->when($classroom->course_id, fn ($q) => $q->where(function ($inner) use ($classroom) {
                $inner->where('course_id', $classroom->course_id)->orWhereNull('course_id');
            }))
            ->latest()
            ->take(6)
            ->get();

        $isTeacher = $classroom->isOwnedBy($request->user());
        $isMember = $classroom->hasMember($request->user());

        return view('classrooms.show', compact(
            'classroom',
            'live',
            'history',
            'missions',
            'isTeacher',
            'isMember'
        ));
    }

    public function edit(Request $request, Classroom $classroom): View
    {
        $this->authorize('update', $classroom);

        return view('classrooms.edit', compact('classroom'));
    }

    public function update(UpdateClassroomRequest $request, Classroom $classroom, ClassroomService $classrooms): RedirectResponse
    {
        $classrooms->update($classroom, $request->user(), $request->validated());

        return redirect()
            ->route('classrooms.show', $classroom)
            ->with('status', 'Classroom updated.');
    }

    public function archive(Request $request, Classroom $classroom, ClassroomService $classrooms): RedirectResponse
    {
        $this->authorize('update', $classroom);
        $classrooms->archive($classroom, $request->user());

        return redirect()
            ->route('classrooms.index')
            ->with('status', 'Classroom archived.');
    }

    public function removeMember(
        Request $request,
        Classroom $classroom,
        User $student,
        ClassroomService $classrooms
    ): RedirectResponse {
        $this->authorize('removeMember', $classroom);
        $classrooms->removeMember($classroom, $request->user(), $student);

        return back()->with('status', $student->preferredName().' was removed from the classroom.');
    }

    public function joinForm(): View
    {
        $this->authorize('join', Classroom::class);

        return view('classrooms.join');
    }

    public function join(JoinClassroomRequest $request, ClassroomService $classrooms): RedirectResponse
    {
        $member = $classrooms->joinByCode($request->user(), $request->validated('code'));

        return redirect()
            ->route('classrooms.show', $member->classroom_id)
            ->with('status', 'You joined the classroom successfully.');
    }

    public function startSession(
        StartClassSessionRequest $request,
        Classroom $classroom,
        ClassSessionService $sessions
    ): RedirectResponse {
        $result = $sessions->start($classroom, $request->user(), $request->validated());

        return redirect()
            ->route('classtwin.show', $result['session'])
            ->with('status', 'Live session started.')
            ->with('attendance_code', $result['plain_code']);
    }
}
