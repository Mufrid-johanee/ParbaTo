<?php

return [
    /*
    |--------------------------------------------------------------------------
    | XP event awards (server-controlled only)
    |--------------------------------------------------------------------------
    */
    'events' => [
        'mission_task_completed' => (int) env('XP_MISSION_TASK', 10),
        'mission_phase_completed' => (int) env('XP_MISSION_PHASE', 25),
        'mission_submitted' => (int) env('XP_MISSION_SUBMIT', 30),
        'mission_evaluated_pass' => (int) env('XP_MISSION_PASS', 50),
        'mission_evaluated_distinction' => (int) env('XP_MISSION_DISTINCTION', 100),
        'assessment_passed' => (int) env('XP_ASSESSMENT_PASS', 40),
        'assessment_excellent' => (int) env('XP_ASSESSMENT_EXCELLENT', 80),
        'classtwin_attendance' => (int) env('XP_ATTENDANCE', 15),
        'daily_login' => (int) env('XP_DAILY_LOGIN', 5),
        'classroom_joined' => (int) env('XP_CLASSROOM_JOIN', 10),
        'portfolio_item_created' => (int) env('XP_PORTFOLIO_ITEM', 20),
        'badge_bonus' => (int) env('XP_BADGE_BONUS', 15),
    ],

    'distinction_score' => 90,

    /*
    |--------------------------------------------------------------------------
    | Named levels (required XP is cumulative total)
    |--------------------------------------------------------------------------
    */
    'levels' => [
        ['level' => 1, 'name' => 'Explorer', 'required_xp' => 0],
        ['level' => 2, 'name' => 'Learner', 'required_xp' => 150],
        ['level' => 3, 'name' => 'Practitioner', 'required_xp' => 400],
        ['level' => 4, 'name' => 'Builder', 'required_xp' => 800],
        ['level' => 5, 'name' => 'Innovator', 'required_xp' => 1500],
        ['level' => 6, 'name' => 'Architect', 'required_xp' => 3000],
        ['level' => 7, 'name' => 'Mentor', 'required_xp' => 5000],
        ['level' => 8, 'name' => 'Master', 'required_xp' => 10000],
    ],
];
