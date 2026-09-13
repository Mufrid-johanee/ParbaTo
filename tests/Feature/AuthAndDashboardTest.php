<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('ParbaTo');
    }

    public function test_student_can_login_and_view_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'learner@example.com',
            'role' => User::ROLE_STUDENT,
        ]);

        $this->post('/login', [
            'email' => 'learner@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('FlexLearn');
    }

    public function test_student_cannot_access_teacher_analytics(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_STUDENT]);

        $this->actingAs($user)
            ->get('/analytics')
            ->assertForbidden();
    }

    public function test_teacher_can_access_analytics(): void
    {
        $user = User::factory()->teacher()->create();

        $this->actingAs($user)
            ->get('/analytics')
            ->assertOk();
    }
}
