<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
use App\Models\StudentSkill;
use App\Services\FlexLearnRecommendationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlexLearnController extends Controller
{
    public function __invoke(Request $request, FlexLearnRecommendationService $flexLearn): View
    {
        $user = $request->user();
        $flexLearn->generateFor($user);

        $skills = StudentSkill::query()
            ->with('skill')
            ->where('user_id', $user->id)
            ->orderBy('mastery')
            ->get();

        $recommendations = Recommendation::query()
            ->with(['skill', 'mission'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderByRaw("FIELD(priority, 'high', 'medium', 'low')")
            ->get();

        return view('flexlearn.index', compact('user', 'skills', 'recommendations'));
    }
}
