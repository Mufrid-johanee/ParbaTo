<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\XpService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $users = User::query()->latest()->paginate(20);
        $stats = [
            'users' => User::query()->count(),
            'students' => User::query()->where('role', User::ROLE_STUDENT)->count(),
            'teachers' => User::query()->where('role', User::ROLE_TEACHER)->count(),
            'total_xp' => (int) User::query()->where('role', User::ROLE_STUDENT)->sum('xp'),
        ];

        return view('admin.index', compact('users', 'stats'));
    }
}
